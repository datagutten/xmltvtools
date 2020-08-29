<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace datagutten\xmltv\tests\tools\xmltv\data;


use datagutten\tools\files\files;
use datagutten\xmltv\tools\data\program;
use datagutten\xmltv\tools\data\recording;
use datagutten\xmltv\tools\exceptions\InvalidFileNameException;
use FileNotFoundException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

date_default_timezone_set('Europe/Oslo');
class recordingTest extends TestCase
{
    function setUp(): void
    {
        if (!class_exists('datagutten\video_tools\video')) {
            $this->markTestSkipped(
                'video class not found, video-tools not installed.'
            );
        }
    }
    public function testFileNotFound()
    {
        $this->expectException(FileNotFoundException::class);
        new recording('z:/opptak/Phineas and Ferb/Phineas og Ferb S01E29 - Boyfriend from 27,000 B.C.ts');
    }

    public function testInvalidFileName()
    {
        $test_file = files::path_join(__DIR__, '..', 'test_data', 'Reklame Kornmo Treider 41.mp4' );
        $this->expectException(InvalidFileNameException::class);
        new recording($test_file);
    }

    public function testIgnoreInvalidFileName()
    {
        $test_file = files::path_join(__DIR__, '..', 'test_data', 'Reklame Kornmo Treider 41.mp4' );
        $recording = new recording($test_file, '', '', true);
        $this->assertEmpty($recording->channel_name);
        $this->assertEmpty($recording->eit);
    }

    public function testPrograms()
    {
        $test_file = files::path_join(__DIR__, '..', 'test_data', '20200605 0855 - Disney XD (N) - Phineas og Ferb x4.ts' );
        $xmltv_path = files::path_join(__DIR__, '..', 'parse', 'test_data');
        $recording = new recording($test_file, $xmltv_path);
        $programs = $recording->programs();
        $this->assertIsArray($programs);
    }
    public function testNearest()
    {
        $test_file = files::path_join(__DIR__, '..', 'test_data', '20200605 0855 - Disney XD (N) - Phineas og Ferb x4.ts' );
        $xmltv_path = files::path_join(__DIR__, '..', 'parse', 'test_data');
        $recording = new recording($test_file, $xmltv_path);
        $program = $recording->program_nearest();
        $this->assertSame(1591340400, $program->start_timestamp);
        $this->assertSame('Phineas og Ferb', $program->title);
    }

    public function testEitInfo()
    {
        $test_file = files::path_join(__DIR__, '..', 'test_data', '20200605 0855 - Disney XD (N) - Phineas og Ferb x4.ts' );
        $recording = new recording($test_file);
        $eit = $recording->eit_info();
        $this->assertInstanceOf(program::class, $eit);
    }

    public function testNoXMLTV()
    {
        $test_file = files::path_join(__DIR__, '..', 'test_data', '20200605 0855 - Disney XD (N) - Phineas og Ferb x4.ts' );
        $this->expectException(InvalidArgumentException::class);
        $recording = new recording($test_file);
        $recording->programs();
    }
    public function testNoXMLTVNearest()
    {
        $test_file = files::path_join(__DIR__, '..', 'test_data', '20200605 0855 - Disney XD (N) - Phineas og Ferb x4.ts' );
        $this->expectException(InvalidArgumentException::class);
        $recording = new recording($test_file);
        $recording->program_nearest();
    }

    public function testTime()
    {
        $test_file = files::path_join(__DIR__, '..', 'test_data', '20200605 0855 - Disney XD (N) - Phineas og Ferb x4.ts' );
        $recording = new recording($test_file);
        $time = $recording->time();
        $this->assertSame($time, '08:55-08:55');
    }

    public function testEitTime()
    {
        $test_file = files::path_join(__DIR__, '..', 'test_data', '20200605 0855 - Disney XD (N) - Phineas og Ferb x4.ts' );
        $recording = new recording($test_file);
        $time = $recording->eit_time();
        $this->assertSame($time, '10:00-10:29');
    }

    public function testDurationInvalidFile()
    {
        $this->expectError();
        $test_file = files::path_join(__DIR__, '..', 'test_data', '20200605 0855 - Disney XD (N) - Phineas og Ferb x4.eit' );
        $this->expectErrorMessageMatches('/Unable to get duration:.+/');
        new recording($test_file);
    }
}
