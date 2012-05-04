<?php
namespace System\API;
use System\Events\Event;
use System\Collections\Collection;
use System\Exceptions\SystemException;
use System\Configurations\Configuration;
use \IApplication;
use \CGAF;

class SMSData {
	public $From;
	public $From_TOA;
	public $From_SMSC;
	public $Sent;
	public $Received;
	public $Subject;
	public $Modem;
	public $IMSI;
	public $Alphabet;
	public $Message;
	public $StorageFile;
	public $Type;
	function __construct($sms) {
		$this->Type = SMSTools::SMS_INCOMING;
		if (is_string($sms)) {
			$this->parseString($sms);
		}
	}
	private function parseString($sms) {
		$o = explode(PHP_EOL, $sms);
		foreach ($o as $k => $v) {
			if ($v === "") {
				$i = $k;
				while ($i < count($o)) {
					$this->Message = $o[$i];
					$i++;
				}
				break;
			} else {
				$rv = explode(": ", $v);
				$kv = ucwords($rv[0]);
				$this->$kv = $rv[1];
			}
		}
	}
}

class SMSCollection extends Collection {
	function __construct() {
		parent::__construct(null, false, 'System\\API\SMSData');
	}
}
/**
 *
 * Enter description here ...
 * @author e1
 * @example
 * 	Required
 *  sudo apt-get install procmail
 * 	Allow www-data to access sms daemon
 * 	adduser www-data smsd
 */

class SMSTools extends \BaseObject {
	const SMS_INCOMING = 'incoming';
	private $_appOwner;
	private $_config;
	function __construct(\IApplication $app) {
		$this->_appOwner = $app;
		$this->_config = new Configuration(null, false);
		$f = $app->getInternalStorage('configurations/') . 'smstools.json';
		if (!is_file($f)) {
			$f = CGAF::getInternalStorage('configurations', false) . 'smstools-default.json';
		}
		$this->_config->loadFile($f);
		$this->preinit();
	}
	private function preInit() {
	}
	private function log($msg) {
		$this->dispatchEvent(new Event($this, 'onlog', array(
						$msg
				)));
	}
	function inbox() {
		$this->log('Cheking sms inbox');
		$retval = new SMSCollection();
		$ipath = \Utils::ToDirectory($this->_config->getConfig('System.incoming') . DS);
		$files = \Utils::getDirFiles($ipath, $ipath, false);
		foreach ($files as $f) {
			$c = file_get_contents($f);
			$sms = new SMSData($c);
			$sms->Type = self::SMS_INCOMING;
			$sms->StorageFile = basename($f);
			$retval->add($sms);
		}
		return $retval;
	}
	function delete(SMSData $data) {
		$dir = '';
		switch ($data->Type) {
		case self::SMS_INCOMING:
			$dir = \Utils::ToDirectory($this->_config->getConfig('System.incoming') . DS);
			break;
		default:
			return false;
			break;
		}
		$f = $dir . $data->StorageFile;
		if (is_file($f)) {
			if (@unlink($f)) {
				$this->log('Delete success');
				return true;
			}
			$this->log('Deleting file ' . $this->StorageFile . ' Failed');
			return false;
		}else{
			$this->log('Deleting non existing file');
		}
	}
	function sendSMS($id, $to, $text, $setting = null) {
		$def = array(
				'flash' => 'no',
				'charset' => 'unmodified'
		);
		$setting = \Utils::arrayMerge($def, $setting);
		$device = $this->_config->getConfig('System.devices');
		$filename = $this->_config->getConfig('System.outgoing') . DS . $id . '-' . time();
		if (($handle = fopen($filename . ".LOCK", "w")) != false) {
			$l = strlen($st = "To: $to\n");
			fwrite($handle, $st);
			if ($setting['charset'] == "UNICODE") {
				$l += strlen($st = "Alphabet: UCS\n");
				fwrite($handle, $st);
				$text = mb_convert_encoding($text, "UCS-2BE", "UTF-8");
			} else if ($setting['charset'] == "ISO")
				$text = mb_convert_encoding($text, "ISO-8859-15", "UTF-8");
			if ($setting['flash'] != "") {
				$l += strlen($st = "Flash: yes\n");
				fwrite($handle, $st);
			}
			$l += strlen($st = "Adjustment: +");
			fwrite($handle, $st);
			$pad = 14 - $l % 16 + 16;
			while ($pad-- > 0)
				fwrite($handle, "+");
			fwrite($handle, "\n\n$text");
			fclose($handle);
			if (rename($filename . ".LOCK", $filename) == true) {
				return true;
			}
			return false;
		} else
			throw new SystemException('Unable to send sms' . ferror());
	}
}
