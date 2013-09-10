<?php
namespace System\Web\JS\Packer;

using('libs.minifier.minify.min.lib.JSMinPlus');
class JSMinPlusPacker extends \JSMinPlus implements IScriptPacker
{
    function __construct()
    {
        parent::__construct('');
    }

    /* (non-PHPdoc)
     * @see IJSMinifier::setScript()
     */
    public function setScript($script)
    {
        $this->input = str_replace("\r\n", "\n", $script);
        $this->inputLength = strlen($this->input);
    }

    public function pack()
    {
        return $this->min();
    }
}

?>