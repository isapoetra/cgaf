<?php
use System\Locale\Locale;

defined("CGAF") or die ("Restricted Access");
use System\ACL\ACLHelper, System\Exceptions\AccessDeniedException;

abstract class Utils {
	private static $_imagesExt = array(
			'jpeg',
			'jpg',
			'gif',
			'png'
	);
	private static $_agentSuffix;

	/**
	 * Removes all XSS attacks that came in the input.
	 * Function taken from:
	 * http://quickwired.com/smallprojects/php_xss_filter_function.php
	 *
	 * @param $val mixed
	 *             The Value to filter
	 * @return mixed
	 */
	public static function filterXSS($val) {
		// remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are
		// allowed
		// this prevents some character re-spacing such as <java\0script>
		// note that you have to handle splits with \n, \r, and \t later since
		// they *are* allowed in some inputs
		$val = preg_replace('/([\x00-\x08][\x0b-\x0c][\x0e-\x20])/', '', $val);
		// straight replacements, the user should never need these since they're
		// normal characters
		// this prevents like <IMG
		// SRC=&#X40&#X61&#X76&#X61&#X73&#X63&#X72&#X69&#X70&#X74&#X3A&#X61&#X6C&#X65&#X72&#X74&#X28&#X27&#X58&#X53&#X53&#X27&#X29>
		$search = 'abcdefghijklmnopqrstuvwxyz';
		$search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$search .= '1234567890!@#$%^&*()';
		$search .= '~`";:?+/={}[]-_|\'\\';
		for ($i = 0; $i < strlen($search); $i++) {
			// ;? matches the ;, which is optional
			// 0{0,7} matches any padded zeros, which are optional and go up to
			// 8 chars
			// &#x0040 @ search for the hex values
			$val = preg_replace('/(&#[x|X]0{0,8}' . dechex(ord($search [$i])) . ';?)/i', $search [$i], $val); // with
			// a
			// ;
			// &#00064
			// @
			// 0{0,7}
			// matches
			// '0'
			// zero
			// to
			// seven
			// times
			$val = preg_replace('/(&#0{0,8}' . ord($search [$i]) . ';?)/', $search [$i], $val); // with
			// a
			// ;
		}
		// now the only remaining whitespace attacks are \t, \n, and \r
		$ra1 = Array(
				'javascript',
				'vbscript',
				'expression',
				'applet',
				'meta',
				'xml',
				'blink',
				'link',
				'style',
				'script',
				'embed',
				'object',
				'iframe',
				'frame',
				'frameset',
				'ilayer',
				'layer',
				'bgsound',
				'title',
				'base'
		);
		$ra2 = Array(
				'onabort',
				'onactivate',
				'onafterprint',
				'onafterupdate',
				'onbeforeactivate',
				'onbeforecopy',
				'onbeforecut',
				'onbeforedeactivate',
				'onbeforeeditfocus',
				'onbeforepaste',
				'onbeforeprint',
				'onbeforeunload',
				'onbeforeupdate',
				'onblur',
				'onbounce',
				'oncellchange',
				'onchange',
				'onclick',
				'oncontextmenu',
				'oncontrolselect',
				'oncopy',
				'oncut',
				'ondataavailable',
				'ondatasetchanged',
				'ondatasetcomplete',
				'ondblclick',
				'ondeactivate',
				'ondrag',
				'ondragend',
				'ondragenter',
				'ondragleave',
				'ondragover',
				'ondragstart',
				'ondrop',
				'onerror',
				'onerrorupdate',
				'onfilterchange',
				'onfinish',
				'onfocus',
				'onfocusin',
				'onfocusout',
				'onhelp',
				'onkeydown',
				'onkeypress',
				'onkeyup',
				'onlayoutcomplete',
				'onload',
				'onlosecapture',
				'onmousedown',
				'onmouseenter',
				'onmouseleave',
				'onmousemove',
				'onmouseout',
				'onmouseover',
				'onmouseup',
				'onmousewheel',
				'onmove',
				'onmoveend',
				'onmovestart',
				'onpaste',
				'onpropertychange',
				'onreadystatechange',
				'onreset',
				'onresize',
				'onresizeend',
				'onresizestart',
				'onrowenter',
				'onrowexit',
				'onrowsdelete',
				'onrowsinserted',
				'onscroll',
				'onselect',
				'onselectionchange',
				'onselectstart',
				'onstart',
				'onstop',
				'onsubmit',
				'onunload'
		);
		$ra = array_merge($ra1, $ra2);
		$found = true; // keep replacing as long as the previous round replaced
		// something
		while ($found == true) {
			$val_before = $val;
			for ($i = 0; $i < sizeof($ra); $i++) {
				$pattern = '/';
				for ($j = 0; $j < strlen($ra [$i]); $j++) {
					if ($j > 0) {
						$pattern .= '(';
						$pattern .= '(&#[x|X]0{0,8}([9][a][b]);?)?';
						$pattern .= '|(&#0{0,8}([9][10][13]);?)?';
						$pattern .= ')?';
					}
					$pattern .= $ra [$i] [$j];
				}
				$pattern .= '/i';
				$replacement = substr($ra [$i], 0, 2) . '<x>' . substr($ra [$i], 2); // add
				// in
				// <>
				// to
				// nerf
				// the
				// tag
				$val = preg_replace($pattern, $replacement, $val); // filter out
				// the
				// hex tags
				if ($val_before == $val) {
					// no replacements were made, so exit the loop
					$found = false;
				}
			}
		}
		return $val;
	}

