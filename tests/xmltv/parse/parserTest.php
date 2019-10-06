<?php
/**
 * Created by PhpStorm.
 * User: abi
 * Date: 04.10.2019
 * Time: 15:00
 */

namespace datagutten\xmltv\tests\tools\xmltv\parse;

use datagutten\xmltv\tools\parse\InvalidXMLFileException;
use datagutten\xmltv\tools\parse\parser;
use FileNotFoundException;
use PHPUnit\Framework\TestCase;

class parserTest extends TestCase
{
    /**
     * @var parser
     */
    public $parser;
    public function setUp(): void
    {
        $config = file_get_contents(__DIR__.'/test_config.php');
        $config = str_replace('__DIR__', __DIR__, $config);
        file_put_contents(__DIR__.'/config.php', $config);
        set_include_path(__DIR__);
        $this->parser = new parser();
    }
    public function tearDown(): void
    {
        unlink(__DIR__.'/config.php');
    }

    public function testFind_program()
    {
        $program = $this->parser->find_program(strtotime('2019-10-03 22:55'), 'natgeo.no');
        $this->assertEquals('Vinterveiens helter', $program->{'title'});
        $this->assertEquals('20191003230000 +0000', $program->attributes()->{'start'});
    }

    public function testGet_programs()
    {
        $programs = $this->parser->get_programs('natgeo.no', strtotime('2019-10-04'));
        $this->assertIsArray($programs);
        $this->assertEquals('20191004000000 +0000', $programs[0]->attributes()->{'start'});
    }

    public function testGet_programsNotCombined()
    {
        $programs = $this->parser->get_programs('natgeo.no', strtotime('2019-10-04'), false);
        $this->assertIsArray($programs);
        $this->assertEquals('20191004060000 +0000', $programs[0]->attributes()->{'start'});
    }

    public function testSeason_episode()
    {

    }

    public function testCombine_days()
    {
        $parser = new parser();
        $day1 = $parser->files->load_file('natgeo.no', strtotime('2019-10-03'));
        $day2 = $parser->files->load_file('natgeo.no', strtotime('2019-10-04'));
        $day = $parser->combine_days(array($day1, $day2), '20191004');
        $this->assertIsArray($day);
        $this->assertEquals('20191004000000 +0000', $day[0]->attributes()['start']);
    }
}
