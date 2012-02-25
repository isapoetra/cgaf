<?php
namespace System\Template;
use System\Parsers\MarkdownExtra;
use System\Parsers\Wiki;
use \Utils;
use \CGAF;
use \AppManager;
use \System\Exceptions\SystemException;

final class TemplateHelper {
	private static $_instances = array();
	public static function getInstance($args = null, $templateEngine = null) {
		if (!$templateEngine) {
			if (AppManager::isAppStarted()) {
				$templateEngine = AppManager::getInstance()->getConfig("template.class", "BaseTemplate");
			} else {
				$templateEngine = CGAF::getConfig("template.class", "BaseTemplate");
			}
		}
		$class = '\\System\\Template\\' . $templateEngine;
		$instance = new $class(AppManager::getInstance());
		if (!$instance) {
			throw new SystemException("Unable to get template instance %s", $templateEngine);
		}
		return $instance;
	}
	public static function getInstanceForFile($file, $args) {
		$ext = Utils::getFileExt($file, false);
		return self::getInstanceForExt($ext, $args);
	}
	public static function getInstanceForExt($ext, $args) {
		switch (strtolower($ext)) {
		case 'html':
			return self::getInstance($args, 'SimpleTemplate');
		case 'php':
			return self::getInstance($args, 'BaseTemplate');
		case 'wiki':
			return new Wiki();
		case 'md':
			return new MarkdownExtra();
		}
	}
	public static function renderString($s, $params = array(), $controller = null, $ext = 'php') {
		$tmp = CGAF::getInternalStorage('.cache/statics/', false, true) . hash('crc32', $s) . '.' . $ext;
		file_put_contents($tmp, $s);
		$retval = self::renderFile($tmp, $params, $controller, $ext);
		unlink($tmp);
		return $retval;
	}
	public static function renderFile($file, $params = array(), $controller = null, $ext = null) {
		if (!is_file($file)) {
			return null;
		}
		$params = $params ? $params : array();
		$instance = $ext ? self::getInstanceForExt($ext, $params) : self::getInstanceForFile($file, $params);
		$instance->reset();
		$controller = $controller ? $controller : AppManager::getInstance()->getController();
		$instance->setController($controller);
		$instance->assign('appOwner', $controller->getAppOwner());
		$instance->assign($controller->getAppOwner()->getVars());
		$instance->assign($params);
		return $instance->renderFile($file, true);
	}
}
?>