	public static function IncludeFile($fname) {
		if (!is_file($fname)) {
			throw new IOException ("File Not Found" . Logger::WriteDebug($fname));
		}
		$tmp = ob_get_clean();
		ob_start();
		include $fname;
		$retval = ob_get_clean();
		echo $tmp;
		return $retval;
	}

	public static function obGetCleanAll() {
		$s = "";
		do {
			$s = ob_get_contents() . $s;
		} while (ob_end_clean());
		return $s;
	}

	public static function isLive($f) {
		if (is_array($f)) {
			$retval = array();
			foreach ($f as $k => $v) {
				$retval [$k] = self::isLive($v);
			}
			return $retval;
		}
		return Strings::BeginWith("./", $f) || Strings::BeginWith($f, "http://") || Strings::BeginWith($f, "https://") || (is_file($f) && substr($f, 0, strlen(SITE_PATH)) === SITE_PATH);
	}

	public static function makeDir($pathname, $mode = 0750, $securepatern = null) {
		if (is_array($pathname)) {
			$retval = array();
			foreach ($pathname as $p) {
				$retval [$p] = self::makeDir($p, $mode, $securepatern);
			}
			return $retval;
		}
		$pathname = self::ToDirectory($pathname);
		if (!$pathname) {
			return false;
		}

		if (!is_dir($pathname)) {
			if (!@mkdir($pathname, $mode, true)) {
				if (CGAF_DEBUG) {
					echo '<pre>';
					debug_print_backtrace();
					die ("Error while creating directory $pathname");
				}
				return false;
			}
		}
		if ($securepatern) {
			self::securePath($pathname, $securepatern);
		}
		return $pathname;
	}

	public static function getDirList($dir) {
		$dir = self::ToDirectory($dir);
		$files = array();
		if (!is_dir($dir)) {
			Logger::Warning("Directory not exists " . $dir);
			return $files;
		}
		$dh = opendir($dir);
		if (!$dh) {
			Logger::Warning("unable to open dir " . $dir);
			return $files;
		}
		while (false !== ($filename = readdir($dh))) {
			if (substr($filename,0,1)!=='.' && is_dir($dir . "/" . $filename))
				$files [] = $filename;
		}
		closedir($dh);
		return $files;
	}

	/**
	 * Enter description here .
	 * ..
	 *
	 * @param $dir     string
	 * @param $base    string
	 * @param $recurse boolean
	 * @param
	 *                 regex string $match ex /\.png$/i
	 */
	public static function getDirFiles($dir, $base = null, $recurse = true, $match = null) {
		$dir = self::ToDirectory($dir);
		$files = array();
		if (!is_dir($dir)) {
			return $files;
		}
		$dh = opendir($dir);
		if (!$dh) {
			return $files;
		}
		while (false !== ($filename = readdir($dh))) {
			if (substr($filename, 0, 1) == ".")
				continue;
			if (is_file($dir . DS . $filename)) {
				if ($match) {
					$matches = array();
					if (preg_match($match, $filename, $matches)) {
						$files [] = self::ToDirectory($base . $filename);
					}
				} else {
					$files [] = self::ToDirectory($base . $filename);
				}
			} else if (is_dir($dir . DS . $filename) && $recurse) {
				$childs = self::getDirFiles($dir . DS . $filename, ($base ? $base . DS : "") . $filename . DS, true, $match);
				$files = array_merge($files, $childs);
			}
		}
		closedir($dh);
		natcasesort($files);
		return $files;
	}

	public static function bindToObject(&$o, $arr, $all = false) {
		if (!is_object($o))
			die ("Invalid Parameter");
		if (!(is_array($arr) || is_object($arr)))
			die ("Invalid Parameter");
		if (is_object($arr)) {
			$arr = get_object_vars($arr);
		}
		$rvar = get_object_vars($o);
		foreach ($arr as $k => $v) {
			if ($all || array_key_exists($k, $rvar)) {
				$o->$k = $v;
			}
		}
		/*
		 * if (isset($arr['sysmessage'])) { pp($arr);
		* pp('->'.$arr['sysmessage'].'<--'); pp($o->sysmessage); }
		*/
		return $o;
	}

	public static function toObject($o, &$ref, $bindAll = true) {
		return \Convert::toObject($o, $ref, $bindAll);
	}

