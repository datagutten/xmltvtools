<?php


namespace datagutten\xmltv\tools\data;

use datagutten\video_tools\exceptions as video_exceptions;
use datagutten\video_tools\video;
use DependencyFailedException;
use FileNotFoundException;
use RuntimeException;

class RecordingFile extends BaseElement
{
    /**
     * @var string Full path to recording file
     */
    public $file;

    /**
     * @var array Result from pathinfo()
     */
    public $pathinfo;



    /**
     * recording_file constructor.
     * @param $file
     * @throws FileNotFoundException
     */
    public function __construct($file)
    {
        // @codeCoverageIgnoreStart
        if(!class_exists('datagutten\video_tools\video'))
            throw new RuntimeException('video class not found, video-tools not installed');
        // @codeCoverageIgnoreEnd
        if(!file_exists($file))
            throw new FileNotFoundException($file);
        $this->file = $file;
        $this->pathinfo = pathinfo($file);
    }

    public function basename()
    {
        return $this->pathinfo['basename'];
    }

    /**
     * Get recording duration
     * Requires package datagutten/video-tools
     * @return int Duration
     * @throws video_exceptions\DurationNotFoundException
     * @throws DependencyFailedException
     */
    public function getDuration()
    {
        return video::duration($this->file);
    }

    /**
     * @return int
     * @throws video_exceptions\DurationNotFoundException
     * @throws DependencyFailedException
     */
    public function duration(): int
    {
        $file = $this->file . '.duration';
        if (!file_exists($file)) {
            $duration = $this->getDuration();
            file_put_contents($file, $duration);
        } else {
            $duration = intval(file_get_contents($file));
            if (empty($duration)) {
                unlink($file);
                return $this->duration();
            }
        }
        $this->setDuration($duration);
        return $duration;
    }

    /**
     * Get duration as a hours:minutes:seconds string
     * @throws video_exceptions\DurationNotFoundException|DependencyFailedException
     */
    public function durationHMS()
    {
        return video::seconds_to_time($this->duration());
    }
}
