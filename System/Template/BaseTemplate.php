<?php
namespace System\Template;
use System\Web\JS\JSUtils;
use \System\Web\JS\CGAFJS;
//TODO Cleanup
class BaseTemplate extends AbstractTemplate {
	private $_cssFile = array();
	private $_css = array();
	private $_jsFile = array();
	private $_scripts = array();
	private $_jsEngine = "jq";
	private $_controller;
	private $_metas = array();
	private $_header = array();
	private $_assetHandlers = array();
	private $_assets = array();
	function addAssetHandler($handler) {
		$this->_assetHandlers[] = $handler;
	}
	function getLive($name, $prefix = null, $throw = true) {
		if ($name == null) {
			return null;
		}
		$retval = $this->getAppOwner()->getLiveAsset($name, $prefix);
		if (!$retval && $throw) {
			throw new SystemException($name . $prefix);
		}
		return $retval;
	}
	function Assign($varName, $value = null, $overwrite = true) {
		if ($varName === 'site_title') {
			return $this->assignHeader('title', $value, null, $overwrite);
		}
		return parent::Assign($varName, $value, $overwrite);
	}
	private function parseHeaderElement($tag, $value) {
		switch (strtolower($tag)) {
		case 'meta':
			foreach ($value as $k => $v) {
				$this->addMeta($k, __($v));
			}
			break;
		default:
			$this->_header[$tag] = $value;
			break;
		}
	}
	protected function Init() {
		parent::Init();
		$owner = $this->getAppOwner();
		$header = $owner && $owner instanceof IApplication ? $owner->getConfig('header', array()) : array();
		foreach ($header as $k => $v) {
			$this->parseHeaderElement($k, $v);
		}
		$this->_css = array();
	}
	function assignHeader($name, $value, $key, $replace = false) {
		if ($key) {
			$metas = isset($this->_header[$key]) ? $this->_header[$key] : array();
			if (!isset($metas[$name])) {
				$metas[$name] = array();
			}
			if (!is_array($value)) {
				$value = array(
						$value);
			}
			if ($replace) {
				$metas[$name] = $value;
			} else {
				$metas[$name] = array_merge_recursive($metas[$name], $value);
			}
			$this->_header[$key] = $metas;
			return $this;
		} else {
			$this->_header[$name] = $value;
		}
	}
	function addMeta($name, $value, $replace = false) {
		return $this->assignHeader($name, $value, 'meta', $replace);
	}
	function getController($controllerName = NULL) {
		if ($controllerName) {
			return $this->getAppOwner()->getController($controllerName);
		}
		if (!$this->_controller) {
			$this->_controller = $this->getAppOwner()->getController();
		}
		return $this->_controller;
	}
	function setController($value) {
		$this->_controller = $value;
	}

	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $js
	 */
	public function addClientScript($script, $type = 'javascript') {
		$this->getAppOwner()->addClientScript($script);
	}

