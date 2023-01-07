<?php

namespace datagutten\dreambox\tests;

use datagutten\dreambox\eit_parser;
use datagutten\dreambox\exceptions\EitException;
use PHPUnit\Framework\TestCase;

class eit_parserTest extends TestCase
{
    public function testMJD()
    {
        $ymd = eit_parser::parseMJD(58759);
        $this->assertSame(2019, $ymd[0]);
        $this->assertSame(10, $ymd[1]);
        $this->assertSame(3, $ymd[2]);
    }

    public function testParse()
    {
        $data = file_get_contents(__DIR__ . '/test_data/Ice Road Rescue S04E01 - Ekstremvær HD.eit');
        $info = eit_parser::parse($data);
        $this->assertSame('Vinterveiens helter', $info['name']);
        $this->assertSame('(1:8/s4) Ekstremvær. Norsk dokumentarserie fra 2019.', $info['short_description']);
        $this->assertSame('Bergingsbilene har fått en ny fiende: ekstremvær. Brå temperaturendringer gir mannskapene uventede utfordringer.', $info['description']);
    }

    public function testParse2()
    {
        $data = file_get_contents(__DIR__ . '/test_data/20180807 2000 - arte HD - The Bomb.eit');
        $info = eit_parser::parse($data);
        $this->assertSame('The Bomb', $info['name']);
        $this->assertSame('<x>SCHEDULE</x>Dokumentarfilm USA 2015', $info['short_description']);
        $this->assertSame('August 1945. Er erzählt die Geschichte des Wettlaufs um die Entwicklung der ersten Atombombe und des späteren atomaren Wettrüstens während des Kalten Krieges.', $info['description']);
    }

    public function testParseInvalidFile()
    {
        $this->expectException(EitException::class);
        $this->expectExceptionMessage('Invalid file');
        eit_parser::parse('asdf');
    }

    public function testGetHeader()
    {
        $data = file_get_contents(__DIR__ . '/test_data/Ice Road Rescue S04E01 - Ekstremvær HD.eit');
        $info = eit_parser::parse_header($data);
        $this->assertSame(array(2019, 10, 3), $info['date']);
        $this->assertSame(array(1, 0, 0), $info['duration']);
        $this->assertSame(array(20, 0, 0), $info['time']);
    }

    public function testGet_codepage()
    {
        $data = [
            1 => 'iso-8859-5',
            2 => 'iso-8859-6',
            3 => 'iso-8859-7',
            4 => 'iso-8859-8',
            5 => 'iso-8859-9',
            6 => 'iso-8859-10',
            7 => 'iso-8859-11',
            9 => 'iso-8859-13',
            10 => 'iso-8859-14',
            11 => 'iso-8859-15',
            21 => 'utf-8'];

        foreach ($data as $code => $codepage)
        {
            $this->assertEquals($codepage, eit_parser::get_codepage($code));
        }

        $data = file_get_contents(__DIR__ . '/test_data/Ice Road Rescue S04E01 - Ekstremvær HD.eit');
        $this->assertEquals('iso-8859-9', eit_parser::get_codepage(ord($data[18])));
    }

    public function testInvalidCodepage()
    {
        $this->expectException(EitException::class);
        $this->expectExceptionMessage('Unknown codepage id 99');
        eit_parser::get_codepage(99);
    }

    public function testGet_stringWithInvalidCodepage()
    {
        $string = eit_parser::get_string('aasdf' . mb_convert_encoding('æøå', 'ISO-8859-1'), 0, 8);
        $this->assertEquals('asdfæøå', $string);
    }

    public function testSeason_episode1()
    {
        $string = '(1:8/s4) Ekstremvær. Norsk dokumentarserie fra 2019.';
        $info = eit_parser::season_episode($string);
        $this->assertSame($info['season'], 4);
        $this->assertSame($info['episode'], 1);
    }

    public function testSeason_episode2()
    {
        $string = '(12) Idolet til Milo kommer til byen. Elliot får seg en ny jobb.';
        $info = eit_parser::season_episode($string);
        $this->assertSame($info['episode'], 12);
    }

    public function testSeason_episode3()
    {
        $string = '(2:12) Idolet til Milo kommer til byen. Elliot får seg en ny jobb.';
        $info = eit_parser::season_episode($string);
        $this->assertSame($info['episode'], 2);
    }

    public function testSeason_episode4()
    {
        $string = '(3:15/s3)';
        $info = eit_parser::season_episode($string);
        $this->assertSame($info['episode'], 3);
        $this->assertSame($info['season'], 3);
    }
}
