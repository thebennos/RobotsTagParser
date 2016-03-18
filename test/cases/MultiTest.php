<?php

namespace vipnytt\XRobotsTagParser\tests;

use vipnytt\XRobotsTagParser;

class MultiTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Multi directives test
     *
     * @dataProvider generateDataForTest
     * @param string $url
     * @param string $bot
     * @param array $options
     */
    public function testMultipleDirectives($url, $bot, $options)
    {
        $parser = new XRobotsTagParser($url, $bot, $options);
        $this->assertInstanceOf('vipnytt\XRobotsTagParser', $parser);

        $this->assertTrue($parser->getRules(true)['all']);
        $this->assertTrue($parser->export()['']['all']);
        $this->assertTrue($parser->export()['googlebot']['all']);

        $this->assertTrue($parser->getRules(true)['noindex']);
        $this->assertTrue($parser->export()['']['noindex']);
        $this->assertTrue($parser->export()['googlebot']['noindex']);

        $this->assertTrue($parser->getRules(true)['nofollow']);
        $this->assertTrue($parser->export()['']['nofollow']);
        $this->assertTrue($parser->export()['googlebot']['nofollow']);

        $this->assertTrue($parser->getRules(true)['none']);
        $this->assertTrue($parser->export()['']['none']);
        $this->assertTrue($parser->export()['googlebot']['none']);

        $this->assertTrue($parser->getRules(true)['noarchive']);
        $this->assertTrue($parser->export()['']['noarchive']);
        $this->assertTrue($parser->export()['googlebot']['noarchive']);

        $this->assertTrue($parser->getRules(true)['nosnippet']);
        $this->assertTrue($parser->export()['']['nosnippet']);
        $this->assertTrue($parser->export()['googlebot']['nosnippet']);

        $this->assertTrue($parser->getRules(true)['noodp']);
        $this->assertTrue($parser->export()['']['noodp']);
        $this->assertTrue($parser->export()['googlebot']['noodp']);

        $this->assertTrue($parser->getRules(true)['notranslate']);
        $this->assertTrue($parser->export()['']['notranslate']);
        $this->assertTrue($parser->export()['googlebot']['notranslate']);

        $this->assertTrue($parser->getRules(true)['noimageindex']);
        $this->assertTrue($parser->export()['']['noimageindex']);
        $this->assertTrue($parser->export()['googlebot']['noimageindex']);
    }

    /**
     * Generate test data
     * @return array
     */
    public function generateDataForTest()
    {
        return [
            [
                'http://example.com/',
                'googlebot',
                ['headers' =>
                    [
                        'HTTP/1.1 200 OK',
                        'Date: Tue, 25 May 2010 21:42:43 GMT',
                        'X-Robots-Tag: all',
                        'X-Robots-Tag: noindex',
                        'X-Robots-Tag: nofollow',
                        'X-Robots-Tag: none',
                        'X-Robots-Tag: noarchive',
                        'X-Robots-Tag: nosnippet',
                        'X-Robots-Tag: noodp',
                        'X-Robots-Tag: notranslate',
                        'X-Robots-Tag: noimageindex',
                        'X-Robots-Tag: unavailable_after: Friday, 25 Jun 2010 15:00:00 PST',
                        'X-Robots-Tag: googlebot: all, none, nofollow,nosnippet,notranslate, unavailable_after: Friday, 25 Jun 2010 15:00:00 PST, noindex, noarchive, noodp,noimageindex'
                    ]
                ]
            ]
        ];
    }
}
