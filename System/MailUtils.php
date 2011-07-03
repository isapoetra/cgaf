<?php
using('libs.PEAR');
abstract class MailUtils {
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
	}
}