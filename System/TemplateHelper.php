<?php
final class TemplateHelper {
	private static $_instances = array ();

	private static function getInstance($args=null,$templateEngine=null) {
		$app = AppManager::getInstance();
		
		if (!$templateEngine) {
			$templateEngine = AppManager::getInstance()->getConfig( "template.class", "BaseTemplate" );
			CGAF::Using('System.'.CGAF_CONTEXT.'.Template.'.$templateEngine,false);
		}
		$instance = $app->getClassInstance($templateEngine,null,$app);
		return $instance;

	}
	public static function renderFile($file,$params=array()) {
		$params = $params ? $params : array();		
		$instance =  self::getInstance();
		$instance->reset();
		$instance->assign($params);
		return $instance->renderFile($file,true);
	}
}
?>