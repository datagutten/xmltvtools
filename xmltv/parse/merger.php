<?php


namespace datagutten\xmltv\tools\parse;

/**
 * Merge information from different xmltv files
 * @package datagutten\xmltv\tools\parse
 */
class merger extends parser
{
    public $parsers = [];
    function __construct($xmltv_path, $sub_folders)
    {
        parent::__construct(['xmltv_path'=>$xmltv_path, 'xmltv_default_sub_folder'=>$sub_folders[0]]);
        foreach (array_slice($sub_folders, 1) as $folder)
        {
            $this->parsers[] = new parser(['xmltv_path'=>$xmltv_path, 'xmltv_default_sub_folder'=>$folder]);
        }
    }

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