	function getCSSLink($css, $id = null) {
		$c = null;
		if ($this->getContentCallback()) {
			$c = call_user_func_array($this->getContentCallback(), array(
					"rpath" => $css,
					"content" => null,
					"id" => $id,
					"group" => "css"));
		}
		if (!$c) {
			if (is_array($css)) {
				$r = array();
				foreach ($css as $c) {
					$tc = $this->getLive($c);
					if ($tc) {
						$r[] = $tc;
					}
					$tc = $this->getAppOwner()->getAssetAgent($c);
					if ($tc) {
						$r[] = $tc;
					}
				}
				$c = $r;
			} else {
				$c[] = $this->getLive($css);
				$r = $this->getAppOwner()->getAssetAgent($css);
				if ($r) {
					$c[] = $r;
				}
			}
		}
		if ($c) {
			if (is_array($c)) {
				$retval = '';
				foreach ($c as $cs) {
					$retval .= "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"" . $cs . "\"/>\n";
				}
				return $retval;
			} else {
				return "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"" . $c . "\"" . ($id && !is_numeric($id) ? " id=\"$id\"" : "") . "/>";
			}
		}
		return null;
	}
	function renderScript($s, $force = false) {
		if (Request::isSupport("javascript") || $force) {
			if (is_array($s)) {
				ppd($s);
				$s = implode("\n;", $s);
			}
			return "<script type=\"text/javascript\">\n" . $s . "\n</script>";
		}
		return null;
	}
	function isAllow($access = "view") {
		return $this->getAppOwner()->getController()->isAllow($access);
	}
	protected function _renderHeader() {
		$retval = '';
		foreach ($this->_header as $k => $v) {
			switch (strtolower($k)) {
			case 'title':
				$retval .= '<title>' . $v . '</title>' . "\n";
				break;
			case 'meta':
				foreach ($v as $name => $value) {
					$retval .= '<meta name="' . $name . '" content="' . implode(', ', $value) . '"/>' . "\n";
				}
				break;
			default:
				foreach ($v as $value) {
					$retval .= "<$k";
					foreach ($value as $n => $v2) {
						$retval .= " $n=\"$v2\"";
					}
					$retval .= "/>";
				}
			}
		}
		return $retval;
	}
	function renderHeader() {
		static $rendered;
		if (!$rendered) {
			$retval = "";
			if (!Request::isAJAXRequest() && !Request::isDataRequest()) {
				$tmp = $this->getLive('favicon.ico');
				$retval .= $this->_renderHeader();
				if ($tmp != null) {
					$retval .= "<link rel=\"shortcut icon\" href=\"$tmp\"/>\n";
				}
			}
		}
		$rendered = true;
		$retval = $this->renderClientAssets();
		$retval .= $this->_renderClientScript();
		//$retval .= $this->renderJS ();
		//$retval .= $this->renderCSS ();
		//$retval .= $this->renderAsset ();
		return $retval;
	}
	function renderFooter() {
	}
	function _getLive($rdata, $group) {
		$retval = array();
		if (!count($rdata)) {
			return null;
		}
		$cache = $this->getAppOwner()->getCacheManager();
		foreach ($rdata as $key => $data) {
			$js = $data['url'];
			if (is_array($js) && !isset($js['url'])) {
				$rj = $this->_getLive($js, $key);
				$retval = array_merge($retval, $rj);
				continue;
			} elseif (is_array($js)) {
				ppd($js);
			}
			if (Utils::isLive($js)) {
				$retval[] = $js;
			} else {
				$r = null;
				if ($this->getContentCallback()) {
					$r = call_user_func_array($this->getContentCallback(), array(
							"rpath" => $js,
							"content" => null,
							"id" => is_numeric($key) ? $js : $key,
							"group" => $group));
				}
				if (!$r) {
					if (is_array($js)) {
						$tocache = array();
						foreach ($js as $v) {
							$v = $this->getJSAsset($v['url']);
							if ($v) {
								$tocache[] = $v;
							}
						}
					} else {
						$tocache = $js;
					}
					$live = $cache->get($key, $group);
					if (!$live) {
						$live = $cache->putFile($tocache, $key, null, "js");
					}
					$r = $this->getLive($live, $this->_jsEngine);
				}
				if ($r) {
					if (is_array($r)) {
						$retval = array_merge($retval, $r);
					} else {
						$retval[] = $r;
					}
				}
			}
		}
		return $retval;
	}
	private function _getLiveAsset($asset) {
		return $this->getAppOwner()->getLiveAsset($asset);
		;
	}
	/**
	 *
	 * Enter description here ...
	 * @deprecated
	 */
	public function renderAsset() {
		return $this->renderClientAssets();
	}
	public function renderClientAssets() {
		$assets = $this->getAppOwner()->getClientAssets();
		if (!count($assets) && !count($assets)) {
			return null;
		}
		$assets = array();
		$retval = $this->_renderLive($assets);
		$this->_assets = array();
		return $retval;
	}
	private function _renderClientScript($script = true) {
		$retval = '';
		foreach ($this->_scripts as $type => $s) {
			switch (strtolower($type)) {
			case 'js':
			case 'javascript':
				$retval .= $script ? '<script language="javascript" type="text/javascript">' : '';
				break;
			default:
				throw new UnimplementedException('unhandle script type ' . $type);
				break;
			}
			foreach ($s as $v) {
				$retval .= $v;
				switch (strtolower($type)) {
				case 'js':
				case 'javascript':
					$retval .= ";\n";
					break;
				}
			}
			switch (strtolower($type)) {
			case 'js':
			case 'javascript':
				$retval .= $script ? '</script>' : '';
				break;
			default:
				break;
			}
		}
		$this->_scripts = array();
		return $retval;
	}
	public function renderCSS() {
		if (!count($this->_cssFile) && !count($this->_css)) {
			return null;
		}
		$css = $this->_getLive($this->_cssFile, 'css');
		$retval = "<!-- BEGIN CSS --->\n";
		$retval .= $this->_renderLive($css);
		if (count($this->_css)) {
			$retval .= '<style type="text/css" media="all">';
			$css = WebUtils::parseCSS(implode("", $this->_css));
			$retval .= '</style>';
		}
		$retval .= "<!-- END CSS --->\n";
		$this->_css = array();
		$this->_cssFile = array();
		return $retval;
	}
	private function getLiveJS($js, $throw = true) {
		$ori = $js;
		$min = $this->getLive(Utils::changeFileExt($js, "min.js"), $this->_jsEngine, false);
		if ($min === null) {
			//
			if (!CGAF_DEBUG) {
				$js = JSUtils::PackFile($this->getAppOwner()->getAsset($ori));
			} else {
				$js = $this->getLive($js, $this->_jsEngine, $throw);
			}
		} else {
			if (CGAF_DEBUG) {
				$alt = $this->getLive($js, $this->_jsEngine, false);
				if ($alt !== null) {
					$min = $alt;
				}
			}
			$js = $min;
		}
		return $js;
	}
	private function getJSAsset($js) {
		$min = $this->getAppOwner()->getResource(Utils::changeFileExt($js, "min.js"), null, false);
		$js = $this->getAppOwner()->getResource($js, null, false);
		if (!CGAF_DEBUG && $min) {
			$js = $min;
		}
		return $js;
		//return HTMLUtils::getJSAsset ( $js, false, $this->_jsEngine );
	}
	private function _renderLive($r, $data = null) {
		if (!$r) {
			$r = $this->_assets;
		}
		$f = $this->getAppOwner()->getLiveAsset($r);
		if ($r) {
			if (is_array($r)) {
				$retval = '';
				foreach ($r as $j) {
					$retval .= $this->_renderLive($j, $data) . "\n";
				}
				return $retval;
			}
			$ext = Utils::getFileExt($r, false);
			$r = Utils::LocalToLive($r, $ext);
			switch (strtolower($ext)) {
			case 'js':
				return '<script language="javascript" type="text/javascript" ' . ($data['id'] ? 'id="' . $data['id'] . '" ' : '') . ' src="' . $r . '"></script>';
			case 'css':
				return "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"" . $r . "\"/>";
			default:
				if (stripos($ext, 'js')) {
					return '<script language="javascript" type="text/javascript" ' . ($data['id'] ? 'id="' . $data['id'] . '" ' : '') . ' src="' . $r . '"></script>';
				} else {
					return "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"" . $r . "\"/>";
				}
			}
		}
	}
	private function _renderJS($js, $key) {
		$r = null;
		if (is_array($js)) {
			$retval = array();
			foreach ($js as $j) {
				$retval[] = $this->_renderJS($j['url'], $key);
			}
			return $retval;
		}
		$cache = $this->getAppOwner()->getCacheManager();
		if ($this->getContentCallback()) {
			$r = call_user_func_array($this->getContentCallback(), array(
					"rpath" => $js,
					"content" => null,
					"id" => is_numeric($key) ? $js : $key,
					"group" => "js"));
		}
		if (!$r) {
			if (is_array($js)) {
				$tocache = array();
				foreach ($js as $v) {
					$v = $this->getJSAsset($v['url']);
					if ($v) {
						$tocache[] = $v;
					}
				}
			} else {
				$tocache = $js;
			}
			$live = $cache->get($key, "js");
			if (!$live) {
				$live = $cache->putFile($tocache, $key, null, "js");
			}
			$r = $this->getLive($live, $this->_jsEngine);
		}
		return $r;
	}
	private function renderJS($script = true) {
		$retval = "";
		if (!Request::get('__s') && Request::isSupport("javascript") && (count($this->_jsFile) || count($this->_scripts))) {
			$retval = count($this->_jsFile) ? "<!-- BEGIN JSFile --->\n" : '';
			foreach ($this->_jsFile as $key => $data) {
				$js = $data['url'];
				$r = $this->_renderJS($js, $key);
				if ($r) {
					$retval .= $this->_renderLive($r, $data);
				}
			}
			$retval .= count($this->_jsFile) ? "<!-- END JSFile --->\n" : '';
			$retval .= $script ? "<script type=\"text/javascript\">" : '';
			//$retval .= '$(function() {';
			//$retval .= "$.ui.dialog.defaults.bgiframe = true;";
			foreach ($this->_scripts as $js) {
				$retval .= $js . ";\n";
			}
			//$retval .= '});';
			$retval .= $script ? '</script>' : '';
			$this->_scripts = array();
			$this->_jsFile = array();
		}
		return $retval;
	}
	public function renderJSOnly() {
		$retval = "<script type=\"text/javascript\" language=\"javascript\">";
		$retval .= "$.ui.dialog.defaults.bgiframe = true;";
		$retval .= '$(function() {';
		foreach ($this->_scripts as $js) {
			$retval .= $js . ";";
		}
		$retval .= '});';
		$retval .= '</script>';
		return $retval;
	}
	public function reset() {
		$this->_vars = array();
		$this->_jsFile = array();
		$this->_cssFile = array();
	}
}
?>