	public static function copyFile($source, $dest, $options = array(
			"overwrite" => false,
			'folderPermission' => 0755,
			'removeSoure' => false,
			'filePermission' => 0755
	), $callback = null, $callbackparam = null) {
		$overwrite = isset ($options ["overwrite"]) ? $options ["overwrite"] : false;
		$removeSoure = isset ($options ["removeSoure"]) ? $options ["removeSoure"] : false;
		$folderPermission = isset ($options ['folderPermission']) ? $options ['folderPermission'] : 0755;
		$filePermission = isset ($options ['filePermission']) ? $options ['filePermission'] : 0755;
		$source = self::ToDirectory($source);
		$dest = self::ToDirectory($dest);
		if ($source === $dest) {
			return $dest;
		}
		$result = false;
		if (is_file($source)) {
			if ($dest [strlen($dest) - 1] == DS) {
				if (!file_exists($dest)) {
					self::makeDir($dest, $folderPermission, true);
				}
				$__dest = $dest . DS . basename($source);
			} else {
				$__dest = $dest;
			}
			$__dest = self::ToDirectory($__dest);
			if (!is_dir(dirname($__dest))) {
				self::makeDir(dirname($__dest), $folderPermission, true);
			}
			if (!is_file($__dest) || $overwrite) {
				if ($callback) {
					$param = array(
							$__dest
					);
					$param = $callbackparam ? array_merge($param, $callbackparam) : $param;
					$result = call_user_func_array($callback, $param);
					if (!$result) {
						return $result;
					}
				}
				$result = copy($source, $__dest);
				if (!@chmod($__dest, $filePermission)) {
					Logger::Warning("unable to set permission %s " . $__dest, $filePermission);
				}
				if ($result && $removeSoure) {
					@unlink($source);
				}
				if ($result) {
					$result = $__dest;
				}
			} elseif (is_file($__dest)) {
				return true;
			}
		} elseif (is_dir($source)) {
			/*
			 * $files = self::getDirFiles($source,null,false); foreach ($files
			 		* as $file) { $dname = $dest . $file ;
			* self::copyFile($source.DS.$file, $dname); } $dirs =
			* self::getDirList($source); foreach ($dirs as $file) { $dname =
			* $dest . $file ; self::copyFile($source.DS.$file, $dname); }
			*/
			$dirHandle = opendir($source);
			while ($file = readdir($dirHandle)) {
				if (substr($file, 0, 1) !== '.') {
					$__dest = $dest . DS . $file;
					$result = self::copyFile($source . DS . $file, $__dest, $options, $callback, $callbackparam);
				}
			}
			closedir($dirHandle);
		} else {
			$result = false;
		}
		return $result;
	}

	/**
	 * Enter description here .
	 * ..
	 *
	 * @param $f        unknown_type
	 * @param $base     unknown_type
	 * @param $callback unknown_type
	 * @deprecated
	 *
	 *
	 *
	 */
	public static function LocalToLive($f, $base = null, $callback = null) {
		$base = isset ($base) ? $base : null;
		if (is_array($f)) {
			$retval = array();
			foreach ($f as $ff) {
				$file = self::LocalToLive($ff, $base, $callback);
				if ($file) {
					$retval [] = $file;
				} elseif (CGAF_DEBUG) {
					ppd($file);
				}
			}
			return $retval;
		}
		$tmp = $f;
		if (!self::isLive($f)) {
			$tmp = self::getLiveFile($f, $base);
			if ($callback !== null) {
			} else {
				self::copyFile($f, $tmp);
			}
		}
		$tmp = self::PathToLive($tmp);
		return $tmp;
	}

	public static function PathToLive($path) {
		if (Strings::BeginWith($path, 'https:') || Strings::BeginWith($path, 'http:') || Strings::BeginWith($path, 'ftp:')) {
			return $path;
		}
		$path =  self::ToDirectory($path);
		if (!Strings::BeginWith($path, ASSET_PATH)) {
			pp(ASSET_PATH);
			ppd($path);
		}
		$retval = str_ireplace(DS, '/', str_ireplace(ASSET_PATH, ASSET_URL, self::ToDirectory($path)));
		// pp($path.$retval);
		return $retval;
	}

	public static function getLiveFile($f, $base) {
		return self::ToDirectory(CGAF::getConfig("System.LiveTempPath", SITE_PATH . DS . "tmp" . DS . $base . DS . basename($f)));
	}

	public static function ToDirectory($dir, $replaceSpace = false) {

		return \CGAF::toDirectory($dir, $replaceSpace);
	}

	public static function getFileName($fname, $includeExt = false) {
		$retval = basename($fname);
		return $includeExt ? $retval : substr($retval, 0, strlen($retval) - strlen(self::getFileExt($fname, true)));
	}

	public static function getFileExt($fname, $dot = true) {
		if (!$fname) {
			return null;
		}
		$ext = Strings::FromLastPos($fname, ".");
		if (strpos($ext, '?')) {
			$ext = substr($ext, 0, strpos($ext, '?'));
		}
		return strpos($fname, ".") !== false ? (($dot ? "." : "") . $ext) : '';
	}

	public static function toCamelCase($str, $first = true) {
		if ($first) {
			$str [0] = strtoupper($str [0]);
		}
		$func = create_function('$c', 'return strtoupper($c[1]);');
		return preg_replace_callback('/_([a-z])/', $func, $str);
	}

	public static function toClassName($str) {
		$str = self::toCamelCase($str);
		$str = str_replace(" ", "", $str);
		return $str;
	}

	public static function removeFile($file, $DeleteMe = true, $recurse = false, $validExt = null) {
		if ($validExt) {
			$validExt = is_array($validExt) ? $validExt : explode(',', $validExt);
		}
		$file = self::ToDirectory($file);
		if (!CGAF::isAllowFile($file, ACLHelper::ACCESS_MANAGE)) {
			throw new AccessDeniedException ('Access Denied,cannot delete file ' . $file);
		}
		if (is_dir($file)) {
			if (!$dh = @opendir($file))
				return;
			while (false !== ($obj = readdir($dh))) {
				if ($obj == '.' || $obj == '..')
					continue;
				$f = self::ToDirectory($file . '/' . $obj);
				if (is_dir($f)) {
					self::removeFile($f, true, true, $validExt);
				} else {
					if ($validExt) {
						$ext = self::getFileExt($obj, false);
						if (!in_array($ext, $validExt)) {
							continue;
						}
					}
					if (!@unlink($f))
						self::removeFile($file . '/' . $obj, true);
				}
			}
			if ($DeleteMe) {
				closedir($dh);
				@rmdir($file);
			}
		} elseif (is_readable($file)) {
			@unlink($file);
		}
	}

