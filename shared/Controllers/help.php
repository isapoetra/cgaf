<?php
namespace System\Controllers;
use System\MVC\Controller;
class HelpController extends Controller {
	function __construct($appOwner) {
		parent::__construct($appOwner, 'help');
	}
	function isAllow($access = 'view') {
		switch (strtolower($access)) {
		case 'download':
			return $this->getAppOwner()->isAuthentificated();
			break;
		case 'get':
		case 'view':
		case 'html':
			return true;
		}
		return parent::isAllow($access);
	}
	function get() {
		$id = Request::get('id', 'index');
		$path = $this->getInternalPath() . DS . $id . '.swf';
		if (!is_file($path)) {
			$p = Utils::changeFileExt($path, 'pdf');
			if (!is_file($p)) {
				throw new IOException('file.notfound');
			}
			try {
				$pdf = new PDF($p);
				$pdf->toSWF($path);
			} catch (Exception $e) {
				throw new SystemException('error while converting pdf' . $e->getMessage());
			}
		}
		$stream = new MediaStream($path);
		$stream->stream();
		CGAF::doExit();
	}
	private function findHelpFile($id) {
		$fname = basename($id);
		$path = $this->getInternalPath() . 'html' . DS;
		$activeLocale = $this->getAppOwner()->getLocale()->getLocale();
		$search = array(
				$path . $activeLocale . DS . $fname,
				$path . $fname);
		foreach ($search as $s) {
			if (is_file($s)) {
				return $s;
			}
		}
		return null;
	}
	function download() {
	}
	function html() {
		$id = Request::get('id', 'index.html');
		switch ($id) {
		//hook for html help
		case 'nsh.js':
			$path = $this->getAppOwner()->getAsset('help.js');
			break;
		default:
			$path = $this->findHelpFile($id);
			break;
		}
		if (!is_file($path)) {
			die('Help file not found');
		}
		header("Content-Type: " . Utils::getFileMime($path));
		$ext = Utils::getFileExt($path, false);
		$contents = file_get_contents($path);
		switch ($ext) {
		case 'htm':
		case 'html':
			$contents = str_replace("src='", 'src=\'' . BASE_URL . '/help/html/?id=', $contents);
			$contents = str_replace('background="', 'background="' . BASE_URL . '/help/html/?id=', $contents);
			$contents = str_replace('background: url(', 'background: url(' . BASE_URL . '/help/html/?id=', $contents);
			$contents = str_replace("src=\"", 'src="' . BASE_URL . '/help/html/?id=', $contents);
			$contents = str_replace('href="', 'href="' . BASE_URL . '/help/html/?id=', $contents);
			$contents = Utils::parseDBTemplate($contents, array());
			if (Request::isAJAXRequest()) {
				$contents .= <<< EOT
<script type="text/javascript">
$('#helpcontainer a').bind('click',function(e){
	$('#helpcontainer').load($(this).attr('href'));
	return false;
});
</script>
EOT;
			} else {
				$contents = $this->render(array(
								'_a' => 'header'), null, true) . $contents;
				$contents .= $this->render(array(
								'_a' => 'footer'), null, true);
			}
			break;
		default:
			;
			break;
		}
		echo $contents;
		CGAF::doExit();
	}
	function Index() {
		return parent::render(null, null);
	}
}
