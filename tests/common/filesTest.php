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
        $this->filesystem->mkdir(__DIR__.'/xmltv_test/natgeo.no');
    }

    public function testMissingPath()
    {
        $this->filesystem->remove(__DIR__.'/xmltv_test');
        $this->expectException(FileNotFoundException::class);
        new files(__DIR__.'/xmltv_test', ['xmltv_test']);
    }

    public function testNoAlternateSubFolder()
    {
        $files = new files(__DIR__.'/xmltv_test', ['xmltv_test']);
        $this->assertSame(1, count($files->sub_folders));
    }

    public function testCreateFolder()
    {
        $files = new files(__DIR__.'/xmltv_test', ['xmltv_test', 'xmltv']);
        $file = $files->file('test.no', strtotime('2019-10-01'), 'php', 'xml', true);
        $dir = dirname($file);
        $this->assertFileExists($dir);
        $this->assertSame(__DIR__.'/xmltv_test/test.no/php/2019', $dir);
    }

    public function testFile()
    {
        $files = new files(__DIR__.'/xmltv_test', ['xmltv_test', 'xmltv']);
        $file = $files->file('natgeo.no', strtotime('2019-10-04'), 'xmltv');
        $path = [__DIR__, 'xmltv_test', 'natgeo.no',
            'xmltv', '2019', 'natgeo.no_2019-10-04.xml'];
        $this->assertEquals(implode(DIRECTORY_SEPARATOR, $path), $file);
    }
    public function testExistingFile()
    {
        $files = new files(__DIR__.'/xmltv_test', ['xmltv_test', 'xmltv']);
        $this->filesystem->mkdir(__DIR__.'/xmltv_test/natgeo.no/xmltv/2019');
        touch(__DIR__.'/xmltv_test/natgeo.no/xmltv/2019/natgeo.no_2019-10-04.xml');
        $file = $files->file('natgeo.no', strtotime('2019-10-04'), 'xmltv');
        $this->assertEquals(realpath(__DIR__.'/xmltv_test/natgeo.no/xmltv/2019/natgeo.no_2019-10-04.xml'), $file);
    }

    public function testDefaultSubFolder()
    {
        $files = new files(__DIR__.'/xmltv_test', ['xmltv_test', 'xmltv']);
        $file = $files->file('natgeo.no', strtotime('2019-10-04'));
        $this->assertEquals(__DIR__.'/xmltv_test/natgeo.no/xmltv_test/2019/natgeo.no_2019-10-04.xml', $file);
    }

    public function testDefaultTimestamp()
    {
        $files = new files(__DIR__.'/xmltv_test', ['xmltv_test', 'xmltv']);
        $file = $files->file('natgeo.no');
        $timestamp = strtotime('midnight');
        $this->assertEquals(sprintf(__DIR__.'/xmltv_test/natgeo.no/xmltv_test/%s/natgeo.no_%s.xml', date('Y', $timestamp), date('Y-m-d', $timestamp)), $file);
    }

    public function testInvalidChannelId()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid channel id: foobar');
        $files = new files(__DIR__.'/xmltv_test', ['xmltv_test', 'xmltv']);
        $files->file('foobar');
    }

    public function testInvalidXML()
    {
        $files = new files(__DIR__.'/xmltv_test', ['xmltv_test', 'xmltv']);
        $file = $files->file('test.no', strtotime('2019-10-01'), 'php', 'xml', true);
        $xml = new SimpleXMLElement('<xmltv></xmltv>');
        $xml->asXML($file);

        $this->expectException(InvalidXMLFileException::class);
        $this->expectExceptionMessage('Invalid XML file: '.realpath($file));
        $files->load_file('test.no', strtotime('2019-10-01'), 'php');
    }

    public function testEmptyXML()
    {
        $files = new files(__DIR__.'/xmltv_test', ['xmltv_test', 'xmltv']);
        $file = $files->file('test.no', strtotime('2019-10-01'), 'php', 'xml', true);
        touch($file);
        $this->expectExceptionMessage('Document is empty');
        $this->expectException(InvalidXMLFileException::class);
        $files->load_file('test.no', strtotime('2019-10-01'), 'php');
    }

    public function tearDown(): void
    {
        $this->filesystem->remove(__DIR__.'/xmltv_test');
    }
}
