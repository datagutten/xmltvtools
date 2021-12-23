<?php

use datagutten\xmltv\tools\data\Recording;
use datagutten\xmltv\tools\exceptions\InvalidFileNameException;
use datagutten\xmltv\tools\exceptions\XMLTVException;

require __DIR__.'/../vendor/autoload.php';
try {
    $recording = new Recording($argv[1]);
    $program = $recording->nearestProgram();
}
catch (XMLTVException|FileNotFoundException|InvalidFileNameException $e) {
    echo $e->getMessage()."\n";
}