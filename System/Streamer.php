<?php
use System\Web\WebUtils;
final class Streamer {
	public static function Stream($file, $mime = null) {
		$ext = Utils::getFileExt($file, false);
		if (!$mime) {
			$finfo = new FileInfo($file);
			$mime = $finfo->Mime;
		}
		$content = null;
		switch ($ext) {
		case 'css':
			$content = WebUtils::parseCSS($file, true, FALSE);
			break;
		default:
			;
			break;
		}
		header('Content-type: ' . $mime);
		if ($content) {
			echo $content;
		} else {
			readfile($file);
		}
		CGAF::doExit();
	}
	public static function Render($file) {
		return self::Stream($file);
	}
}
