<?php
use System\Web\WebUtils;
use System\Streamer\FLV;
final class Streamer {
	public static function StreamString($string, $fileName = null, $mime = 'text/html') {
		header_remove('Content-type');
		if ($fileName) {
			header("Content-Disposition: attachment; filename=\"" . basename($fileName) . "\";");
		}
		header('Content-type: ' . $mime);
		header("Content-Length: " . strlen($string));
		echo $string;
		CGAF::doExit();
	}
	public static function Stream($file, $mime = null) {
		if (!is_readable($file)) {
			CGAF::doExit();
		}
		$ext = Utils::getFileExt($file, false);
		if (!$mime) {
			$finfo = new FileInfo($file);
			$mime = $finfo->Mime;
		}
		$content = null;
		switch ($ext) {
		case 'css':
			if (strpos($file, '.min.css') === false) {
				$content = WebUtils::parseCSS($file, true, FALSE);
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
		header_remove('Content-type');
		header('Content-type: ' . $mime);
		//header("Content-Disposition: attachment; filename=\"".basename($file)."\";" );
		//header("Content-Transfer-Encoding: binary");
		header("Content-Length: " . $fsize);
		//ppd(headers_list());
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
