<?php

use System\Applications\IApplication;
use System\Collections\SearchItemCollection;
use System\Configurations\Configuration;
use System\Exceptions\SystemException;

abstract class OpenSearch
{
    private static function _header($s, $o, $f)
    {
        $total = 0;
        foreach ($o as $v) {
            $total += $v['result']->getResultCount();
        }
        $baseurl = BASE_URL;
        $title = AppManager::getInstance()->getConfig('site.search.shortname');
        switch ($f) {
            case 'html':
                return <<< EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
 <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
   <head profile="http://a9.com/-/spec/opensearch/1.1/" >
     <title>Search result for : $s</title>
     <link rel="search"
           type="application/opensearchdescription+xml"
           href="$baseurl/OpenSearch/?_type=content"
           title="$title" />
     <meta name="totalResults" content="$total"/>
     <meta name="startIndex" content"1"/>
     <meta name="itemsPerPage" content="10"/>
   </head>
EOF;
                break;
                break;

            default:
                throw new SystemException('unhandled search format' . $f);
                break;
        }

    }

    private static function _footer($f)
    {
        switch ($f) {
            case 'html':
                return '</html>';
                break;

            default:
                ;
                break;
        }
    }

    public static function parse($s, $o, $f)
    {

        $retval = self::_header($s, $o, $f);
        if (is_array($o)) {
            $r = null;
            foreach ($o as $v) {
                $res = $v ['result'];
                if ($res instanceof SearchItemCollection) {
                    $r .= $res->renderAs($f);

                }

            }
            switch ($f) {
                case 'html' :
                    $retval .= '<ul>' . $r . '</ul>';
                    break;
                default :
                    ;
                    break;
            }
        }
        $retval .= self::_footer($f);
        return $retval;
    }
}

class BasicSearchEngine implements ISearchEngine
{
    private $_appOwner;
    private $_config;

    function __construct(IApplication $appOwner)
    {
        $this->_appOwner = $appOwner;
        $this->_config = new Configuration ($appOwner->getConfig("searchEngine", array()));
    }

    public function getAppOwner()
    {
        return $this->_appOwner;
    }

    protected function getSearch($s, $format)
    {
        $c = $this->_appOwner->getCacheManager();

        $sid = md5($s . '-' . $format);
        $retval = $c->getContent($sid, 'search');
        if ($retval === null) {
            $cfg = $this->getConfigs();
            foreach ($cfg as $sc) {
                try {
                    using('System.SearchProvider.' . strtolower($sc ["provider"]));
                    $ct = 'TSearchProvider' . $sc ["provider"];
                    $c = new $ct ($this);
                    $r = $c->search($s, $sc);
                    if ($r instanceof SearchItemCollection) {
                        $data = array();
                        $data ["config"] = $sc;
                        $data ["result"] = $r;
                        $retval [] = $data;
                    }
                } catch (Exception $e) {
                    ppd($e);
                    Logger::write($e->getMessage());
                }
            }
            //$c->put ( $sid, serialize ( $retval ), "search" );
        } else {
            $retval = unserialize($retval);
            $retval ["__info"] = "cache";
        }
        return $retval;
    }

    function doSearch(IController $controler)
    {
        $s = Request::get("s");
        $f = Request::get('format', 'default');
        if ($s == null) {
            return null;
        }
        $retval = $this->getSearch($s, $f);
        switch (strtolower($f)) {
            case 'rss':
            case 'atom':
                return OpenSearch::parse($s, $retval, $f);
            case 'html':
            default:
                return $controler->render(array(
                    '_a' => 'result'), array(
                    "rows" => $retval));
        }

    }

    function doAdvanced()
    {

    }

    function getConfigs()
    {
        return $this->_config->getConfigs();
    }

    function getConfig($configName, $def = null)
    {
        return $this->_config->getConfig($configName, $def);
    }
}

?>