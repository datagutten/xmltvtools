<?php

namespace datagutten\xmltv\tests\tools\common;

use datagutten\xmltv\tools\common\files;
use PHPUnit\Framework\TestCase;

class filesTest extends TestCase
{
    public function testFile()
    {
        $files = new files();
        $file = $files->file('natgeo.no', strtotime('2019-10-04'), 'xmltv');
        $this->assertEquals(__DIR__.'/xmltv_test/natgeo.no/xmltv/2019/natgeo.no_2019-10-04.xml', $file);
    }
}
