<?php
#using('System.Web.Utils');
abstract class WebUtils {
	public static $_lastCSS;
	private static $_cssCompressor;
	public static function pastHead() {		
		header ( 'Expires: Mon, 03 Sept 1977 05:00:00 GMT' ); // Date in the past
		header ( 'Last-Modified: ' . gmdate ( 'D, d M Y H:i:s' ) . ' GMT' ); // always modified
		header ( 'Cache-Control: no-cache, must-revalidate, no-store, post-check=0, pre-check=0' ); // HTTP/1.1
		header ( 'Pragma: no-cache' ); // HTTP/1.0
		header ( 'Content-type: text/html' );
	}
	private static function _processCSSUriCB($m) {

		$isImport = ($m [0] [0] === '@');
		// determine URI and the quote character (if any)
		if ($isImport) {
			$quoteChar = $m [1];
			$uri = $m [2];
		} else {
			// $m[1] is either quoted or not
			$quoteChar = ($m [1] [0] === "'" || $m [1] [0] === '"') ? $m [1] [0] : '';
			$uri = ($quoteChar === '') ? $m [1] : substr($m [1], 1, strlen($m [1]) - 2);
		}
		if ('/' !== $uri [0] && false === strpos($uri, '//') && 0 !== strpos($uri, 'data:')) {
			$nval = AppManager::getInstance()->getLiveData($uri);

			if (! $nval) {
				$nval = AppManager::getInstance()->getLiveData(self::$_lastCSS . Utils::ToDirectory($uri));
			}
			if ($nval) {
				$uri = $nval; //str_replace ( BASE_URL, '', $nval );
			}
		}
		return $isImport ? "@import {$quoteChar}{$uri}{$quoteChar}" : "url({$quoteChar}{$uri}{$quoteChar})";
	}

	private static function rewriteCSSURL(&$content) {
		$parsed = array ();
		// rewrite
		$content = preg_replace_callback('/@import\\s+([\'"])(.*?)[\'"]/', array (
				'WebUtils',
				'_processCSSUriCB'
		), $content);

		$content = preg_replace_callback('/url\\(\\s*([^\\)\\s]+)\\s*\\)/', array (
				'WebUtils',
				'_processCSSUriCB'
		), $content);

	}

	public static function parseCSS($css, $file = false, $pack = true) {

		if ($pack == null) {
			$pack = ! CGAF_DEBUG;
		}

		$content = "";
		$retval = "";
		if (is_array($css)) {
			foreach ( $css as $k => $v ) {
				self::$_lastCSS = dirname($v) . DS;
				$retval .= "\n/*" . basename($v) . "*/\n" . self::parseCSS(file_get_contents($v), false, false);
			}

			if ($pack) {
				$retval = self::packCSS($retval);
			}
			return $retval;
		} elseif (is_string($file)) {

			self::$_lastCSS = dirname($css) . DS;
			$content = $file;
			$file = true;
			if (is_string($pack)) {
				$pack = ! CGAF_DEBUG;
			}
		} else {
			if ($file) {
				$content = file_get_contents($css);
				self::$_lastCSS = dirname($css) . DS;
			} else {
				$content = $css;
			}
		}

		if ($content) {
			self::rewriteCSSURL($content);
		}

		if ($pack) {
			//die('x');			
			$content = self::PackCSS($content);
		}
		return $content;
	}

	public static function PackCSS($css) {
		$css = str_replace('@CHARSET "UTF-8";', '', $css);		
		if (! self::$_cssCompressor) {
			using('libs.minify');
			self::$_cssCompressor = new CSSCompresor($css);
		} else {
			self::$_cssCompressor ->setContent($css);
		}
		return self::$_cssCompressor ->Compress();
	}

	/**
	 * Process a font-family listing and return a replacement
	 *
	 * @param array $m regex matches
	 *
	 * @return string
	 */
	protected static function _cssfontFamilyCB($m) {
		$m [1] = preg_replace('/
                \\s*
                (
                    "[^"]+"      # 1 = family in double qutoes
                    |\'[^\']+\'  # or 1 = family in single quotes
                    |[\\w\\-]+   # or 1 = unquoted family
                )
                \\s*
            /x', '$1', $m [1]);
		return 'font-family:' . $m [1] . $m [2];
	}

	public static function sendMail($to,$m, $template, $subject = "Notification") {

		$app = AppManager::getInstance();

		$tpl = $app->getInternalData("template/email/$template.html");
		if ($tpl) {
			$o = new stdClass();
			if ($m instanceof DBTable) {
				$arr = $m->getFields(true, true, false);
				$o = Utils::bindToObject($o, $arr, true);
			} elseif (is_array($m)) {
				$o = Utils::bindToObject($o, $m, true);
			} elseif (is_object($m)) {
				$o = $m;
			}
			$o->title = __($subject);
			$o->base_url = BASE_URL;
			$msg = Utils::parseDBTemplate(file_get_contents($tpl), $o);

			return MailUtils::send($to, $subject, $msg, true);
		} else {
			throw new SystemException('mail.template.notfound');
		}
	}
}
