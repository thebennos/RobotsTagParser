<?php
namespace vipnytt;

/**
 * X-Robots-Tag HTTP header parser class
 *
 * @author VIP nytt (vipnytt@gmail.com)
 * @author Jan-Petter Gundersen (europe.jpg@gmail.com)
 *
 * Project:
 * @link https://github.com/VIPnytt/X-Robots-Tag-parser
 * @license https://opensource.org/licenses/MIT MIT license
 *
 * Specification:
 * @link https://developers.google.com/webmasters/control-crawl-index/docs/robots_meta_tag#using-the-x-robots-tag-http-header
 */

use GuzzleHttp;
use vipnytt\XRobotsTagParser\Exceptions\XRobotsTagParserException;
use vipnytt\XRobotsTagParser\Rebuild;
use vipnytt\XRobotsTagParser\UserAgentParser;

class XRobotsTagParser
{
    const HEADER_RULE_IDENTIFIER = 'X-Robots-Tag';
    const USERAGENT_DEFAULT = '';

    const DIRECTIVE_ALL = 'all';
    const DIRECTIVE_NONE = 'none';
    const DIRECTIVE_NO_ARCHIVE = 'noarchive';
    const DIRECTIVE_NO_FOLLOW = 'nofollow';
    const DIRECTIVE_NO_IMAGE_INDEX = 'noimageindex';
    const DIRECTIVE_NO_INDEX = 'noindex';
    const DIRECTIVE_NO_ODP = 'noodp';
    const DIRECTIVE_NO_SNIPPET = 'nosnippet';
    const DIRECTIVE_NO_TRANSLATE = 'notranslate';
    const DIRECTIVE_UNAVAILABLE_AFTER = 'unavailable_after';

    protected $url = '';
    protected $userAgent = self::USERAGENT_DEFAULT;
    protected $userAgentMatch = self::USERAGENT_DEFAULT;
    protected $config = [];

    protected $headers = [];
    protected $currentRule = '';
    protected $currentUserAgent = self::USERAGENT_DEFAULT;

    protected $rules = [];

    /**
     * Constructor
     *
     * @param string $url
     * @param string $userAgent
     * @param array $config
     * @throws XRobotsTagParserException
     */
    public function __construct($url, $userAgent = self::USERAGENT_DEFAULT, array $config = [])
    {
        $this->url = $url;
        if (!filter_var($this->url, FILTER_VALIDATE_URL)) {
            throw new XRobotsTagParserException('Invalid URL provided');
        }
        // User-Agent for HTTP request
        $this->userAgent = $userAgent;
        // Set any optional configuration options
        $this->config = $config;
        // Parse rules
        $this->parse();
        // User-Agent matching rules
        $parser = new UserAgentParser($this->userAgent);
        $this->userAgentMatch = $parser->match(array_keys($this->rules), self::USERAGENT_DEFAULT);
    }

    /**
     * Parse HTTP headers
     *
     * @return void
     */
    protected function parse()
    {
        $this->headers = $this->selectHeaderSource();
        foreach ($this->headers as $header) {
            $parts = array_map('trim', explode(':', mb_strtolower($header), 2));
            if (count($parts) < 2 || $parts[0] != mb_strtolower(self::HEADER_RULE_IDENTIFIER)) {
                // Header is not a rule
                continue;
            }
            $this->currentRule = $parts[1];
            $this->detectDirectives();
        }
    }

    /**
     * Select HTTP header source
     *
     * @return array
     */
    protected function selectHeaderSource()
    {
        if (isset($this->config['headers']) && is_array($this->config['headers'])) {
            return $this->config['headers'];
        }
        // No provided HTTP headers
        return $this->getHeaders();
    }

    /**
     * Request the HTTP headers from an URL
     *
     * @return array Raw HTTP headers
     * @throws XRobotsTagParserException
     */
    protected function getHeaders()
    {
        try {
            if (!isset($this->config['guzzle']['headers']['User-Agent'])) {
                $this->config['guzzle']['headers']['User-Agent'] = $this->userAgent;
            }
            $client = new GuzzleHttp\Client();
            $res = $client->head($this->url, $this->config['guzzle']);
            $headers = [];
            foreach ($res->getHeader(self::HEADER_RULE_IDENTIFIER) as $name => $values) {
                $headers[] = $name . ': ' . implode(' ', $values) . "\r\n";
            }
            return $headers;
        } catch (GuzzleHttp\Exception\TransferException $e) {
            throw new XRobotsTagParserException($e->getMessage());
        }
    }

