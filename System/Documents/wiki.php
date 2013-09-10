<?php
namespace System\Documents;

use System\Parsers\Wiki as WikiParser;

class Wiki implements IDocument
{
    private $_parser;
    private $_file;

    function __construct()
    {
        $this->_parser = new WikiParser();
    }

    function parseString($s)
    {
        return $this->_parser->parseString($s);
    }

    function loadFile($f)
    {
        $this->_file = $f;
    }

    function Render($return = false)
    {
        if ($this->_file) {
            return $this->_parser->parseFile($this->_file);
        }
    }
}

?>