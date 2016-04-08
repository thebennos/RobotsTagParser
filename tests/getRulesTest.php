<?php
namespace vipnytt\XRobotsTagParser\Tests;

use vipnytt\XRobotsTagParser;

class GetRulesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Get rules
     *
     * @dataProvider generateDataForTest
     * @param string $userAgent
     * @param array $headers
     */
    public function testGetRules($userAgent, $headers)
    {
        $parser = new XRobotsTagParser($userAgent, $headers);
        $this->assertInstanceOf('vipnytt\XRobotsTagParser', $parser);

        $this->assertTrue($parser->getRules(true)['noindex']);
        $this->assertTrue($parser->getRules(true)['noarchive']);
        $this->assertTrue($parser->getRules(true)['noodp']);
    }

    /**
     * Generate test data
     * @return array
     */
    public function generateDataForTest()
    {
        return [
            [
                'googlebot',
                [
                    'X-Robots-Tag: googlebot: noindex, noarchive',
                    'X-Robots-Tag: bingbot: noindex, noodp',
                    'X-Robots-Tag: noindex, noodp',
                ]
            ]
        ];
    }
}