	public static function realPath($f) {
		$f = str_replace('\\', '/', self::ToDirectory($f));
		return realpath($f);
	}

	public static function changeFileExt($fname, $ext) {
		return self::toDirectory(Strings::EndWith($fname, $ext) ? $fname : (strrpos($fname, ".") ? substr($fname, 0, strrpos($fname, ".")) : $fname) . ($ext ? '.' : '') . "$ext");
	}

	public static function changeFileName($fname, $newname) {
		$dname = dirname($fname);
		$ext = self::getFileExt($fname, true);
		if ($dname !== '.') {
			return self::ToDirectory($dname . DS . $newname . $ext);
		} else {
			return $newname . $ext;
		}
	}

	/**
	 * Enter description here .
	 * ..
	 *
	 * @param $o        unknown_type
	 * @param $settings unknown_type
	 * @deprecated
	 *
	 *
	 *
	 */
	public static function toXML($o, $settings = null) {
		return Convert::toXML($o, $settings);
	}

	public static function bool2yesno($val) {
		$val = ( boolean )$val;
		return __($val ? "boolean.yes" : "boolean.no", $val ? "Yes" : "No");
	}

	public static function parseDBTemplate($tpl, $row) {
		$retval = $tpl;
		$row->base_url=BASE_URL;
		$row->app_url=APP_URL;
		foreach ($row as $k => $v) {
			if (is_string($v) || is_numeric($v)) {
				$retval = str_ireplace("#$k#", $v, $retval);
			}
		}
		return $retval;
	}

	public static function getFileMime($filename, $mimePath = null) {
		$mimePath = $mimePath ? $mimePath : dirname(__FILE__);
		$fileext = substr(strrchr($filename, '.'), 1);
		if (empty ($fileext))
			return (false);
		$regex = "/^([\w\+\-\.\/]+)\s+(\w+\s)*($fileext\s)/i";
		$lines = file("$mimePath/mime.types");
		foreach ($lines as $line) {
			if (substr($line, 0, 1) == '#')
				continue;
			// skip comments
			$line = rtrim($line) . " ";
			if (!preg_match($regex, $line, $matches))
				continue;
			// no match to the extension
			return ($matches [1]);
		}
		return (false); // no match at all
	}

	public static function implode($glue, $pieces, $ignoreEmpty = true) {
		if ($ignoreEmpty) {
			$retval = "";
			foreach ($pieces as $v) {
				if (empty ($v))
					continue;
				$retval .= $v . $glue;
			}
			return $retval;
		}
		return implode($glue, $pieces);
	}

	public static function generatePassword($length = 8) {
		// start with a blank password
		$password = "";
		// define possible characters
		$possible = "0123456789bcdfghjkmnpqrstvwxyz";
		// set up a counter
		$i = 0;
		// add random characters to $password until $length is reached
		while ($i < $length) {
			// pick a random character from the possible ones
			$char = substr($possible, mt_rand(0, strlen($possible) - 1), 1);
			// we don't want this character if it's already in the password
			if (!strstr($password, $char)) {
				$password .= $char;
				$i++;
			}
		}
		// done!
		return $password;
	}

	public static function explode($delim, $string, $ignoreempty = false) {
		$ret = explode($delim, $string);
		if ($ignoreempty) {
			$retval = array();
			foreach ($ret as $k => $v) {
				if (empty ($v))
					continue;
				$retval [] = $v;
			}
			return $retval;
		}
		return $ret;
	}

	public static function isImage($ext) {
		return in_array($ext, self::$_imagesExt);
	}

	public static function formatBytes($size, $retstring = null) {
		// adapted from code at
		// http://aidanlister.com/repos/v/function.size_readable.php
		$sizes = array(
				'B',
				'kB',
				'MB',
				'GB',
				'TB',
				'PB',
				'EB',
				'ZB',
				'YB'
		);
		if ($retstring === null) {
			$retstring = '%01.2f %s';
		}
		$lastsizestring = end($sizes);
		foreach ($sizes as $sizestring) {
			if ($size < 1024) {
				break;
			}
			if ($sizestring != $lastsizestring) {
				$size /= 1024;
			}
		}
		if ($sizestring == $sizes [0]) {
			$retstring = '%01d %s';
		} // Bytes aren't normally fractional
		return sprintf($retstring, $size, $sizestring);
	}

	public static function isValidExt($exts, $ext) {
		$exts = is_array($exts) ? $exts : explode(',', $exts);
		return in_array($ext, $exts);
	}

