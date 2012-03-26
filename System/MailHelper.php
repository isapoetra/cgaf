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
			/*$headers  = 'MIME-Version: 1.0' . "\r\n";
			 $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= 'To: '.$m->fullname.'<'.$m->email.'>'. "\r\n";
			$headers .= 'From: '.$app->getconfig('mail.'.$template.'.from'). "\r\n";;*/
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
	function send($mail,$subject,$msg,$html=false,$cc=array()) {
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

		// Additional headers
		$headers .= 'To: Mary <mary@example.com>, Kelly <kelly@example.com>' . "\r\n";
		$headers .= 'From: Birthday Reminder <birthday@example.com>' . "\r\n";
		$headers .= 'Cc: birthdayarchive@example.com' . "\r\n";
		$headers .= 'Bcc: birthdaycheck@example.com' . "\r\n";

	}
	/*
	 public static function send($recipient,$subject,$content,$html=true) {
	PEAR::load('Mail');
	PEAR::load('Mail.mime');
	$app = AppManager::getInstance();
	$crlf = "\n";
	$sender = $app->getconfig('mail.sender','admin@localhost');
	$headers = array(
			'From'          => $sender,
			'Return-Path'   => $sender,
			'Subject'       => $subject
	);
	// Creating the Mime message
	$mime = new Mail_mime($crlf);
	// Setting the body of the email
	$mime->setTXTBody($content);
	if ($html) {
	$mime->setHTMLBody($content);
	}

	// Set body and headers ready for base mail class
	$body = $mime->get();
	$headers = $mime->headers($headers);
	$engine = $app->getConfig('mail.engine','smtp');
	$params = $app->getConfig('mail.'.$engine.'.params',$app->getConfig('mail.params'));

	// Sending the email using smtp
	$mail = Mail::factory($engine, $params);
	$result = 0;
	try {
	$result = $mail->send($recipient, $headers, $body);
	}catch(Exception $e) {
	return false;
	}

	return $result == 1;
	}*/
}

?>