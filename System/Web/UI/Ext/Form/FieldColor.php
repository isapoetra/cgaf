<?php
namespace System\Web\UI\Ext\Form;
class FieldColor extends Field
{

    function __construct($id, $title, $value, $configs = null)
    {
        parent::__construct("gcolorcombo", $id, $title, $value, $configs);
    }
}