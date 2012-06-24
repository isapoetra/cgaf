<?php
namespace System\MVC;
use System\ACL\ACLHelper;
use System\Exceptions\SystemException;
use System\Web\Utils\HTMLUtils;
use System\Template\TemplateHelper;

abstract class StaticContentController extends Controller {
  protected $_template;
  protected $_useLocale = true;
  /**
   *
   * Extension of content file without dot
   * @var string
   */
  protected $_fileExt = 'html';
  function __construct(Application $appOwner, $routeName = null,$extension ='html') {
    parent::__construct($appOwner,$routeName);
    $this->_fileExt = $extension;
  }
  public function getContentPath() {
    return $this->getAppOwner()->getInternalData('contents/' . $this->getControllerName() . '/', true);
  }
  protected function getContentFile($a, $check = true) {
    $spath = array(
        $this->getContentPath(),
        \CGAF::getInternalStorage('contents/' . $this->getControllerName() . '/', false)
    );
    $lc = $this->getAppOwner()->getLocale()->getLocale();
    $dc = $this->getAppOwner()->getLocale()->getDefaultLocale();
    $s = array();
    foreach ($spath as $p) {
      $def = $p . $a . '.' . $this->_fileExt;
      if ($this->_useLocale) {
        $f = $p . $a . '-' . $lc . '.' . $this->_fileExt;
        $s[] = $f;
      }
      $s[] = $def;
      if (!$check) {
        if (is_file($f)) {
          return $f;
        } elseif ($lc === $dc) {
          return $def;
        }
      } elseif (is_file($f)) {
        return $f;
      }elseif (is_file($def)) {
        return $def;
      }
    }
    if (CGAF_DEBUG) {
      ppd($s);
    }
  }

  /**
   * Enter description here ...
   * @param string $a
   * @param string $f
   * @return NULL
   */
  protected function renderFile($a, $f,$params=null) {
    return $this->renderStaticConent($a, $f, $this->_template,$params);
  }
  /* (non-PHPdoc)
   * @see System\MVC.Controller::Index()
  */
  function Index($a = null) {
    $route = MVCHelper::getRoute();
    $a = $this->getActionAlias($a ? $a : $route['_a']);
    $id = ACLHelper::secureFile(\Request::get('id',null,true), false);
    if ($id) {
      $a = $a . DS . $id;
    }
    $f = $this->getContentFile($a, true);
    if ($f && is_file($f)) {
      return $this->renderFile($a, $f);
    } elseif ($route['_a'] !=='index') {
      return "Content not found".$route['_a'];
    }
    //}
    return parent::Index();
  }
}
