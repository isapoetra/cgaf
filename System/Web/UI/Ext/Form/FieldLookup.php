<?php
namespace System\Web\UI\Ext\Form;
class FieldLookup extends Field
{

    function __construct($name, $label, $value, $configs = null)
    {
        parent::__construct('lookupfield', $name, $label, $value, $configs);
        $this->_controlScript = array(
            "id" => "lookup_field",
            "url" => \CGAF::findLiveFile("Lookup.js", "js")
        );
    }
}