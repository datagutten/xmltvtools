<?php

namespace datagutten\xmltv\tests\tools\common;

use datagutten\xmltv\tools\common\files;
use PHPUnit\Framework\TestCase;

class filesTest extends TestCase
{
    public function setUp(): void
    {
        //copy(__DIR__.'/test_config.php', __DIR__.'/config.php');
        $config = file_get_contents(__DIR__.'/test_config.php');
        $config = str_replace('__DIR__', __DIR__, $config);
        file_put_contents(__DIR__.'/config.php', $config);
    }

    public function testFile()
    {
        $files = new files();
        $file = $files->file('natgeo.no', strtotime('2019-10-04'), 'xmltv');
        $this->assertEquals(__DIR__.'/xmltv_test/natgeo.no/xmltv/2019/natgeo.no_2019-10-04.xml', $file);
    }

    public function tearDown(): void
    {
        unlink(__DIR__.'/config.php');
    }
}
