<?php
namespace System\MVC;

//TODO Smarter load row
use System\MVC\Model;

class LocaleFriendlyModel extends Model
{
    private $_localeField;

    function __construct($connection = null, $tableName, $pk, $localeField, $includeAppId = false)
    {
        parent::__construct($connection, $tableName, $pk, $includeAppId);
        $this->_localeField = $localeField;
    }

    function getLocalizeValue($locale = null, $field = null)
    {
        $field = $field ? $field : $this->_localeField;
        return __fromObject($field, $this, $locale);
    }
}
