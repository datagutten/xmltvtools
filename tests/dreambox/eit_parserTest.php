<?php

namespace datagutten\dreambox\tests;

use datagutten\dreambox\eit_parser;
use PHPUnit\Framework\TestCase;

class eit_parserTest extends TestCase
{

    public function testParse()
    {

    }

    public function testGet_codepage()
    {

    }

    public function testGet_string()
    {

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
}
