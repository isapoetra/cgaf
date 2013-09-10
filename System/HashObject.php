<?php
class HashObject
{
    function __construct($hash)
    {
        Utils::bindToObject($this, $hash, true);
    }
}