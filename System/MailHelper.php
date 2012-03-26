<?php

use System\DB\Table;
use System\Exceptions\SystemException;
use System\Mail\MailObject;
abstract class MailTransport {
	private static $_instance =array();
	function getInstance($transport=null) {
		$transport = $transport ? $transport : MailHelper::getConfig('engine','smtp');
		if (!isset(self::$_instance[$transport])) {
			$c = 'System\\Mail\\Transport\\'.$transport;
			self::$_instance[$transport]= new $c;
		}
		return self::$_instance[$transport];
	}
}
class MailException extends SystemException {

}

class MailHelper {
	public static function getConfigs($configName,$default=null) {
		$configName = 'mail.'.$configName;
		$retval = array();
		$iconfig=\AppManager::getInstance()->getConfigs($configName,array());
		$cconfig = \CGAF::getConfigs($configName);
		return array_merge($cconfig,$iconfig);		
		
	}
	public static function getConfig($configName,$default=null) {
		$configName = 'mail.'.$configName;
		return \AppManager::getInstance()->getConfig($configName,\CGAF::getConfig($configName,$default));
		
	}
	public static function sendMail($m, $template, $subject = "alKisah-Online") {

		$app = AppManager::getInstance ();

		$tpl = $app->getInternalData ( "template/email/" ).$template.'.html';
		if (!is_file($tpl)) {
			$tpl=\CGAF::getInternalStorage('template/email/',false,false).$template.'.html';

		}
		if (is_file($tpl)) {
			$o = new stdClass ();
			if ($m instanceof Table) {
				$arr = $m->getFields ( true, true, false );
				$o = Utils::bindToObject ( $o, $arr, true );
			} else if (is_array ( $m )) {
				$o = Utils::bindToObject ( $o, $m, true );
			} elseif (is_object ( $m )) {
				$o = $m;
			}
			$mail = $m->user_email ? $m->user_email : $m->user_name;
			if (!\Utils::isEmail($mail)) {
				throw new SystemException('error.invalidemail');
			}

			$o->title = __ ( $subject );
			$o->base_url = BASE_URL;
			$msg = Utils::parseDBTemplate ( file_get_contents ( $tpl ), $o );
			$e=new MailObject();
			$e->to = $mail;
			$e->subject = $subject;
			if (!$ok=MailTransport::getInstance()->send($e)) {
				throw new MailException('mail.senderror');
			}
			return $ok;
		} else {
			throw new SystemException ( 'mail.template.notfound'.$tpl );
		}
	}
	
}

?>