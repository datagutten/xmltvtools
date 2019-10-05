<?php


namespace datagutten\xmltv\tools\common;


use Exception;
use FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;

class files
{
    public $xmltv_path;
    public $default_sub_folder;
    /**
     * @var Filesystem
     */
    public $filesystem;
    /**
     * files constructor.
     * @throws Exception
     * @throws FileNotFoundException
     */
    function __construct()
    {
        $config = require 'config.php';
        if(empty($config['xmltv_path']))
            throw new Exception('xmltv_path not set in config');
        $this->xmltv_path = $config['xmltv_path'];

        if(!file_exists($this->xmltv_path))
            throw new FileNotFoundException($this->xmltv_path);

        if(empty($config['xmltv_default_sub_folder']))
            throw new Exception('xmltv_default_sub_folder not set in config');
        $this->default_sub_folder = $config['xmltv_default_sub_folder'];

        $this->filesystem = new Filesystem();
    }

    function file($channel,$timestamp = null,$sub_folder = null, $extension = 'xml')
    {
        if(empty($timestamp))
            $timestamp=strtotime('midnight');
        if(empty($sub_folder))
            $sub_folder = $this->default_sub_folder;

        $folder = $this->xmltv_path.'/'.filename::folder($channel, $sub_folder, $timestamp);
        $this->filesystem->mkdir($folder);
        $file = $folder.'/'.filename::filename($channel, $timestamp, $extension);
        if(file_exists($file))
            return realpath($file);
        else
            return $file;
    }
}