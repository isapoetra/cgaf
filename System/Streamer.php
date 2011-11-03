<?php
use System\Web\WebUtils;
use System\Streamer\FLV;
use \FileInfo;
final class Streamer {
	public static function StreamString($string, $fileName = null, $mime = null) {
		header_remove('Content-type');
		if ($fileName) {
			header("Content-Disposition: attachment; filename=\"" . basename($fileName) . "\";");
		}
		if (!$mime) {
			$finfo = new FileInfo($fileName);
			$mime = $finfo->Mime;
		}
		header('Content-type: ' . $mime);
		header("Content-Length: " . strlen($string));
		echo $string;
		CGAF::doExit();
	}
	public static function Stream($file, $mime = null, $downloadmode = false) {
		if (!is_readable($file)) {
			CGAF::doExit();
		}
		$ext = Utils::getFileExt($file, false);
		if (!$mime) {
			$finfo = new FileInfo($file);
			$mime = $finfo->Mime;
		}
		//ppd($mime);
		$content = null;
		switch ($ext) {
		case 'css':
			if (!$downloadmode) {
				if (strpos($file, '.min.css') === false) {
					$content = WebUtils::parseCSS($file, true, FALSE);
				}
			}
			break;
		case 'png':
			break;
		case 'flv':
			$streamer = new FLV($file);
			$streamer->stream();
			CGAF::doExit();
			break;
		default:
			break;
		}
		$fsize = filesize($file);
		header('Content-Type: ' . $mime, true);
		header("Content-Length: " . $fsize);
		if ($downloadmode) {
			header('Content-Disposition: attachment; filename="' . basename($file) . '"');
		}
		if ($content) {
			echo $content;
		} else {
			$i = readfile($file);
		}
		CGAF::doExit();
	}
	public static function Render($file) {
		return self::Stream($file);
	}
}
