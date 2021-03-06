<?php
namespace System\Web;

use System\AbstractResponse;
use System\JSON\JSONResult;
use System\Session\Session;
use System\Web\JS\JSUtils;
use Utils;

class Response extends AbstractResponse
{
    private $_flush;

    function __construct()
    {
        parent::__construct(true);
    }

    function Init()
    {
        parent::Init();
    }

    private function hasSent($header)
    {
        $list = headers_list();

        foreach ($list as $l) {
            if (substr(strtolower($l), 0, strlen($header)) === strtolower($header)) {
                return true;
            }
        }
        return false;
    }

    function flush()
    {
        if ($this->_flushed)
            return null;
        if (!$this->hasSent('Content-Type')) {
            @header("Content-Type:text/html; charset=UTF-8");
        }
        return parent::flush();
    }

    function Write($s, $attr = null)
    {
        if ($s === null) {
            return null;
        }
        if ($attr !== null) {
            $attr = new \Attribute($attr);
            $attr->addIgnore("__tag");
            $tag = $attr->get("__tag");
            $s = isset($tag) ? "<$tag " . $attr->Render(true) . ">$s</$tag>\n" : $s;
        }
        return parent::write($s);
    }

    function forceContentExpires()
    {
        header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
    }

    function Redirect($url = null)
    {
        $url = $url ? $url : BASE_URL;
        $this->forceContentExpires();
        $this->clearBuffer();
        if (\Request::isJSONRequest()) {
            $r = new JSONResult(true, '', $url);
            $this->write($r->render(true));
        } elseif (\Request::isAJAXRequest()) {
            echo '<noscript><div class="redirect"><a href="' . $url . '">click here to continue</a></div></noscript>';
            echo JSUtils::renderJSTag('document.location="' . $url . '";', false);
        } else {
            header("Location: $url");
        }
        exit();
    }
}

?>
