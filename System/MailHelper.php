<?php
use System\Exceptions\SystemException;
class MailHelper {
	public static function sendMail($m, $template, $subject = "alKisah-Online") {
		
		$app = AppManager::getInstance ();
		
		$tpl = $app->getInternalData ( "template/email/$template.html" );
		if ($tpl) {
			$o = new stdClass ();
			if ($m instanceof System\DB\Table) {
				$arr = $m->getFields ( true, true, false );
				$o = Utils::bindToObject ( $o, $arr, true );
			} else if (is_array ( $m )) {
				$o = Utils::bindToObject ( $o, $m, true );
			} elseif (is_object ( $m )) {
				$o = $m;
			}
			$o->title = __ ( $subject );
			$o->base_url = BASE_URL;
			$msg = Utils::parseDBTemplate ( file_get_contents ( $tpl ), $o );
			/*$headers  = 'MIME-Version: 1.0' . "\r\n";
			 $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			 $headers .= 'To: '.$m->fullname.'<'.$m->email.'>'. "\r\n";
			 $headers .= 'From: '.$app->getconfig('mail.'.$template.'.from'). "\r\n";;*/
			return MailUtils::send ( $m->user_name, $subject, $msg, true );
		} else {
			throw new SystemException ( 'mail.template.notfound' );
		}
	}
}

?>