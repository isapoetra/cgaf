<?php
namespace System\IO;
use System\Events\Event;
use \Exception;
/**
 *
 * Enter description here ...
 * @author e1
 * @original sourcecode from bas weerman
 * sudo apt-get install libssh2-php
 */

class SFTPConnection extends \BaseObject {
	private $connection;
	private $sftp;
	private $_baseRemotePath;
	private $_dirs = array();
	public function __construct($host, $port = 22) {
		$this->connection = ssh2_connect($host, $port, null, array(
				$this,
				'connlog'
		));
		if (!$this->connection)
			throw new Exception("Could not connect to $host on port $port.");
	}
	function connlog($reason, $message, $language) {
		$this->log(sprintf("Server disconnected with reason code [%d] and message: %s\n", $reason, $message));
	}
	private function log($msg, $pref = '>> ') {
		$this->dispatchEvent(new Event($this, 'onlog', $pref . $msg));
	}
	public function setBaseRemotePath($path) {
		$this->log('setting remotepath ' . $path);
		$this->_baseRemotePath = $path;
	}
	public function login($username, $password) {
		$this->log('Authentificating...' . $username);
		if (!ssh2_auth_password($this->connection, $username, $password))
			throw new Exception("Could not authenticate with username $username " . "and password $password.");
		$this->sftp = ssh2_sftp($this->connection);
		if (!$this->sftp)
			throw new Exception("Could not initialize SFTP subsystem.");
	}
	public function mkdir($path) {
		if (!in_array($path, $this->_dirs)) {
			ssh2_sftp_mkdir($this->sftp, $path, 0770, true);
			$this->_dirs[] = $path;
		}
	}
	public function uploadFile($local_file, $remote_file, $scpmode = false) {
		if ($scpmode) {
			ssh2_scp_send($this->connection, $local_file, $remote_file);
		} else {
			$sftp = $this->sftp;
			$this->mkdir(dirname($remote_file));
			$stream = @fopen("ssh2.sftp://$sftp$remote_file", 'w+');
			if (!$stream)
				throw new Exception("Could not open file: $remote_file");
			$data_to_send = @file_get_contents($local_file);
			if ($data_to_send === false)
				throw new Exception("Could not open local file: $local_file.");
			if (@fwrite($stream, $data_to_send) === false)
				throw new Exception("Could not send data from file: $local_file.");
			@fclose($stream);
		}
	}
	public function exec($cmd) {
		$this->log($cmd);
		if (!($stream = ssh2_exec($this->connection, $cmd))) {
			throw new Exception('SSH command failed');
		}
		stream_set_blocking($stream, true);
		$data = "";
		while ($buf = fread($stream, 4096)) {
			$data .= $buf;
		}
		fclose($stream);
		$this->log($data, '<< ');
		return $data;
	}
	function scanFilesystem($remote_file) {
		$sftp = $this->sftp;
		$dir = "ssh2.sftp://$sftp$remote_file";
		$tempArray = array();
		$handle = opendir($dir);
		// List all the files
		while (false !== ($file = readdir($handle))) {
			if (substr("$file", 0, 1) != ".") {
				if (is_dir($file)) {
					//                $tempArray[$file] = $this->scanFilesystem("$dir/$file");
				} else {
					$tempArray[] = $file;
				}
			}
		}
		closedir($handle);
		return $tempArray;
	}
	public function receiveFile($remote_file, $local_file) {
		$sftp = $this->sftp;
		$stream = @fopen("ssh2.sftp://$sftp$remote_file", 'r');
		if (!$stream)
			throw new Exception("Could not open file: $remote_file");
		$contents = fread($stream, filesize("ssh2.sftp://$sftp$remote_file"));
		file_put_contents($local_file, $contents);
		@fclose($stream);
	}
	public function deleteFile($remote_file) {
		$sftp = $this->sftp;
		unlink("ssh2.sftp://$sftp$remote_file");
	}
}
