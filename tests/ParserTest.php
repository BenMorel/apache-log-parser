<?php

namespace BenMorel\ApacheLogParser\Tests;

use BenMorel\ApacheLogParser\Parser;

use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    /**
     * @dataProvider providerParse
     *
     * @param string $logFormat
     * @param string $line
     * @param bool   $assoc
     * @param array  $expected
     */
    public function testParse(string $logFormat, string $line, bool $assoc, array $expected) : void
    {
        $parser = new Parser($logFormat);
        $actual = $parser->parse($line, $assoc);

        $this->assertSame($expected, $actual);
    }

    /**
     * @return array
     */
    public function providerParse() : array
    {
        return [
            [
                '%h %l %u %t "%{Host}i" "%r" %>s %b "%{Referer}i" "%{User-Agent}i"',
                '1.2.3.4 - - [30/May/2018:15:00:23 +0200] "www.example.com" "GET / HTTP/1.0" 200 1234 "-" "Mozilla/5.0"',
                false,
                [
                    '1.2.3.4',
                    '-',
                    '-',
                    '30/May/2018:15:00:23 +0200',
                    'www.example.com',
                    'GET / HTTP/1.0',
                    '200',
                    '1234',
                    '-',
                    'Mozilla/5.0'
                ]
            ], [
                '%h,%t,"%{Host}i","%r",%>s,%b,%{msec}t',
                '::1,[30/May/2018:13:13:20 +0000],"localhost","GET /a?b&c HTTP/1.1",404,199,1527686000427' . "\r\n",
                true,
                [
                    'remoteHostname'     => '::1',
                    'time'               => '30/May/2018:13:13:20 +0000',
                    'requestHeader:Host' => 'localhost',
                    'firstRequestLine'   => 'GET /a?b&c HTTP/1.1',
                    'status'             => '404',
                    'responseSize'       => '199',
                    'time:msec'          => '1527686000427'
                ]
            ]
        ];
    }

    /**
     * @dataProvider providerParseWithInvalidFormatString
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown format string
     *
     * @param string $logFormat
     */
    public function testParseWithUnknownFormatString(string $logFormat) : void
    {
        new Parser($logFormat);
    }

    /**
     * @return array
     */
    public function providerParseWithInvalidFormatString() : array
    {
        return [
            ['%^'],
            ['%_'],
            ['%Z']
        ];
    }
}
