<?php
namespace System\Web\UI\JQ;

use Request;
use System\JSON\JSON;
use System\Web\JS\CGAFJS;
use System\Web\JS\JSFunc;

class JSTree extends TreeView
{
    function __construct($id, $url = null)
    {
        parent::__construct($id, $url);

    }

    protected function parseModelRows($rows, $loadChild = false)
    {
        $retval = array();

        foreach ($rows as $row) {
            // ppd($row);
            $child = array();
            if ($loadChild) {
                $child = $this->loadAll($row->id, $loadChild);
            }
            $r = new \stdClass ();
            // $r->id = $row->id;
            $row->text = $this->_baseLocale ? __($this->_baseLocale . '.' . $row->text, $row->text) : $row->text;
            /*
             * $r->data = new \stdclass(); $r->data->id = $row->id;
             */
            $r->li_attr = new \stdClass ();
            $r->li_attr->state = 'closed';
            if ($row->childs) {
                $r->attr->role = 'folder';
                $r->attr->state = '';
            }
            $r->li_attr->id = $row->id;
            $r->title = $this->parseText($this->_nodeText ? $this->_nodeText : $row->text, $row);
            if ($loadChild) {
                $r->children = $child;
            } else {
                $r->children = $row->childs > 0 ? true : false;
            }

            $retval [] = $r;
        }
        return $retval;
    }

    public function Render($return = false)
    {
        if (Request::isDataRequest()) {
            return $this->renderData();
        }
        CGAFJS::loadPlugin('jstree/jquery.jstree', true);
        $retval = null;
        $id = $this->getId();
        $parent = $this->getConfig('renderTo', 'body');
        $this->removeConfig('renderTo');
        if ($this->_asyncMode) {
            $df = <<< EOT
function(n) {
	console.log(arguments);
	var id = 0;
	if (typeof(n)=="object") {
		id = $(n).attr('id');
	}
	return {"root" : id};
}
EOT;
            $this->setConfig('json', array(
                'ajax' => array(
                    'url' => $this->_url,
                    'data' => new JSFunc ($df)
                )
            ));
        }
        $this->setConfig('plugins', array(
            'themes',
            'json',
            'ui'
        ));
        $configs = JSON::encodeConfig($this->_configs);
        $js = '';
        if (!$return) {
            $c = urlencode("<div id=\"$id\"></div>");
            $js = <<<EOT
			if ($('#$id').length ===0) {
				$(decodeURIComponent('$c').replace('+',' ')).appendTo('$parent');
			}
EOT;
        } else {
            $retval = "<div id=\"$id\"  style=\"min-height:200px;\"></div>";
        }
        $js .= '$.jstree.THEMES_DIR=\'' . ASSET_URL . 'js/jQuery/plugins/jstree/themes/\';';
        $js .= '$(\'#' . $this->getId() . '\')';
        $js .= '.delegate("a", "click", function (event, data) {	event.preventDefault();})';
        // $js .= '.bind("before.jstree", function (e, data) {
        // console.log(data);})';
        $js .= '.jstree(' . $configs . ')';
        // ppd($this->_events);
        if ($this->_events) {
            foreach ($this->_events as $e => $a) {

                foreach ($a as $ev) {
                    $js .= '.bind("' . $e . '.jstree",';
                    if (is_string($ev)) {
                        $js .= $ev;
                    } elseif ($ev instanceof JSFunc) {
                        $js .= $ev->Render(true);
                    } else {
                        ppd($ev);
                    }
                }
            }
            $js .= ')';
        }
        $js .= ';';
        $this->getAppOwner()->addClientScript($js);
        if (!$return) {
            \Response::write($retval);
        }
        return $retval;
    }
}
