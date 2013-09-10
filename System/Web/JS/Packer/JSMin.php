<?php
namespace System\Web\JS\Packer;

use System\Web\JS\Packer\Engine\JSMin as JSMinEngine;

class JSMin implements IScriptPacker
{
    private $_input;

    function __construct($script = null)
    {
        $this->_input = $script;
    }

    /* (non-PHPdoc)
     * @see IScriptPacker::setScript()
     */
    public function setScript($script)
    {
        $this->_input = str_replace("\r\n", "\n", $script);
    }

    public function pack()
    {
        if (!$this->_input) {
            return null;
        }
        return JSMinEngine::minify($this->_input);
    }
}

?>