<?php


namespace datagutten\xmltv\tools\common;


use datagutten\xmltv\tools\exceptions\InvalidXMLFileException;
use Exception;
use FileNotFoundException;
use InvalidArgumentException;
use SimpleXMLElement;
use Symfony\Component\Filesystem\Filesystem;

class files
{
    public $xmltv_path;
    /**
     * @var string Default sub folder
     */
    public $default_sub_folder;
    /**
     * @var array Alternate sub folder
     */
    public $alternate_sub_folders;
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

        if(empty($config['xmltv_alternate_sub_folders']))
            $this->alternate_sub_folders = array();
        else
            $this->alternate_sub_folders = $config['xmltv_alternate_sub_folders'];

        $this->filesystem = new Filesystem();
    }

    function file($channel,$timestamp = null,$sub_folder = null, $extension = 'xml')
    {
        if (!preg_match('/[a-z0-9]+\.[a-z]+/', $channel)) {
            throw new InvalidArgumentException('Invalid channel id: ' . $channel);
        }
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

    /**
     * @param string $channel
     * @param int $timestamp
     * @param string $sub_folder
     * @return SimpleXMLElement
     * @throws FileNotFoundException XML file not found
     * @throws InvalidXMLFileException XML file has no <programme> element
     */
    function load_file($channel, $timestamp = null, $sub_folder = null)
    {
        if(!empty($sub_folder))
            $folders = [$sub_folder];
        else
            $folders = [$this->default_sub_folder] + $this->alternate_sub_folders;

        $file = '';
        foreach ($folders as $sub_folder)
        {
            $file = $this->file($channel, $timestamp, $sub_folder);
            if(file_exists($file))
                break;
        }
        if(!file_exists($file))
            throw new FileNotFoundException($file);
        $xml = simplexml_load_file($file);
        if(empty($xml->programme))
            throw new InvalidXMLFileException('Invalid XML file: '.$file);
        return $xml;
    }
}