	public static function getSalt($encryption = 'md5-hex', $seed = '', $plaintext = '') {
		// Encrypt the password.
		switch ($encryption) {
			case 'crypt' :
			case 'crypt-des' :
				if ($seed) {
					return substr(preg_replace('|^{crypt}|i', '', $seed), 0, 2);
				} else {
					return substr(md5(mt_rand()), 0, 2);
				}
				break;
			case 'crypt-md5' :
				if ($seed) {
					return substr(preg_replace('|^{crypt}|i', '', $seed), 0, 12);
				} else {
					return '$1$' . substr(md5(mt_rand()), 0, 8) . '$';
				}
				break;
			case 'crypt-blowfish' :
				if ($seed) {
					return substr(preg_replace('|^{crypt}|i', '', $seed), 0, 16);
				} else {
					return '$2$' . substr(md5(mt_rand()), 0, 12) . '$';
				}
				break;
			case 'ssha' :
				if ($seed) {
					return substr(preg_replace('|^{SSHA}|', '', $seed), -20);
				} else {
					return mhash_keygen_s2k(MHASH_SHA1, $plaintext, substr(pack('h*', md5(mt_rand())), 0, 8), 4);
				}
				break;
			case 'smd5' :
				if ($seed) {
					return substr(preg_replace('|^{SMD5}|', '', $seed), -16);
				} else {
					return mhash_keygen_s2k(MHASH_MD5, $plaintext, substr(pack('h*', md5(mt_rand())), 0, 8), 4);
				}
				break;
			case 'aprmd5': /* 64 characters that are valid for APRMD5 passwords. */
				$APRMD5 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
				if ($seed) {
					return substr(preg_replace('/^\$apr1\$(.{8}).*/', '\\1', $seed), 0, 8);
				} else {
					$salt = '';
					for ($i = 0; $i < 8; $i++) {
						$salt .= $APRMD5{rand(0, 63)};
					}
					return $salt;
				}
				break;
			default :
				$salt = '';
				if ($seed) {
					$salt = $seed;
				}
				return $salt;
				break;
		}
	}

	public static function getCryptedPassword($plaintext, $salt = '', $encryption = 'md5-hex', $show_encrypt = false) {
		// Get the salt to use.
		$salt = self::getSalt($encryption, $salt, $plaintext);
		// Encrypt the password.
		switch ($encryption) {
			case 'plain' :
				return $plaintext;
			case 'sha' :
				$encrypted = base64_encode(mhash(MHASH_SHA1, $plaintext));
				return ($show_encrypt) ? '{SHA}' . $encrypted : $encrypted;
			case 'crypt' :
			case 'crypt-des' :
			case 'crypt-md5' :
			case 'crypt-blowfish' :
				return ($show_encrypt ? '{crypt}' : '') . crypt($plaintext, $salt);
			case 'md5-base64' :
				$encrypted = base64_encode(mhash(MHASH_MD5, $plaintext));
				return ($show_encrypt) ? '{MD5}' . $encrypted : $encrypted;
			case 'ssha' :
				$encrypted = base64_encode(mhash(MHASH_SHA1, $plaintext . $salt) . $salt);
				return ($show_encrypt) ? '{SSHA}' . $encrypted : $encrypted;
			case 'smd5' :
				$encrypted = base64_encode(mhash(MHASH_MD5, $plaintext . $salt) . $salt);
				return ($show_encrypt) ? '{SMD5}' . $encrypted : $encrypted;
			case 'aprmd5' :
				$length = strlen($plaintext);
				$context = $plaintext . '$apr1$' . $salt;
				$binary = JUserHelper::_bin(md5($plaintext . $salt . $plaintext));
				for ($i = $length; $i > 0; $i -= 16) {
					$context .= substr($binary, 0, ($i > 16 ? 16 : $i));
				}
				for ($i = $length; $i > 0; $i >>= 1) {
					$context .= ($i & 1) ? chr(0) : $plaintext [0];
				}
				$binary = JUserHelper::_bin(md5($context));
				for ($i = 0; $i < 1000; $i++) {
					$new = ($i & 1) ? $plaintext : substr($binary, 0, 16);
					if ($i % 3) {
						$new .= $salt;
					}
					if ($i % 7) {
						$new .= $plaintext;
					}
					$new .= ($i & 1) ? substr($binary, 0, 16) : $plaintext;
					$binary = JUserHelper::_bin(md5($new));
				}
				$p = array();
				for ($i = 0; $i < 5; $i++) {
					$k = $i + 6;
					$j = $i + 12;
					if ($j == 16) {
						$j = 5;
					}
					$p [] = JUserHelper::_toAPRMD5((ord($binary [$i]) << 16) | (ord($binary [$k]) << 8) | (ord($binary [$j])), 5);
				}
				return '$apr1$' . $salt . '$' . implode('', $p) . JUserHelper::_toAPRMD5(ord($binary [11]), 3);
			case 'md5-hex' :
			default :
				$encrypted = ($salt) ? md5($plaintext . $salt) : md5($plaintext);
				return ($show_encrypt) ? '{MD5}' . $encrypted : $encrypted;
		}
	}

	public static function findConfig($configName, $configs,$toArray=true) {
		$configs = $toArray ?  \Convert::toArray($configs) : $configs;
		if (isset ($configs [$configName])) {
			return $configs [$configName];
		}
		$cfgs = explode('.', $configName);
		$c = null;
		$cnt = count($cfgs);
		$i = 1;
		$rests = $configs;
		$lconfig = '';
		foreach ($cfgs as $cfg) {
			if ($c == null) {
				$c = $cfg;
				$lconfig = $cfg . '.';
			}
			$lconfig = substr($configName, strlen($lconfig));
			if (isset ($configs [$c])) {
				if ($i < $cnt) {
					return self::findConfig($lconfig, $configs [$c]);
				}
				$lconfig .= $cfg . '.';
				$rests = array_shift($rests);
				return $configs [$cfg];
			}
			$i++;
		}
		$retval = null;
		if ($configs) {
			foreach ($configs as $k => $v) {
				if (substr($k, 0, strlen($configName . '.')) === $configName . '.') {
					$retval [substr($k, strlen($configName . '.'))] = $v;
				}
			}
		}
		return $retval;
	}

