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
     * @throws FileNotFoundException
     * @throws InvalidXMLFileException
     */
    function testCombine()
    {
        $parser = new parser(__DIR__.'/test_data');
        $day1 = $parser->load_xml_file('natgeo.no', strtotime('2019-10-03'));
        $day2 = $parser->load_xml_file('natgeo.no', strtotime('2019-10-04'));
        $day = $parser->combine_days(array($day1, $day2), '20191004');
        $this->assertIsArray($day);
        $this->assertEquals('20191004000000 +0000', $day[0]->attributes()['start']);
    }
}
