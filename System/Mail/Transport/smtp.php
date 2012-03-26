<?php
namespace System\Mail\Transport;
use System\Mail\MailObject;

class SMTP extends AbstractTransport {
	private $_mailObject;
	private $_mime_boundary;
	function __construct(MailObject $o=null) {
		$this->_mime_boundary = md5(time());
		$this->_mailObject=$o;
		parent::init('smtp');		
		\System::iniset($this->getConfigs('iniset'),null,'');				
	}
	function send(MailObject $o) {
		$this->_mailObject=$o;
		$headers = $o->getHeaders();
		$header = $this->prepareHeader();
		$message = $o->content;
		$addOption='-f'.ini_get('sendmail_from');
		$attachments = $o->getAttachments();
		if ($attachments) {
			$message = "--{$this->_mime_boundary}\n" . "Content-Type: text/plain; charset=\"iso-8859-1\"\n" .
					"Content-Transfer-Encoding: 7bit\n\n" . $message . "\n\n";
			foreach($attachments as $f) {
				if(is_file($f)){
					$message .= "--{$this->_mime_boundary}\n";
					$fp =    @fopen($f,"rb");
					$data =    @fread($fp,filesize($f));
					@fclose($fp);
					$data = chunk_split(base64_encode($data));
					$message .= "Content-Type: application/octet-stream; name=\"".basename($f)."\"\n" .
							"Content-Description: ".basename($f)."\n" .
							"Content-Disposition: attachment;\n" . " filename=\"".basename($f)."\"; size=".filesize($f).";\n" .
							"Content-Transfer-Encoding: base64\n\n" . $data . "\n\n";
					$message .= "--{$this->_mime_boundary}--";
				}
			}

		}
		return mail($o->to, '=?UTF-8?B?'.base64_encode($o->subject).'?=', $message,$addOption);

	}

	private function prepareHeader() {
		$o=$this->_mailObject;
		$headers = $o->getHeaders();
		switch (strtolower($o->priority)) {
			case 'high':
				$headers['X-Priority']='1 (Higuest)';
				$headers['X-MSMail-Priority']='High';
				$headers['Importance']='High';
				break;
			default:
				throw new MailException('mail.unhandleimportance',$value);
		}
		
		$retval = array();
		if ($o->getAttachments()) {
			//Main Idea from Anda comment @ http://php.net/manual/en/function.mail.php
			$semi_rand = md5(time());
			$this->_mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";
			$headers['Content-Type']='multipart/mixed';
			$headers['boundary']=$this->_mime_boundary;
		}
		foreach ($headers as $k=>$v) {
			$retval[] = $k.':'.\Convert::toString($v);
		}
		if ($o->cc) {
			$retval[]='CC:'.\Convert::toString($o->cc,',');
		}
		if ($o->bcc) {
			$retval[]='BCC:'.\Convert::toString($o->cc,',');
		}

		return implode(PHP_EOL,$retval);

	}
}