	public static function QuoteFileName($fname) {
		$fname = self::ToDirectory($fname);
		return escapeshellarg($fname);
	}

	public static function securePath($path, $ext = '(flv|swf|mp4|mp3)$', $override = false) {
		$f = self::ToDirectory($path . DS . '.htaccess');
		if (is_dir($path)) {
			if (is_file($f) && !$override) {
				return;
			}
			$ht = <<< EOT
<Files ~ "\.$ext">
  order allow,deny
  deny from all
</Files>
EOT;
			file_put_contents($f, $ht);
		}
	}

	public static function formatCurrency($num,$locale=null) {
		$locale= $locale? $locale :  AppManager::getInstance()->getLocale()->getLocale();

		$lc = AppManager::getInstance()->getLocale();

		$cr = $lc->_('currencies.prefix','',null,$locale);
		$cre =$lc->_('currencies.suffix','',null,$locale);
		return $cr . ' ' . self::formatNumber($num,2,$locale) . $cre;
	}

	/**
	 * Enter description here .
	 * ..
	 *
	 * @param $num      unknown_type
	 * @param $decimals unknown_type
	 * @deprecated moved to Locale
	 */
	public static function formatNumber($num, $decimals = 2,$locale=null) {
		$locale= $locale? $locale :  AppManager::getInstance()->getLocale()->getLocale();
		$lc = AppManager::getInstance()->getLocale();
		$c=array(
				'comma'=>$lc->_('decimalSeparator','',null,$locale),
				'thousand' => $lc->_('thousandsSeparator','',null,$locale)
		);
		// ppd($c);
		return number_format($num, $decimals, $c ['comma'], $c ['thousand']);
	}

	public static function formatTime($dur, $showms = false) {
		$h = round($dur / (60 * 60 * 60));
		$m = ($dur - ($dur / (60 * 60 * 60))) / 60;
		$s = $dur % 60;
		$ms = $dur % (60 * 60);
		if ($h > 0) {
			if ($showms) {
				$sformat = '%02d:%02d:%02d.%02d';
			} else {
				$sformat = '%02d:%02d:%02d';
			}
			return sprintf($sformat, $h, $m, $s, $ms);
		} else {
			if ($showms) {
				$sformat = '%02d:%02d.%02d';
			} else {
				$sformat = '%02d:%02d';
			}
			return sprintf($sformat, $m, $s, $ms);
		}
	}

	public static function generateId($prefix = null) {
		return $prefix . str_replace('.', '', microtime(true));
	}

	public static function parseSysParam($args) {
		if (!$args) {
			return '';
		} elseif (is_string($args)) {
			return $args;
		} elseif (is_array($args) || is_object($args)) {
			$r = '';
			foreach ($args as $k => $v) {
				if (is_array($v)) {
					$r .= self::parseSysParam($v);
				} elseif (is_numeric($k)) {
					$r .= ' ' . $v;
				} elseif (is_string($k)) {
					$r .= ' ' . $k . '=' . $v;
				}
			}
			return $r;
		}
	}

	public static function sysexec($cmd, $param = array(), $returnoutput = true) {
		$param = self::parseSysParam($param);
		$cmd = $cmd . ($param ? ' ' . $param : '');
		$out = null;
		$r = 0;
		@exec($cmd, $out, $r);
		if ($out) {
			$r = $out [0];
			if (!$r) {
				return false;
			}
		}
		return $returnoutput ? $out : $r;
	}

	public static function DBDataToParam($o, $params = null) {
		if (!$o) {
			return $o;
		}
		if (!is_string($o)) {
			return $o;
		}

		if (substr($o, 0, 1) === '#') {
		    $o .= "\n";
			$o = explode("\n", substr($o, 1));
			$r = array();
			foreach ($o as $v) {
				if (!$v)
					continue;
				$v = explode('=', $v);
				if ($params) {
					$v [1] = \Strings::Replace($v [1], $params, null, false, null, '#', '#');
				}
				$r [$v [0]] = $v [1];
			}
			return $r;
		}
		return @unserialize($o);
	}

	public static function parseIni($f) {
		// if cannot open file, return false
		if (!is_file($f))
			return false;
		$ini = file($f);
		// to hold the categories, and within them the entries
		$cats = array();
		$last = 'Default';
		foreach ($ini as $i) {
			if (@preg_match('/\[(.+)\]/', $i, $matches)) {
				$last = $matches [1];
			} elseif (@preg_match('/(.+)=(.+)/', $i, $matches)) {
				$cats [$last] [$matches [1]] = $matches [2];
			}
		}
		return $cats;
	}

	public static function versionCompare($version1, $version2, $operand) {
		$v1Parts = explode('.', $version1);
		$version1 .= str_repeat('.0', 3 - count($v1Parts));
		$v2Parts = explode('.', $version2);
		$version2 .= str_repeat('.0', 3 - count($v2Parts));
		$version1 = str_replace('.x', '.1000', $version1);
		$version2 = str_replace('.x', '.1000', $version2);
		return version_compare($version1, $version2, $operand);
	}

	public static function getOSInfo() {
		static $info;
		if (!$info) {
			$info = array(
					'a' => php_uname('a'),
					's' => php_uname('s'),
					'n' => php_uname('n'),
					'r' => php_uname('r'),
					'v' => php_uname('v'),
					'm' => php_uname('m'),
					'php' => array(
							'v' => PHP_VERSION
					)
			);
		}
		return $info;
	}

