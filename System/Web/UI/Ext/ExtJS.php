<?php
namespace System\Web\UI\Ext;

use Response;
use System\JSON\JSON;
use System\Web\JS\JSUtils;
use Utils;

class ExtJS extends Component
{
    protected $_js;
    protected $_quotestr;

    function __construct($js, $quotestr = false)
    {
        if (is_array($js)) {
            $js = "{" . JSON::encode($js) . "}";
        }
        $this->_quotestr = $quotestr;
        $this->id = Utils::generateId($this->_prefix);
        $this->_js = $js;
    }

    function render($return = false, &$handle = false)
    {
        if ($this->_quotestr) {
            $this->_js = "'" . JSUtils::addSlash($this->_js) . "'";
        }
        if (!$return) {
            Response::Write($this->_js);
        }
        $handle = true;
        return $this->_js;
    }
}
