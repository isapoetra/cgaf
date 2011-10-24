<?php
namespace System\Web\UI\JQ;
use System\Exceptions\SystemException;
use System\Web\UI\Controls\Form;
use System\Web\UI\Controls\FileEditor;
use System\Web\JS\CGAFJS;
use System\JSON\JSON;
use \Request;
use \Utils;
use \AppManager;
class FileManager extends Control {
	private $_basePath = '.';
	private $_baseScript;
	private $_baseRoot = '/';
	private $_returnPath;
	private $_js = array();
	private $_ignore = array(
			'.svn',
			'.git');
	function __construct($id) {
		parent::__construct($id);
		$this->_jsClientObj = 'fileManager';
		$this->_baseScript = BASE_URL . '/assets/js/jQuery/plugins/';
		$this->setConfig('baseUrl', Request::getOrigin());
		$this->SetConfig('fmasset', ASSET_URL . 'js/jQuery/plugins/filemanager/');
	}
	function setBasePath($value) {
		$this->_basePath = $value;
		return $this;
	}
	function setCanEdit($value) {
		$this->setConfig('toolbar.buttonEdit', $value);
	}
	function addClientScript($js) {
		$this->_js[] = $js;
	}
	function RenderScript($return = false) {
		$retval = "";
		$app = $this->getAppOwner();
		$baseurl = ASSET_URL . 'js/jQuery/plugins/';
		//TODO merge as 1
		$scripts = array(
				$this->_baseScript . 'tablesorter/jquery.metadata.js',
				$this->_baseScript . 'tablesorter/jquery.tablesorter.js',
				//$this->_baseScript . 'tablesorter/themes/blue/style.css',
				$this->_baseScript . 'jQuery-Impromptu/jquery-impromptu.js	',
				$this->_baseScript . 'jQuery-Impromptu/default.css',
				$this->_baseScript . 'jquery.contextmenu/jquery.contextMenu.js',
				$this->_baseScript . 'jquery.contextmenu/jquery.contextMenu.css',
				$this->_baseScript . 'jquery.filetree/jqueryFileTree.js',
				$this->_baseScript . 'jquery.filetree/jqueryFileTree.css',
				$this->_baseScript . 'jquery.splitter/jquery.splitter.js',
				$this->_baseScript . 'jquery.splitter/jquery.splitter.css',
				$this->_baseScript . 'filemanager/jquery.filemanager.js',
				$this->_baseScript . 'filemanager/styles/filemanager.css');
		$app->addClientAsset($scripts);
		return parent::RenderScript($return);
	}
	private function getRoot($root = null, $request = 'dir') {
		$info = $this->getRequestInfo($request);
		return $info->realPath;
	}
	private function renderTree($root = null) {
		$root = $this->getRequestInfo('dir');
		$retval = null;
		$path = null;
		if ($root->baseRoot === '/') {
			$retval .= "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
			if (is_string($this->_basePath)) {
				$dir = '/0';
				$retval .= "<li class=\"directory collapsed\"><a href=\"#\" rel=\"/0/\">Root</a></li>";
			} else {
				foreach ($this->_basePath as $k => $v) {
					$dir = '/' . $k;
					$retval .= "<li class=\"directory collapsed\"><a href=\"#\" rel=\"/" . $k . "/\">" . htmlentities($v['title']) . "</a></li>";
				}
			}
			$retval .= '</ul>';
			return $retval;
		}
		if ($root) {
			if (is_dir($root->realPath)) {
				$retval = "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
				$files = Utils::getDirList($root->realPath);
				foreach ($files as $file) {
					$retval .= "<li class=\"directory collapsed\"><a href=\"#\" rel=\"" . htmlentities($root->baseRoot . $file) . "/\">" . htmlentities($file) . "</a></li>";
				}
				$files = Utils::getDirFiles($root->realPath, null, false);
				foreach ($files as $file) {
					$ext = preg_replace('/^.*\./', '', $file);
					$retval .= "<li class=\"file ext_$ext\"><a href=\"#\" rel=\"" . htmlentities($root->baseRoot . $file) . "\">" . htmlentities($file) . "</a></li>";
				}
				$retval .= '</ul>';
				return $retval;
			} else {
				return '<ul><li>Path not found</li></ul>';
			}
		}
		return $retval;
	}
	public function error($string, $textarea = false) {
		$array = array(
				'Error' => $string,
				'Code' => '-1',
				'Properties' => $this->properties);
		if ($textarea) {
			echo '<textarea>' . json_encode($array) . '</textarea>';
		} else {
			echo json_encode($array);
		}
		die();
	}
	private function ignore($f) {
		return $f == '.' || $f == '..' || in_array($f, $this->_ignore);
	}
	private function getRequestInfo($request) {
		$root = Request::get($request, '/', false);
		$retval = new \stdClass();
		urldecode($root);
		$idx = substr($root, 1, strpos($root, '/', 1) - 1);
		$retval = new \stdClass();
		$retval->baseRoot = ($idx === false ? '/' : '/' . $idx . '/') . substr($root, strpos($root, '/', 1) + 1, strrpos($root, '/'));
		$retval->reqFile = urldecode(substr($root, strpos($root, '/', 1) + 1));
		$retval->rootName = is_array($this->_basePath) ? $this->_basePath[$idx]['title'] : '/';
		$retval->root = is_array($this->_basePath) ? $this->_basePath[$idx] : $this->_basePath;
		$retval->realPath = Utils::ToDirectory((is_array($this->_basePath) ? $retval->root['path'] : $retval->root) . $retval->reqFile);
		$retval->human = '/' . $retval->rootName . '/' . $retval->reqFile;
		return $retval;
	}
	private function getfolder() {
		$array = array();
		$reqInfo = $this->getRequestInfo('path');
		$app = AppManager::getInstance();
		if ($reqInfo->baseRoot == '/') {
			foreach ($this->_basePath as $k => $v) {
				$array['/' . $k . '/'] = array(
						'Path' => '/' . $k . '/',
						'Filename' => $v['title'],
						'File Type' => 'dir',
						'Preview' => $app->getLiveData('filetypes/_Close.png'),
						'Properties' => array(
								'Date Created' => null,
								'Date Modified' => null,
								'Height' => null,
								'Width' => null,
								'Size' => null),
						'Error' => "",
						'Code' => 0);
			}
		} else {
			if (!is_dir($reqInfo->realPath)) {
				$this->error(sprintf(__('error.unabletoopendirectory', 'Unable to Open Directory [%s]'), $reqInfo->human));
			} else {
				$handle = opendir($reqInfo->realPath);
				while (false !== ($file = readdir($handle))) {
					if ($this->ignore($file))
						continue;
					if (is_dir($reqInfo->realPath . $file)) {
						$array[$reqInfo->baseRoot . $file] = array(
								'Path' => $reqInfo->baseRoot . $file . '/',
								'Filename' => $file,
								'File Type' => 'dir',
								'Preview' => $app->getLiveData('filetypes/_Close.png'),
								'Properties' => array(
										'Date Created' => null,
										'Date Modified' => null,
										'Height' => null,
										'Width' => null,
										'Size' => null),
								'Error' => "",
								'Code' => 0);
					} else {
						$info = $this->getFileInfo($reqInfo->realPath . DS . $file);
						$array[$reqInfo->baseRoot . $file] = array(
								'Path' => $reqInfo->baseRoot . $file,
								'Filename' => $info['filename'],
								'File Type' => $info['filetype'],
								'Preview' => $info['preview'],
								'Properties' => $info['properties'],
								'Error' => "",
								'Code' => 0);
					}
				}
				closedir($handle);
			}
		}
		return $array;
	}
	private function getFileInfo($path) {
		if ($path == '') {
			$path = $this->get['path'];
		}
		if (!is_readable($path))
			return null;
		$app = AppManager::getInstance();
		$retval = array();
		$retval['filename'] = basename($path);
		$retval['filetype'] = Utils::getFileExt($path, false);
		$retval['filemtime'] = filemtime($path);
		$retval['filectime'] = filectime($path);
		$retval['preview'] = $app->getLiveData('filetypes/' . $retval['filetype'] . '.png');
		if (is_dir($path)) {
			$retval['preview'] = $app->getLiveData('filetypes/_Open.png');
		} else if (Utils::isImage($retval['filetype'])) {
			//TODO recheck for security
			$retval['preview'] = $app->getLiveData($path);
			$retval['returnpath'] = $retval['preview'];
			list($width, $height, $type, $attr) = getimagesize($path);
			$retval['properties']['Height'] = $height;
			$retval['properties']['Width'] = $width;
			$retval['properties']['Size'] = filesize($path);
		}
		$retval['properties']['Date Modified'] = $app->getLocale()->formatDate($retval['filemtime'], true); //;date($this->config['date'], $retval['filemtime']);
		return $retval;
	}
	function setReturnPath($f) {
		$this->_returnPath = $f;
	}
	function getReturnPath($f) {
		return ($this->_returnPath ? $this->_returnPath : $this->_baseRoot) . $f;
	}
	private function edit() {
		$reqInfo = $this->getRequestInfo('path');
		if (is_file($reqInfo->realPath)) {
			if ($this->getAppOwner()->isValidToken()) {
				file_put_contents($_REQUEST['__fcontent']);
				\Response::Redirect($this->getConfig('baseUrl'));
			} else {
				$retval = new Form();
				$feditor = new FileEditor($reqInfo->realPath);
				$feditor->setId('__fcontent');
				$retval->addChild($feditor);
				$retval->setRenderToken(true);
				return $retval->Render(true);
			}
		}
		throw new SystemException('not found');
	}
	function render($return = false) {
		if (Request::get('tree')) {
			return $this->renderTree();
		}
		$mode = Request::get('mode');
		switch (strtolower($mode)) {
		case 'edit':
			if ($this->getConfig('toolbar.buttonEdit')) {
				return $this->edit();
			}
		case 'download':
			if ($this->getConfig('toolbar.buttonDownload')) {
				$path = $this->getRequestInfo('path');
				return \Streamer::Stream($path->realPath,null,true);
			}
		case 'getfolder':
			return $this->getFolder();
		case 'getinfo':
			$path = $this->getRequestInfo('path');
			$info = $this->getFileInfo($path->realPath);
			$array = array(
					'Path' => $this->getReturnPath($path->reqFile),
					'Filename' => $info['filename'],
					'File Type' => $info['filetype'],
					'Preview' => $info['preview'],
					'Properties' => $info['properties'],
					'Error' => "",
					'Code' => 0);
			return $array;
		}
		return parent::Render($return);
	}
}
?>
