<?php
use System\Exceptions\SystemException;

class OSInfo extends BaseObject {

	function __construct() {
		$this->os = PHP_OS;
	}

	function getWindow() {
		return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
	}

	function getLinux() {
		return strtoupper(substr(PHP_OS, 0, 3)) === 'LIN';
	}
}

/**
 * Class System
 */
abstract class System {
	private static $_osInfo;
	private static $_initialized;
	const WebContext = 'Web';

    /**
     * @internal param $path
     */
    public static function addIncludePath() {
		foreach (func_get_args() as $path) {
			if (!file_exists($path)
					or (file_exists($path) && filetype($path) !== 'dir')) {
				trigger_error("Include path '{$path}' not exists",
						E_USER_WARNING);
				continue;
			}
			$delim = self::isLinux() ? ':' : ';';
			$paths = explode($delim, get_include_path());
			$path = Utils::ToDirectory($path);
			if (array_search($path, $paths) === false)
				array_push($paths, $path);
			set_include_path(implode($delim, $paths));
		}
	}

	public static function isConsole() {
		if (!defined('CGAF_CONTEXT')) {
			return false;
		}
		return strtolower(CGAF_CONTEXT) === 'console';
	}

    /**
     * @internal param $path
     */
    public static function removeIncludePath() {
		foreach (func_get_args() as $path) {
			$paths = explode(DS, get_include_path());
			if (($k = array_search($path, $paths)) !== false)
				unset($paths[$k]);
			else
				continue;
			if (!count($paths)) {
				trigger_error(
						"Include path '{$path}' can not be removed because it is the only",
						E_USER_NOTICE);
				continue;
			}
			set_include_path(implode(PATH_SEPARATOR, $paths));
		}
	}

	public static function getOSInfo() {
		if (!self::$_osInfo) {
			self::$_osInfo = new OSInfo();
			self::$_osInfo->distro = self::getLinuxDistname();
		}
		return self::$_osInfo;
	}

	public static function isLinuxCompat() {
		return self::isLinux();
	}

	public static function isLinux() {
		return self::getOSInfo()->linux;
	}

	public static function isWindows() {
		return self::getOSInfo()->windows;
	}

	public static function isWebContext() {
		return CGAF_CONTEXT === self::WebContext;
	}

	public static function getLinuxDistname() {
		$distname = '';
		$distver = '';
		$distid = '';
		$distbaseid = '';
		$descr = '';
		try {
			// ** Debian or Ubuntu
			if (@file_exists('/etc/debian_version')) {
				if (is_file('/etc/lsb-release')) {
					$s = Utils::parseIni('/etc/lsb-release');
					$s = $s['Default'];
					$distname = $s["DISTRIB_ID"];
					$distver = $s["DISTRIB_RELEASE"];
					$distid = $s["DISTRIB_CODENAME"] . ' - '
							. $s['DISTRIB_DESCRIPTION'];
					$distbaseid = 'debian';
					$ver = Utils::sysexec('uname -a', null, true);
					$ver = $ver[0];
					$descr = $ver;
				} elseif (trim(file_get_contents('/etc/debian_version'))
						== '4.0') {
					$distname = 'Debian';
					$distver = '4.0';
					$distid = 'debian40';
					$distbaseid = 'debian';
					$descr = "Operating System: Debian 4.0 or compatible\n";
				} elseif (strstr(
						trim(file_get_contents('/etc/debian_version')), '5.0')) {
					$distname = 'Debian';
					$distver = 'Lenny';
					$distid = 'debian40';
					$distbaseid = 'debian';
					$descr = "Operating System: Debian Lenny or compatible\n";
				} elseif (strstr(
						trim(file_get_contents('/etc/debian_version')), '6.0')
						|| trim(file_get_contents('/etc/debian_version'))
								== 'squeeze/sid') {
					$distname = 'Debian';
					$distver = 'Squeeze/Sid';
					$distid = 'debian40';
					$distbaseid = 'debian';
					$descr = "Operating System: Debian $distver or compatible\n";
				} else {
					$distname = 'Debian';
					$distver = 'Unknown';
					$distid = 'debian40';
					$distbaseid = 'debian';
					$descr = "Operating System: Debian or compatible, unknown version.\n";
				}
			}
 elseif (file_exists("/etc/SuSE-release")) {
				// ** OpenSuSE
				if (stristr(file_get_contents('/etc/SuSE-release'), '11.0')) {
					$distname = 'openSUSE';
					$distver = '11.0';
					$distid = 'opensuse110';
					$distbaseid = 'opensuse';
					$descr = "Operating System: openSUSE 11.0 or compatible\n";
				} elseif (stristr(file_get_contents('/etc/SuSE-release'),
						'11.1')) {
					$distname = 'openSUSE';
					$distver = '11.1';
					$distid = 'opensuse110';
					$distbaseid = 'opensuse';
					$descr = "Operating System: openSUSE 11.1 or compatible\n";
				} elseif (stristr(file_get_contents('/etc/SuSE-release'),
						'11.2')) {
					$distname = 'openSUSE';
					$distver = '11.1';
					$distid = 'opensuse110';
					$distbaseid = 'opensuse';
					$descr = "Operating System: openSUSE 11.2 or compatible\n";
				} else {
					$distname = 'openSUSE';
					$distver = 'Unknown';
					$distid = 'opensuse110';
					$distbaseid = 'opensuse';
					$descr = "Operating System: openSUSE or compatible, unknown version.\n";
				}
			} // ** Redhat
 elseif (file_exists("/etc/redhat-release")) {
				$content = file_get_contents('/etc/redhat-release');
				if (stristr($content, 'Fedora release 9 (Sulphur)')) {
					$distname = 'Fedora';
					$distver = '9';
					$distid = 'fedora9';
					$distbaseid = 'fedora';
					$descr = "Operating System: Fedora 9 or compatible\n";
				} elseif (stristr($content, 'Fedora release 10 (Cambridge)')) {
					$distname = 'Fedora';
					$distver = '10';
					$distid = 'fedora9';
					$distbaseid = 'fedora';
					$descr = "Operating System: Fedora 10 or compatible\n";
				} elseif (stristr($content, 'Fedora release 10')) {
					$distname = 'Fedora';
					$distver = '11';
					$distid = 'fedora9';
					$distbaseid = 'fedora';
					$descr = "Operating System: Fedora 11 or compatible\n";
				} elseif (stristr($content, 'CentOS release 5.2 (Final)')) {
					$distname = 'CentOS';
					$distver = '5.2';
					$distid = 'centos52';
					$distbaseid = 'fedora';
					$descr = "Operating System: CentOS 5.2 or compatible\n";
				} elseif (stristr($content, 'CentOS release 5.3 (Final)')) {
					$distname = 'CentOS';
					$distver = '5.3';
					$distid = 'centos53';
					$distbaseid = 'fedora';
					$descr = "Operating System: CentOS 5.3 or compatible\n";
				} else {
					$distname = 'Redhat';
					$distver = 'Unknown';
					$distid = 'fedora9';
					$distbaseid = 'fedora';
					$descr = "Operating System: Redhat or compatible, unknown version.\n";
				}
			} // ** Gentoo
 elseif (file_exists("/etc/gentoo-release")) {
				$content = file_get_contents('/etc/gentoo-release');
				preg_match_all('/([0-9]{1,2})/', $content, $version);
				$distname = 'Gentoo';
				$distver = $version[0][0] . $version[0][1];
				$distid = 'gentoo';
				$distbaseid = 'gentoo';
				$descr = "Operating System: Gentoo $distver or compatible\n";
			}
		} catch (Exception $e) {
		}
		return array(
				'name' => $distname,
				'version' => $distver,
				'id' => $distid,
				'baseid' => $distbaseid,
				'description' => $descr
		);
	}