	public static function loadLib($n, $f = null) {
		return extension_loaded($n) or dl(((PHP_SHLIB_SUFFIX === 'dll') ? 'php_' : '') . ($f ? $f : $n) . '.' . PHP_SHLIB_SUFFIX);
	}

	public static function getFileInfo($file) {
		return new TFileInfo ($file);
	}

	/**
	 * Enter description here .
	 * ..
	 *
	 * @param $o mixed
	 * @deprecated
	 *
	 *
	 *
	 */
	public static function toString($o) {
		return Convert::toString($o);
	}

	public static function generateActivationKey() {
		return mt_rand() . mt_rand() . mt_rand() . mt_rand() . mt_rand();
	}

	public static function getAgentSuffix() {
		if (!self::$_agentSuffix) {
			$agent = strtolower($_SERVER ['HTTP_USER_AGENT']);
			if (strpos($agent, 'applewebkit') !== false) {
				self::$_agentSuffix = '-webkit';
			} elseif (strpos($agent, 'mozilla') !== false) {
				self::$_agentSuffix = '-moz';
			} elseif (strpos($agent, 'opera') !== false) {
				self::$_agentSuffix = '-webkit';
			} elseif (CGAF_DEBUG) {
				throw new SystemException ('unhandle browser ' . $agent);
			} else {
				self::$_agentSuffix = '';
			}
		}
		return self::$_agentSuffix;
	}

	public static function isDiff($o, $n) {
		if (gettype($o) !== gettype($n)) {
			return true;
		}
		if (is_array($o) || is_object($n)) {
			foreach ($o as $k => $v) {
				if ($n->$k !== null && !isset ($n->$k)) {
					return true;
				}
				if (is_numeric($o->$k)) {
					if (( string )$o->$k !== ( string )$n->$k) {
						return true;
					}
				} elseif ($o->$k !== $n->$k) {
					return true;
				}
			}
			return false;
		}
		return $o !== $n;
	}

	public static function isEmail($o) {
		if (!$o) {
			return false;
		}
		$e = "/^[-+\\.0-9=a-z_]+@([-0-9a-z]+\\.)+([0-9a-z]){2,4}$/i";
		// from address
		if (!preg_match($e, $o))
			return false;
		return true;
	}

