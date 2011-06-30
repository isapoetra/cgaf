<?php
defined ( 'CGAF' ) or die ( 'restricted access' );

if (class_exists ( 'AppModule', false )) {
	throw new Exception ( 'whar' );
}
using("System.AppModel.MVC.MVCModule");
interface IAppModule {

	function handleAddins($addinInfo, &$obj, $container);

	//function handleService ($serviceName);
}
/**
 * 
 * Enter description here ...
 * @author Iwan Sapoetra @ Jun 26, 2011
 * @deprecated use MVCModule
 */
abstract class AppModule extends MVCModule {

	
}

?>