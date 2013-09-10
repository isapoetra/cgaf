<?php

class GUID
{
    protected $guidText;
    protected $separator;
    protected $textcase;

    function __construct($separator = "-", $case = "uc")
    {
        $this->guidText = md5(uniqid(rand(), true));
        $this->separator = $separator;
        $this->textcase = $case;
        return $this->guidText;
    }

    function toString()
    {
        $str = $this->guidText;
        switch ($this->textcase) {
            case 'uc':
                $str = strtoupper($str);
                break;
            case 'lc':
                $str = strtolower($str);
                break;
            //default:
            //$str = $str;
        }
        $str = substr($str, 0, 8) . $this->separator . substr($str, 8, 4) . $this->separator . substr($str, 12, 4) . $this->separator . substr($str, 16, 4) . $this->separator . substr($str, 20);
        return htmlspecialchars($str);
    }

    public static function getGUID($asstring = true)
    {
        $guid = new GUID();
        if ($asstring) {
            return $guid->toString();
        } else {
            return $guid;
        }
    }

    public static function isValid($guid)
    {
        if (empty($guid)) {
            return false;
        }
        if ($guid instanceof GUID) {
            return true;
        }
        return true;
    }
}

?>