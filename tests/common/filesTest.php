<?php

namespace datagutten\xmltv\tests\tools\common;

use datagutten\xmltv\tools\common\files;
use datagutten\xmltv\tools\exceptions\InvalidXMLFileException;
use FileNotFoundException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
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
        rmdir(__DIR__.'/xmltv_test');
        $this->expectException(FileNotFoundException::class);
        new files();
    }

    public function testNoAlternateSubFolder()
    {
        $config = file_get_contents(__DIR__.'/config.php');
        $config = str_replace('xmltv_alternate_sub_folders', 'no_xmltv_alternate_sub_folders', $config);
        file_put_contents(__DIR__.'/config.php', $config);
        $files = new files();
        $this->assertEmpty($files->alternate_sub_folders);
    }

    public function testCreateFolder()
    {
        $files = new files;
        $file = $files->file('test.no', strtotime('2019-10-01'), 'php', 'xml', true);
        $dir = dirname($file);
        $this->assertFileExists($dir);
        $this->assertSame(__DIR__.'/xmltv_test/test.no/php/2019', $dir);
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

    public function testInvalidChannelId()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid channel id: foobar');
        $files = new files;
        $files->file('foobar');
    }

    public function testInvalidXML()
    {
        $files = new files;
        $file = $files->file('test.no', strtotime('2019-10-01'), 'php', 'xml', true);
        $xml = new SimpleXMLElement('<xmltv></xmltv>');
        $xml->asXML($file);

        $this->expectException(InvalidXMLFileException::class);
        $this->expectExceptionMessage('Invalid XML file: '.realpath($file));
        $files->load_file('test.no', strtotime('2019-10-01'), 'php');
    }

    public function testEmptyXML()
    {
        $files = new files;
        $file = $files->file('test.no', strtotime('2019-10-01'), 'php', 'xml', true);
        touch($file);
        $this->expectExceptionMessage('Document is empty');
        $this->expectException(InvalidXMLFileException::class);
        $files->load_file('test.no', strtotime('2019-10-01'), 'php');
    }

    public function tearDown(): void
    {
        $this->filesystem->remove(__DIR__.'/xmltv_test');
        unlink(__DIR__.'/config.php');
    }
}