	public static function Initialize() {
		if (self::$_initialized) {
			return;
		}
		self::$_initialized = true;
		CGAF::Using('System.Interfaces.*');
		return;
	}

	public static function getRemoteAddress() {
		if (self::isConsole()) {
			return 'localhost';
		}
		return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
	}
	/**
	 * @deprecated dl since php5.3
	 * @static
	 * @param $ext
	 * @return bool
	 * @throws System\Exceptions\SystemException
	 *
	 */

	public static function loadExtenstion($ext) {
		if (extension_loaded($ext)) {
			return true;
		}
		if (function_exists('dl')) {
			$prefix = (PHP_SHLIB_SUFFIX === 'dll') ? 'php_' : '';
			
			@dl($prefix . $ext . '.' . PHP_SHLIB_SUFFIX);
			if (extension_loaded($ext)) {
				
			}
		} else {
			throw new SystemException(
					'Unable to load extension ' . $ext
							. ',dynamic loading extension not allowed by system');
		}
		if (extension_loaded($ext)) {
			return true;
		}
		return false;
	}

	private static function tryGetTempDir() {
		// Try to get from environment variable
		if (!empty($_ENV['TMP'])) {
			$path = realpath($_ENV['TMP']);
		} else if (!empty($_ENV['TMPDIR'])) {
			$path = realpath($_ENV['TMPDIR']);
		} else if (!empty($_ENV['TEMP'])) {
			$path = realpath($_ENV['TEMP']);
		} else {
			// Detect by creating a temporary file
			// Try to use system's temporary directory
			// as random name shouldn't exist
			$temp_file = tempnam(md5(uniqid(rand(), TRUE)), '');
			if ($temp_file) {
				$temp_dir = realpath(dirname($temp_file));
				unlink($temp_file);
				$path = $temp_dir;
			} else {
				return CGAF::getInternalStorage('.cache/tmp/', true);
			}
		}
		return $path;
	}

	public static function getCurrentUser() {
		static $u;
		if ($u) {
			return $u;
		}
		if (function_exists('posix_getpwuid')) {
			$user = posix_getpwuid(posix_geteuid());
			$groupinfo = posix_getgrgid(posix_getegid());
			$u = array(
					'username' => $user['name'],
					'groups' => $groupinfo['name']
			);
		}
		return $u;
	}

	public static function iniset($set, $value = null) {
		if (is_array($set) || is_object($set)) {
			foreach ($set as $k => $v) {
				ini_set($k, $v);
			}
			return;
		}
		ini_set($set, $value);
	}

	public static function getTempDir() {
		//$path = '';
		if (!function_exists('sys_get_temp_dir')) {
			$path = self::tryGetTempDir();
		} else {
			$path = sys_get_temp_dir();
			if (is_dir($path)) {
				return $path;
			} else {
				$path = self::tryGetTempDir();
			}
		}
		return $path;
	}
}
