<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace datagutten\xmltv\tests\tools\xmltv\data;


use datagutten\tools\files\files;
use datagutten\xmltv\tools\data\Program;
use datagutten\xmltv\tools\data\Recording;
use datagutten\xmltv\tools\exceptions\InvalidFileNameException;
use datagutten\xmltv\tools\exceptions\ProgramNotFoundException;
use FileNotFoundException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

date_default_timezone_set('Europe/Oslo');
class recordingTest extends TestCase
{
    /**
     * @var string
     */
    private $xmltv_path;

    function setUp(): void
    {
        if (!class_exists('datagutten\video_tools\video')) {
            $this->markTestSkipped(
                'video class not found, video-tools not installed.'
            );
        }
        $this->xmltv_path = files::path_join(__DIR__, '..', 'parse', 'test_data');
    }
    public function testFileNotFound()
    {
        $this->expectException(FileNotFoundException::class);
        new Recording('z:/opptak/Phineas and Ferb/Phineas og Ferb S01E29 - Boyfriend from 27,000 B.C.ts');
    }

    public function testInvalidFileName()
    {
        $test_file = files::path_join(__DIR__, '..', 'test_data', 'Reklame Kornmo Treider 41.mp4' );
        $this->expectException(InvalidFileNameException::class);
        new Recording($test_file);
    }

    public function testIgnoreInvalidFileName()
    {
        $test_file = files::path_join(__DIR__, '..', 'test_data', 'Reklame Kornmo Treider 41.mp4' );
        $recording = new Recording($test_file, '', '', true, false);
        $this->assertEmpty($recording->channel_name);
        $this->assertEmpty($recording->eit);
    }

    public function testPrograms()
    {
        $test_file = files::path_join(__DIR__, '..', 'test_data', '20200605 0855 - Disney XD (N) - Phineas og Ferb x4.ts' );
        $xmltv_path = files::path_join(__DIR__, '..', 'parse', 'test_data');
        $recording = new Recording($test_file, $xmltv_path);
        $programs = $recording->programs();
        $this->assertIsArray($programs);
    }
    public function testNearest()
    {
        $test_file = files::path_join(__DIR__, '..', 'test_data', '20200605 0855 - Disney XD (N) - Phineas og Ferb x4.ts' );
        $xmltv_path = files::path_join(__DIR__, '..', 'parse', 'test_data');
        $recording = new Recording($test_file, $xmltv_path);
        $program = $recording->nearestProgram();
        $this->assertSame(1591340400, $program->start_timestamp);
        $this->assertSame('Phineas og Ferb', $program->title);
    }

    public function testEitInfo()
    {
        $test_file = files::path_join(__DIR__, '..', 'test_data', '20200605 0855 - Disney XD (N) - Phineas og Ferb x4.ts' );
        $recording = new Recording($test_file);
        $eit = $recording->eitInfo();
        $this->assertInstanceOf(Program::class, $eit);
    }

    public function testNoXMLTV()
    {
        $test_file = files::path_join(__DIR__, '..', 'test_data', '20200605 0855 - Disney XD (N) - Phineas og Ferb x4.ts' );
        $this->expectException(InvalidArgumentException::class);
        $recording = new Recording($test_file);
        $recording->programs();
    }
    public function testNoXMLTVNearest()
    {
        $test_file = files::path_join(__DIR__, '..', 'test_data', '20200605 0855 - Disney XD (N) - Phineas og Ferb x4.ts' );
        $this->expectException(InvalidArgumentException::class);
        $recording = new Recording($test_file);
        $recording->nearestProgram();
    }

    public function testTime()
    {
        $test_file = files::path_join(__DIR__, '..', 'test_data', '20200605 0855 - Disney XD (N) - Phineas og Ferb x4.ts' );
        $recording = new Recording($test_file);
        $time = $recording->time();
        $this->assertSame($time, '08:55-08:55');
    }

    public function testEitTime()
    {
        $test_file = files::path_join(__DIR__, '..', 'test_data', '20200605 0855 - Disney XD (N) - Phineas og Ferb x4.ts' );
        $recording = new Recording($test_file);
        $time = $recording->eitTime();
        $this->assertSame($time, '10:00-10:29');
    }

    public function testDurationInvalidFile()
    {
        $this->expectError();
        $test_file = files::path_join(__DIR__, '..', 'test_data', '20200605 0855 - Disney XD (N) - Phineas og Ferb x4.eit' );
        $this->expectErrorMessageMatches('/Unable to get duration:.+/');
        new Recording($test_file);
    }

    public function testInvalidEndTime()
    {
        $test_file = files::path_join(__DIR__, '..', 'test_data', '20210328 1030 - MAX HD - Grensevakten x10.ts');
        $recording = new Recording($test_file, $this->xmltv_path, ['xmltv_quad']);
        $this->expectException(ProgramNotFoundException::class);
        $this->expectExceptionMessage('Invalid end time');
        $recording->programs();
    }

    public function testLoop()
    {
        $test_file = files::path_join(__DIR__, '..', 'test_data',
            '20211220 1555 - Nickelodeon - Rugrats (2021) (R) - (6) Amerikansk animasjonsserie fra 2021..ts');
        $recording = new Recording($test_file, $this->xmltv_path, ['xmltv_php'], false, false);
        $programs = $recording->programs();
        $this->assertIsArray($programs);
        $this->assertCount(1, $programs);
    }
}
