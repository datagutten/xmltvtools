<?php /** @noinspection SpellCheckingInspection */

namespace datagutten\xmltv\tests\tools\xmltv\data;

use datagutten\tools\files\files;
use datagutten\video_tools\exceptions;
use datagutten\xmltv\tools\data\RecordingFile;
use DependencyFailedException;
use FileNotFoundException;
use PHPUnit\Framework\TestCase;

class recording_fileTest extends TestCase
{
    function setUp(): void
    {
        if (!class_exists('datagutten\video_tools\video')) {
            $this->markTestSkipped(
                'video class not found, video-tools not installed.'
            );
        }
    }

    /**
     * @throws DependencyFailedException
     * @throws FileNotFoundException
     * @throws exceptions\DurationNotFoundException
     */
    public function testGet_duration()
    {
        $test_file = files::path_join(__DIR__, '..', 'test_data', 'Reklame Kornmo Treider 41.mp4' );
        $file = new RecordingFile($test_file);
        $duration = $file->getDuration();
        $this->assertEquals(49, $duration);
    }

    /**
     * @throws DependencyFailedException
     * @throws FileNotFoundException
     * @throws exceptions\DurationNotFoundException
     */
    public function testDuration_hms()
    {
        $test_file = files::path_join(__DIR__, '..', 'test_data', 'Reklame Kornmo Treider 41.mp4' );
        $file = new RecordingFile($test_file);
        $this->assertEquals('00:00:49', $file->durationHMS());
    }

    /**
     * @throws DependencyFailedException
     * @throws FileNotFoundException
     * @throws exceptions\DurationNotFoundException
     * @requires PHPUnit 9.1
     */
    public function testGet_duration_cache()
    {
        $test_file = files::path_join(__DIR__, '..', 'test_data', 'Reklame Kornmo Treider 41.mp4' );
        $cache_file = $test_file.'.duration';

        $file = new RecordingFile($test_file);
        $this->assertFileDoesNotExist($cache_file);
        $duration = $file->duration();
        $this->assertEquals(49, $duration);

        // Duration in cache
        $this->assertFileExists($cache_file);
        $duration = $file->duration();
        $this->assertEquals(49, $duration);

        // Bad cache
        file_put_contents($cache_file, '');
        $duration = $file->duration();
        $this->assertEquals(49, $duration);
    }

    /**
     * @throws FileNotFoundException
     */
    public function testBasename()
    {
        $test_file = files::path_join(__DIR__, '..', 'test_data', 'Reklame Kornmo Treider 41.mp4' );
        $file = new RecordingFile($test_file);
        $this->assertSame('Reklame Kornmo Treider 41.mp4', $file->basename());
    }

    public function tearDown(): void
    {
        $cache_file = files::path_join(__DIR__, '..', 'test_data', 'Reklame Kornmo Treider 41.mp4.duration' );
        if(file_exists($cache_file))
            unlink($cache_file);
    }
}