    /**
     * Detect directives in rule
     *
     * @return void
     */
    protected function detectDirectives()
    {
        $directives = array_map('trim', explode(',', $this->currentRule));
        $pair = array_map('trim', explode(':', $directives[0], 2));
        if (count($pair) == 2 && !in_array($pair[0], array_keys($this->directiveClasses()))) {
            $this->currentUserAgent = $pair[0];
            $directives[0] = $pair[1];
        }
        foreach ($directives as $rule) {
            $directive = trim(explode(':', $rule, 2)[0]);
            if (in_array($directive, array_keys($this->directiveClasses()))) {
                $this->addRule($directive);
            }
        }
        $this->cleanup();
    }

    /**
     * Array of directives and their class names
     *
     * @return array
     */
    protected function directiveClasses()
    {
        return [
            self::DIRECTIVE_ALL => 'All',
            self::DIRECTIVE_NO_ARCHIVE => 'NoArchive',
            self::DIRECTIVE_NO_FOLLOW => 'NoFollow',
            self::DIRECTIVE_NO_IMAGE_INDEX => 'NoImageIndex',
            self::DIRECTIVE_NO_INDEX => 'NoIndex',
            self::DIRECTIVE_NONE => 'None',
            self::DIRECTIVE_NO_ODP => 'NoODP',
            self::DIRECTIVE_NO_SNIPPET => 'NoSnippet',
            self::DIRECTIVE_NO_TRANSLATE => 'NoTranslate',
            self::DIRECTIVE_UNAVAILABLE_AFTER => 'UnavailableAfter',
        ];
    }

    /**
     * Add rule
     *
     * @param string $directive
     * @return void
     * @throws XRobotsTagParserException
     */
    protected function addRule($directive)
    {
        if (!isset($this->rules[$this->currentUserAgent])) {
            $this->rules[$this->currentUserAgent] = [];
        }
        $class = "\\" . __CLASS__ . "\\directives\\" . $this->directiveClasses()[$directive];
        $object = new $class($this->currentRule);
        if (!$object instanceof XRobotsTagParser\directives\directiveInterface) {
            throw new XRobotsTagParserException('Unsupported directive class');
        }
        $this->rules[$this->currentUserAgent] = array_merge($this->rules[$this->currentUserAgent], [$object->getDirective() => $object->getValue()]);
    }

    /**
     * Cleanup before next rule is read
     *
     * @return void
     */
    protected function cleanup()
    {
        $this->currentRule = '';
        $this->currentUserAgent = self::USERAGENT_DEFAULT;
    }

    /**
     * Return all applicable rules
     *
     * @param bool $raw
     * @return array
     */
    public function getRules($raw = false)
    {
        $rules = [];
        // Default UserAgent
        if (isset($this->rules[self::USERAGENT_DEFAULT])) {
            $rules = array_merge($rules, $this->rules[self::USERAGENT_DEFAULT]);
        }
        // Matching UserAgent
        if (isset($this->rules[$this->userAgentMatch])) {
            $rules = array_merge($rules, $this->rules[$this->userAgentMatch]);
        }
        if (!$raw) {
            $rebuild = new Rebuild($rules);
            $rules = $rebuild->getResult();
        }
        // Result
        return $rules;
    }

    /**
     * Export all rules for all UserAgents
     *
     * @return array
     */
    public function export()
    {
        return $this->rules;
    }

    /**
     * Get the meaning of an Directive
     *
     * @param string $directive
     * @return string
     * @throws XRobotsTagParserException
     */
    public function getDirectiveMeaning($directive)
    {
        if (!in_array($directive, array_keys($this->directiveClasses()))) {
            throw new XRobotsTagParserException('Unknown directive');
        }
        $class = "\\" . __CLASS__ . "\\directives\\" . $this->directiveClasses()[$directive];
        $object = new $class($this->directiveClasses()[$directive]);
        if (!$object instanceof XRobotsTagParser\directives\directiveInterface) {
            throw new XRobotsTagParserException('Unsupported directive class');
        }
        return $object->getMeaning();
    }
}
