<?php

namespace datagutten\xmltv\tests\tools\common;

use datagutten\xmltv\tools\common\files;
use FileNotFoundException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class filesTest extends TestCase
{
    /**
     * @var Filesystem
     */
    public $filesystem;
    public function setUp(): void
    {
        $this->filesystem = new Filesystem();
        //copy(__DIR__.'/test_config.php', __DIR__.'/config.php');
        $config = file_get_contents(__DIR__.'/test_config.php');
        $config = str_replace('__DIR__', __DIR__, $config);
        file_put_contents(__DIR__.'/config.php', $config);
        set_include_path(__DIR__);
        mkdir(__DIR__.'/xmltv_test');
    }

    public function testInvalidConfig1()
    {
        $config = file_get_contents(__DIR__.'/config.php');
        $config = str_replace('xmltv_path', 'xmltv_path_bad', $config);
        file_put_contents(__DIR__.'/config.php', $config);
        $this->expectExceptionMessage('xmltv_path not set in config');
        new files();
    }

    public function testInvalidConfig2()
    {
        $config = file_get_contents(__DIR__.'/config.php');
        $config = str_replace('xmltv_default_sub_folder', 'xmltv_default_sub_folder_bad', $config);
        file_put_contents(__DIR__.'/config.php', $config);
        $this->expectExceptionMessage('xmltv_default_sub_folder not set in config');
        new files();
    }

    public function testMissingPath()
    {
        /*$config = file_get_contents(__DIR__.'/config.php');
        $config = str_replace('xmltv_default_sub_folder', 'xmltv_default_sub_folder_Bad', $config);
        file_put_contents(__DIR__.'/config.php', $config);*/
        rmdir(__DIR__.'/xmltv_test');
        $this->expectException(FileNotFoundException::class);
        new files();
    }

    public function testFile()
    {
        $files = new files();
        $file = $files->file('natgeo.no', strtotime('2019-10-04'), 'xmltv');
        $this->assertEquals(__DIR__.'/xmltv_test/natgeo.no/xmltv/2019/natgeo.no_2019-10-04.xml', $file);
    }
    public function testExistingFile()
    {
        $files = new files();
        $this->filesystem->mkdir(__DIR__.'/xmltv_test/natgeo.no/xmltv/2019');
        touch(__DIR__.'/xmltv_test/natgeo.no/xmltv/2019/natgeo.no_2019-10-04.xml');
        $file = $files->file('natgeo.no', strtotime('2019-10-04'), 'xmltv');
        $this->assertEquals(realpath(__DIR__.'/xmltv_test/natgeo.no/xmltv/2019/natgeo.no_2019-10-04.xml'), $file);
    }

    public function testDefaultSubFolder()
    {
        $files = new files();
        $file = $files->file('natgeo.no', strtotime('2019-10-04'));
        $this->assertEquals(__DIR__.'/xmltv_test/natgeo.no/xmltv_test/2019/natgeo.no_2019-10-04.xml', $file);
    }

    public function testDefaultTimestamp()
    {
        $files = new files();
        $file = $files->file('natgeo.no');
        $timestamp = strtotime('midnight');
        $this->assertEquals(sprintf(__DIR__.'/xmltv_test/natgeo.no/xmltv_test/%s/natgeo.no_%s.xml', date('Y', $timestamp), date('Y-m-d', $timestamp)), $file);
    }

    public function tearDown(): void
    {
        $this->filesystem->remove(__DIR__.'/xmltv_test');
        unlink(__DIR__.'/config.php');
    }
}