	public static function isUtf8($str) {
		return ( bool )preg_match('%^(?:
				[\x09\x0A\x0D\x20-\x7E]            # ASCII
				| [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
				|  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
				| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
				|  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
				|  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
				| [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
				|  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
		)*$%xs', $str);
	}

	/**
	 * Enter description here .
	 * ..
	 *
	 * @param $o mixed
	 * @deprecated
	 *
	 *
	 *
	 */
	public static function toBool($o) {
		return Convert::toBoolean($o);
	}

	public static function arrayExplode($assoc, $delimiter = ",", $removeEmpty = true, $preserveKey = true) {
		$r = explode($delimiter, $assoc);
		if ($removeEmpty) {
			$retval = array();
			foreach ($r as $k => $v) {
				if ($v == "" || $v == null)
					continue;
				if ($preserveKey) {
					$retval [$k] = $v;
				} else {
					$retval [] = $v;
				}
			}
		} else {
			$retval = $r;
		}
		return $retval;
	}

	public static function arrayImplode($assoc_array, $k_v_glue = '=', $seperator = ',', $ignore = '', $quote = null, $ignoreEmpyValue = false) {
		$tmp = array();
		if (!is_array($assoc_array)) {
			return null;
		}
		foreach ($assoc_array as $k => $v) {
			$i = (is_array($ignore) ? in_array($k, $ignore) : $k == $ignore);
			if (!$i) {
				if (is_object($v)) {
					$v = self::toString($v);
				}
				if ($ignoreEmpyValue && (is_null($v) || $v === ''))
					continue;
				$tmp [] = $k . $k_v_glue . ($quote ? $quote . $v . $quote : $v);
			}
		}
		return implode($seperator, $tmp);
	}

	public static function arrayMerge(&$a1, $a2, $uniqueValue = false, $key2lower = false) {
		if (!$a2) {
			return $a1;
		}
		if (is_string($a2)) {
			$a2 = array(
					$a2
			);
		}

		if (!is_array($a1)) {
			$a1 = array();
		}
		if ($key2lower) {
			$ar = array();
			foreach ($a1 as $k => $v) {
				$ar [strtolower($k)] = $v;
			}
			$a1 = $ar;
			$ar = array();
			foreach ($a2 as $k => $v) {
				$ar [strtolower($k)] = $v;
			}
			$a2 = $ar;
		}
		if ($uniqueValue) {
			foreach ($a2 as $k => $v) {
				if (in_array($v, $a1))
					continue;
				if (is_numeric($k)) {
					$a1 [] = $v;
				} else {
					$a1 [$k] = $v;
				}
			}
		} else {
			foreach ($a2 as $k => $v) {
				if (is_numeric($k)) {
					$a1 [] = $v;
				} elseif (is_array($v)) {
					self::arrayMerge($a1 [$k], $v, $uniqueValue, $key2lower);
				} else {
					$a1 [$k] = $v;
				}
			}
		}
		return $a1;
	}

	public static function array_push_associative(&$arr) {
		$args = func_get_args();
		$ret = 0;
		foreach ($args as $arg) {
			if (is_array($arg)) {
				foreach ($arg as $key => $value) {
					$arr [$key] = $value;
					$ret++;
				}
			} else {
				$arr [$arg] = "";
			}
		}
		return $ret;
	}

	public static function arrayRemove(&$arr, $key) {
		if (!is_array($arr)) {
			return $arr;
		}
		if (is_array($key)) {
			foreach ($key as $v) {
				self::arrayRemove($arr, $v);
			}
			return $arr;
		}
		if (!$key || !$arr) {
			return null;
		}
		$retval = array();
		foreach ($arr as $k => $v) {
			if ($k !== $key) {
				$retval [$k] = $v;
			}
		}
		$arr = $retval;
		return $retval;
	}

	public static function arrayRemoveEmptyValue(&$arr) {
		$retval = array();
		foreach ($arr as $k => $v) {
			if (empty ($v))
				continue;
			$retval [$k] = $v;
		}
		$arr = $retval;
	}

	/**
	 * Remove Array based on value
	 *
	 * @param $arr array
	 * @param $key mixed
	 * @return array
	 */
	public static function arrayRemoveValue(&$arr, $key) {
		if (!is_array($arr)) {
			return $arr;
		}
		if (is_array($key)) {
			foreach ($key as $v) {
				self::arrayRemoveValue($arr, $v);
			}
			return $arr;
		}
		if (!$key || !$arr) {
			return null;
		}
		$retval = array();
		foreach ($arr as $k => $v) {
			if ($v !== $key) {
				$retval [$k] = $v;
			}
		}
		$arr = $retval;
		return $retval;
	}

	public static function array_unshift_assoc(&$arr, $key, $val) {
		$arr = array_reverse($arr, true);
		$arr [$key] = $val;
		$arr = array_reverse($arr, true);
		return count($arr);
	}

	public static function getObjectProperty($o, $index) {
		$vars = get_object_vars($o);
		$k = array_keys($vars);
		return isset ($k [$index]) ? $o->$k [$index] : null;
	}

	public static function fixFileName($name) {
		$replacer = array(
				".." => "",
				"/" => DS,
				"//" => DS,
				"`" => "",
				DS . DS => DS
		);
		foreach ($replacer as $k => $v) {
			$name = str_ireplace($k, $v, $name);
		}
		return $name;
	}

	public static function removeNameSpace($o) {
		if (is_object($o)) {
			$o = get_class($o);
		}
		if (strpos($o, '\\') === false) {
			return $o;
		}
		return substr($o, strrpos($o, '\\') + 1);
	}

	public static function addChar($string, $char, $includePrefix = true) {
		$retval = $includePrefix ? $char : '';
		for ($i = 0; $i <= strlen($string) - 1; $i++) {
			$retval .= $string [$i] . $char;
		}
		$retval = substr($retval, 0, strlen($retval) - 1);
		return $retval;
	}

	public static function parseTitle($label, $clean = false) {
		$access = '';
		$slices = array();
		if (preg_match('/(.*)&([a-zA-Z0-9])(.*)/', $label, $slices)) {
			$chk = $slices[2] . $slices[3];
			$ig = array(
					'amp;',
					'middot;');
			foreach ($ig as $i) {
				//ppd(substr($chk,0,strlen($i)-1));
				if (substr($chk, 0, strlen($i)) === $i) {

					return $clean ? $label : array($label);
				}
			}

			$label = $clean ? $slices [1] . $slices [2] . $slices [3] : $slices [1] . '<u>' . $slices [2] . '</u>' . $slices [3];
			$access = " accesskey='" . strtoupper($slices [2]) . "'";
		}
		$label = str_replace('&&', '&', $label);
		return $clean ? $label : array(
				$label,
				$access
		);
	}

	public static function generateManifest($manifest, $id) {
		if (!$manifest) return;
		$app = \AppManager::getInstance();
		$cmanifest = CGAF::getConfig('app.manifest');
		$f = CGAF_PATH . 'manifest/' . $id . '.manifest';
		\Utils::arrayMerge($cmanifest, $manifest, false, true);
		$s = 'CACHE MANIFEST' . PHP_EOL;
		$s .= '#generated : ' . time() . PHP_EOL;
		foreach ($cmanifest as $k => $v) {
			if (!$v)
				continue;
			$s .= PHP_EOL . strtoupper($k) . ':' . PHP_EOL;
			$sf = $app->getLiveAsset($v);
			if ($sf) {
				$s .= is_array($sf) ? implode(PHP_EOL, $sf) : $sf . PHP_EOL;
			}
		}
		if (!isset ($cmanifest ['NETWORK'])) {
			$s .= PHP_EOL . 'NETWORK:' . PHP_EOL . '*' . PHP_EOL;
		}
		$s .= PHP_EOL . '#---EOF---' . PHP_EOL;
		file_put_contents($f, $s);
	}

	public static function changeFileMode($path, $mode, $recurse = false) {
		$mode=octdec($mode);
		if (is_dir($path)) {
			if (!@chmod($path, $mode)) {
				$dirmode_str = decoct($mode);
				return false;
			}
			if ($recurse) {
				$dh = opendir($path);
				while (($file = readdir($dh)) !== false) {
					if ($file != '.' && $file != '..') { // skip self and parent pointing directories
						$fullpath = $path . '/' . $file;
						self::changeFileMode($fullpath,$mode,true);
					}
				}
				closedir($dh);
			}
		} else {
			if (is_link($path)) {
				return true;
			}
			if (!@chmod($path, $mode)) {
				$filemode_str = decoct($mode);
				return false;
			}
			return true;
		}
	}
}

?>
