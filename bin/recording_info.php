<?php

use datagutten\dreambox\recording_info;

require __DIR__.'/../vendor/autoload.php';
$info = new recording_info;
print_r($info->recording_info($argv[1]));