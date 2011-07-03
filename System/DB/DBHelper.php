<?php
if (! defined ( "CGAF" ))
	die ( "Restricted Access" );
abstract class DBHelper {
	
	public static function loadObjectLists($sql,$conn =null) {
		$conn = $conn ? $conn : AppManager::getInstance ()->getDBConnection ();
		$q = new DBQuery ( $conn );
		$q->addSQL ( $sql );
		return $q->loadObjects ();
	}
	public static function lookup($name, $appOwner = null) {
		static $lookup;
		$appId = - 1;
		if ($appOwner == null) {
			$appOwner = AppManager::getInstance ();
			$appId = $appOwner->getAppId ();
		} else {
			if (! is_object ( $appOwner )) {
				$appId = $appOwner;
				$appOwner = AppManager::getInstance ();
			}
		}
		if (! $lookup) {
			$lookup = $appOwner->getModel ( 'lookup' );
		}
		$rows = $lookup->setIncludeAppId ( false )->clear ()->select ( "`key`,`value`,`descr`" )->where ( 'app_id=' . $lookup->quote ( $appId ) )->where ( 'lookup_id=' . $lookup->quote ( $name ) )->loadObjects ();
		return $rows;
	}
	public static function loadScalar($sql,$conn) {
		$q = new DBQuery($conn);
		$q->addSQL($sql);
		return $q->loadScalar();
		
	}
	public static function lookupValue($name, $value) {
		$rows = self::lookup ( $name );
		if (! count ( $rows )) {
			return 'Unknown ' . (CGAF_DEBUG ? $name : '');
		}
		foreach ( $rows as $v ) {
			if ($v->key == $value) {
				return $v->value;
			}
		}
		return null;
	}
}