<?php


namespace datagutten\xmltv\tools\parse;

use datagutten\xmltv\tools\exceptions\XMLTVException;

/**
 * Merge information from different xmltv files
 * @package datagutten\xmltv\tools\parse
 */
class merger extends parser
{
    public $parsers = [];

    public function __construct($xmltv_path, $sub_folders)
    {
        parent::__construct($xmltv_path, $sub_folders);
        foreach (array_slice($sub_folders, 1) as $folder)
        {
            $this->parsers[] = new parser($xmltv_path, [$folder]);
        }
    }

    public function find_program(int $search_time, string $channel, $mode='nearest', bool $multiple_days = false)
    {
        $base_program = parent::find_program($search_time, $channel, $mode);
        $base_keys = array_keys((array)$base_program);
        /**
         * @var parser $parser
         */
        foreach ($this->parsers as $parser)
        {
            try {
                $program = $parser->find_program($search_time, $channel, $mode);
                $keys = array_keys((array)$program);
                $diff = array_diff($keys, $base_keys);
                foreach ($diff as $field) {
                    $base_program->$field = $program->$field;
                }
            }
            catch (XMLTVException $e)
            {
                continue;
            }
        }
        return $base_program;
    }
}
