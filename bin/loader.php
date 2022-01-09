<?php
function get_autoloader()
{
    foreach (array(__DIR__ . '/../../../autoload.php', __DIR__ . '/../vendor/autoload.php', __DIR__ . '/vendor/autoload.php') as $file)
    {
        if (file_exists($file))
        {
            require $file;
            return;
        }
    }
    throw new RuntimeException('Autoloader not found');
}
get_autoloader();