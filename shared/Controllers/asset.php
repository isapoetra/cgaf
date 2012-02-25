<?php
use \System\Exceptions\SystemException;
use System\Web\UI\JQ\FileManager;
class AssetController extends System\MVC\Controller {
	function isAllow($access = 'view') {
		switch (strtolower($access)) {
		case 'view':
		case 'browse':
		case 'get':
			$access = 'view';
			break;
		}
		return parent::isAllow($access);
	}
	function browse() {
		$browser = new FileManager('asset-browser');
		$browser->setBasePath($this->getAppOwner()->getAppPath() . 'assets/images/');
		$browser->setReturnPath(BASE_URL . '/asset/get/?q=');
		return $browser;
	}
	private function prepareHeader($file) {
		if (!$file) {
			return;
		}
		$ext = \Utils::getFileExt($file, false);
		switch ($ext) {
		case 'html':
		case 'css':
		case 'png':
		case 'jpg':
		case 'ico':
		case 'gif':
		case 'js':
		//case 'ttf':
			\CGAF::cacheRequest(time(), 30, false, $file);
			break;
		default:
			;
			break;
		}
	}
	function Index() {
		return $this->get();
	}
	function get() {
		$rasset = Request::get('q', null, true);
		$appId = Request::get('appId');
		$appOwner = null;
		if ($appId) {
			try {
				$appOwner = AppManager::getInstance($appId);
			} catch (\Exception $e) {
			}
		}
		if (!$appOwner) {
			$appOwner = $this->getAppOwner();
		}
		$asset = $appOwner->getAsset($rasset);
		if (!$asset) {
			$asset = $appOwner->getAsset($appOwner->getAppPath() . DS . $rasset);
		}
		if (!$asset) {
			if (CGAF_DEBUG) {
				throw new SystemException('asset ' . $rasset . 'not found');
			}
			CGAF::doExit();
			return;
		}
		if ($appOwner->isAllowToLive($asset)) {
			if (is_readable($asset)) {
				$this->prepareHeader($asset);
				Streamer::Stream($asset);
			} else {
				\Logger::Warning(__CLASS__ . '->' . __FUNCTION__ . 'asset file ' . $asset . ' not readable by system');
				CGAF::doExit();
			}
			return true;
		} else {
		}
	}
}
