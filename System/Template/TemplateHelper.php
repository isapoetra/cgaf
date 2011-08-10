<?php
namespace System\Template;
use \Utils;
use \CGAF;
use \AppManager;
use \System\Exceptions\SystemException;
final class TemplateHelper {
	private static $_instances = array ();

	public static function getInstance($args=null,$templateEngine=null) {


		if (!$templateEngine) {
			if (AppManager::isAppStarted()) {
				$templateEngine = AppManager::getInstance()->getConfig( "template.class", "BaseTemplate" );

			}else{
				$templateEngine =CGAF::getConfig( "template.class", "BaseTemplate" );
			}
		}
		$class = '\\System\\Template\\'.$templateEngine;
		$instance=new $class(AppManager::getInstance());		
		if (!$instance) {
			throw new SystemException("Unable to get template instance %s",$templateEngine);
		}
		return $instance;

	}
	public static function getInstanceForFile($file,$args) {
		$ext = Utils::getFileExt($file,false);
		switch (strtolower($ext)) {
			case 'php':
				return self::getInstance($args,'BaseTemplate');

		}
		pp($file);
		ppd($ext);
	}
	public static function renderFile($file,$params=array(),$controller =null) {
		if (!is_file($file)) {
			return null;
		}
		$params = $params ? $params : array();
		$instance =  self::getInstanceForFile($file,$params);
		$instance->reset();
		$controller =  $controller ? $controller : AppManager::getInstance()->getController();
		$instance->setController($controller);
		$instance->assign('appOwner',$controller->getAppOwner());
		$instance->assign($controller->getAppOwner()->getVars());
		$instance->assign($params);
		return $instance->renderFile($file,true);
	}
}
?>