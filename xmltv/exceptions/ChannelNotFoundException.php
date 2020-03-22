<?php


namespace datagutten\xmltv\tools\exceptions;


use Exception;

class ChannelNotFoundException extends XMLTVException
{
    public function __construct($channel, $code = 0, Exception $previous = null) {
        $message = sprintf('Channel not found: %s', $channel);
        parent::__construct($message, $code, $previous);
    }
}