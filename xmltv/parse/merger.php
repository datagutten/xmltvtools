<?php


namespace datagutten\xmltv\tools\parse;

use datagutten\xmltv\tools\exceptions\ProgramNotFoundException;
use FileNotFoundException;
use SimpleXMLElement;

/**
 * Merge information from different xmltv files
 * @package datagutten\xmltv\tools\parse
 */
class merger extends parser
{
    public $parsers = [];

    /**
     * merger constructor.
     * @param string $xmltv_path XMLTV root path
     * @param array $sub_folders Sub folders of each channel to load data from
     * @throws FileNotFoundException XMLTV path not found
     */
    function __construct($xmltv_path, $sub_folders)
    {
        parent::__construct($xmltv_path, $sub_folders);
        foreach (array_slice($sub_folders, 1) as $folder)
        {
            $this->parsers[] = new parser($xmltv_path, [$folder]);
        }
    }

    /**
     * @param int $search_time Time to search
     * @param string $programs_xml_or_channel
     * @param string $mode
     * @return SimpleXMLElement
     * @throws ProgramNotFoundException Program not found
     */
    function find_program($search_time,$programs_xml_or_channel,$mode='nearest')
    {
        $base_program = parent::find_program($search_time,$programs_xml_or_channel,$mode);
        $base_keys = array_keys((array)$base_program);
        /**
         * @var parser $parser
         */
        foreach ($this->parsers as $parser)
        {
            $program = $parser->find_program($search_time,$programs_xml_or_channel,$mode);
            $keys = array_keys((array)$program);
            $diff = array_diff($keys, $base_keys);
            foreach($diff as $field)
            {
                $base_program->$field = $program->$field;
            }
        }
        return $base_program;
    }
}