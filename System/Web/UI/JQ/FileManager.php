<?php
class TJQFileManager extends JQControl {
	private $_basePath;
	private $_baseScript ;
	private $_baseRoot='/';
	private $_returnPath;
	private $_js =array();
	private $_ignore = array(
		'.svn'
	);
	function __construct($id,$template) {
		parent::__construct($id,$template);
		$this->_baseScript= BASE_URL.'/Data/js/filemanager/';
		$this->setConfig('baseUrl',Request::getOrigin());
	}

	function setBasePath($value) {
		$this->_basePath = $value;
		return $this;
	}
	function addClientScript($js) {
		$this->_js[] = $js;
	}
	function RenderScript($return=false) {
		$retval = "";
		$app = $this->getTemplate()->getAppOwner();

		$baseurl = BASE_URL.'/Data/js/';
		$fmScript = $app->getLiveData('jquery.filemanager.js');
		$retval .= <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>File Manager</title>
		<link rel="stylesheet" type="text/css" href="{$baseurl}filemanager/styles/reset.css" />
		<link rel="stylesheet" type="text/css" href="{$baseurl}jquery.filetree/jqueryFileTree.css" />
		<link rel="stylesheet" type="text/css" href="{$baseurl}jquery.splitter/jquery.splitter.css" />
		<link rel="stylesheet" type="text/css" href="{$baseurl}jquery.contextmenu/jquery.contextMenu.css" />
		<link rel="stylesheet" type="text/css" href="{$baseurl}filemanager/styles/filemanager.css" />
		<!--[if IE]>
		<link rel="stylesheet" type="text/css" href="{$baseurl}filemanager/styles/ie.css" />
		<![endif]-->

	</head>
<body>
EOT;
		$id = $this->getId();
		$configs = JSON::encodeConfig($this->_configs);
		$retval .= '<div id="'.$id.'">&nbsp;</div>';
		$retval .= $this->getTemplate()->renderJS();
		$retval .= JSUtils::renderJSFile(array(
			'jquery-1.3.2.js',
			'jquery.url.js',
			'jquery.impromptu-1.5.js',
			'jquery.splitter/jquery.splitter.js',
			'jquery.filetree/jqueryFileTree.js',
			'jquery.contextmenu/jquery.contextMenu.js',
			'jquery.tablesorter.js',
			'filemanager/jquery.filemanager.js'
		),'filemanager.js');

		//$retval .= CMSUtils::renderJSFile($this->_js);
		$retval .= <<<EOT
			<script type="text/javascript">
				$(function() {
					$('#$id').fileManager($configs);
				})
			</script>
EOT;
		$retval .= $this->getTemplate()->renderFooter();
		$retval .= '</body>';
		$retval .= '</html>';
		return $retval;
	}
	private function getRoot($root = null,$request='dir') {
		$info = $this->getRequestInfo($request);
		return $info->realPath;
	}
	private function renderTree($root = null) {
		$root = $this->getRequestInfo('dir');
		$retval = null;
		$path =  null;
		if ($root->baseRoot === '/') {
			$retval .= "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
			if (is_string($this->_basePath)) {
				$dir = '/0';
				$retval .="<li class=\"directory collapsed\"><a href=\"#\" rel=\"/0/\">Root</a></li>";
			}else{
				foreach($this->_basePath as $k=>$v) {
					$dir = '/'.$k;
					$retval .="<li class=\"directory collapsed\"><a href=\"#\" rel=\"/" . $k . "/\">" . htmlentities($v['title']) . "</a></li>";
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

				$files = Utils::getDirFiles($root->realPath,null,false);
				foreach($files as $file) {
					$ext = preg_replace('/^.*\./', '', $file);
					$retval .= "<li class=\"file ext_$ext\"><a href=\"#\" rel=\"" . htmlentities($root->baseRoot . $file) . "\">" . htmlentities($file) . "</a></li>";
				}
				$retval .= '</ul>';
				return $retval;
			}else{
				return '<ul><li>Path not found</li></ul>';
			}

		}
		return $retval;
	}
	public function error($string,$textarea=false) {
		$array = array(
			'Error'=>$string,
			'Code'=>'-1',
			'Properties'=>$this->properties
		);
		if($textarea) {
			echo '<textarea>' . json_encode($array) . '</textarea>';
		} else {
			echo json_encode($array);
		}
		die();
	}
	private function ignore($f) {
		return $f == '.' || $f == '..' || in_array($f,$this->_ignore);
	}
	private function getRequestInfo($request) {
		$root = Request::get($request,'/',false);
		$retval = new stdClass();
		urldecode($root);
		$idx =substr($root,1,strpos($root,'/',1)-1);
		$retval = new stdClass();
		$retval->baseRoot = ($idx===false ?  '/' : '/'.$idx.'/').substr($root,strpos($root,'/',1)+1,strrpos($root,'/'));
		$retval->reqFile = urldecode(substr($root,strpos($root,'/',1)+1));
		$retval->rootName  =is_array($this->_basePath) ? $this->_basePath[$idx]['title'] : '/';
		$retval->root  =is_array($this->_basePath) ? $this->_basePath[$idx] : $this->_basePath;
		$retval->realPath =  Utils::ToDirectory((is_array($this->_basePath) ?$retval->root['path'] : $retval->root ).$retval->reqFile);
		$retval->human = '/'.$retval->rootName.'/'.$retval->reqFile;
		return $retval;

	}
	private function getfolder() {
		$array = array();

		$reqInfo = $this->getRequestInfo('path');
		$app = AppManager::getInstance();
		if ($reqInfo->baseRoot =='/') {
			foreach($this->_basePath as $k=>$v) {
				$array ['/' . $k . '/'] = array(
						'Path'=> '/'.$k.'/',
						'Filename'=> $v['title'],
						'File Type'=> 'dir',
						'Preview'=> $app->getLiveData('filetypes/_Close.png'),
						'Properties'=> array(
								'Date Created'=> null,
								'Date Modified'=> null,
								'Height'=> null,
								'Width'=> null,
								'Size'=> null),
						'Error'=> "",
						'Code'=> 0);
			}
		}else {
			if(!is_dir($reqInfo->realPath)) {
				$this->error(sprintf(__('error.unabletoopendirectory','Unable to Open Directory [%s]'),$reqInfo->human));
			} else {
				$handle = opendir($reqInfo->realPath);
				while (false !== ($file = readdir($handle))) {
					if($this->ignore($file)) continue;
					if(is_dir($reqInfo->realPath.$file)) {
						$array[$reqInfo->baseRoot . $file] = array(
							'Path'=> $reqInfo->baseRoot . $file.'/',
							'Filename'=>$file,
							'File Type'=>'dir',
							'Preview'=>$app->getLiveData('filetypes/_Close.png'),
							'Properties'=>array(
								'Date Created'=>null,
								'Date Modified'=>null,
								'Height'=>null,
								'Width'=>null,
								'Size'=>null
							),
							'Error'=>"",
							'Code'=>0
						);
					} else {
						$info = $this->getFileInfo($reqInfo->realPath.DS.$file);

						$array [$reqInfo->baseRoot . $file] = array(
								'Path'=> $reqInfo->baseRoot . $file,
								'Filename'=> $info ['filename'],
								'File Type'=> $info ['filetype'],
								'Preview'=> $info ['preview'],
								'Properties'=> $info ['properties'],
								'Error'=> "",
								'Code'=> 0);

					}
				}
				closedir($handle);
			}
		}
		return $array;
	}
	private function getFileInfo($path) {
		if($path=='') {
			$path = $this->get['path'];
		}
		$app=AppManager::getInstance();
		$retval = array();
		$retval['filename'] = basename($path);
		$retval['filetype'] = Utils::getFileExt($path,false);
		$retval['filemtime'] = filemtime($path);
		$retval['filectime'] = filectime($path);

		$retval['preview'] = $app->getLiveData('filetypes/'.$retval['filetype'].'.png');

		if(is_dir($path)) {
			$retval['preview'] = $app->getLiveData('filetypes/_Open.png');
		} else if(Utils::isImage($retval['filetype'])) {
			//TODO recheck for security
			$retval['preview'] = $app->getLiveData($path);
			list($width, $height, $type, $attr) = getimagesize($path);
			$retval['properties']['Height'] = $height;
			$retval['properties']['Width'] = $width;
			$retval['properties']['Size'] = filesize($path);
		}

		$retval['properties']['Date Modified'] = $app->getLocale()->formatDate($retval['filemtime'],true);//;date($this->config['date'], $retval['filemtime']);
		return $retval;
	}
	function setReturnPath($f) {
		$this->_returnPath = $f;
	}
	function getReturnPath($f) {
		return ($this->_returnPath  ? $this->_returnPath  : $this->_baseRoot).$f;
	}
	function render($return = false) {
		if (Request::get('tree')) {
			return $this->renderTree();
		}
		$mode =Request::get('mode');
		switch (strtolower($mode)) {
			case 'getfolder':
				return $this->getFolder();
			case 'getinfo':
				$path = $this->getRequestInfo('path');

				$info = $this->getFileInfo($path->realPath);
				$array = array(
					'Path'=> $this->getReturnPath($path->reqFile),
					'Filename'=>$info['filename'],
					'File Type'=>$info['filetype'],
					'Preview'=>$info['preview'],
					'Properties'=>$info['properties'],
					'Error'=>"",
					'Code'=>0
				);

				return $array;
		}
		return parent::Render($return);
	}
}
?>
