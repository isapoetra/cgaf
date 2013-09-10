<?php
namespace System\Web\Feed;
class RSS2 extends Base
{
    function __construct()
    {
        $this->setValidHeader(array('title', 'link', 'description', 'language', 'pubdate', 'lastBuilddate', 'docs', 'generator' => 'generator', 'managingeditor' => 'managingdirector', 'webMaster' => 'webMaster'));
    }

    function Render($return = false)
    {

        if (!$return) {
            header("Content-Type: application/xml; charset=ISO-8859-1");
        }
        $this->_data ['generator'] = "CGAF RSS Generator";
        $retval = '<?xml version="1.0" encoding="ISO-8859-1" ?>';
        $retval .= '<rss version="2.0">';
        $retval .= '<channel>';
        foreach ($this->_data as $k => $v) {
            if (in_array(strtolower($k), $this->_validHeader)) {
                $retval .= '<' . $k . '>' . $v . '</' . $k . '>';
            }
        }
        $item = array('title', 'link', 'description', 'pubdate', 'guid');
        foreach ($this->_data ['entries'] as $v) {
            $retval .= '<item>';
            foreach ($item as $k) {

                $value = is_object($v) ? $v->$k : $v [$k];
                if ($value) {
                    $retval .= '<' . $k . '>' . $value . '</' . $k . '>';
                }
            }

            $comment = is_object($v) ? $v->comments : (isset ($v ['comments']) ? $v ['comments'] : null);
            if ($comment) {
                $retval .= '<comments>';
                $retval .= '</comments>';
            }
            $retval .= '</item>';

        }
        $retval .= '</channel>';
        $retval .= '</rss>';
        if (!$return) {
            echo $retval;
        }
        return $retval;
    }
}