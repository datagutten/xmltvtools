<?php

use datagutten\xmltv\tools\data\recording;
use datagutten\xmltv\tools\exceptions\InvalidFileNameException;
use datagutten\xmltv\tools\exceptions\XMLTVException;

require __DIR__.'/../vendor/autoload.php';
try {
    $recording = new recording($file);
    $program = $recording->program_nearest();
}
catch (XMLTVException|FileNotFoundException|InvalidFileNameException $e) {
    echo $e->getMessage()."\n";
}