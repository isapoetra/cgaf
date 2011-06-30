<?php
final class Streamer {
	public static function RenderImage($file) {
		$finfo =new FileInfo($file);
		Header('Content-Type: ',$info->Mime);
		readfile($file);

	}
	public static function RenderFile($file) {
		$finfo =new FileInfo($file);
		header('Content-type: '.$finfo->Mime);
		readfile($file);
	}
	public static function Render($file) {
		$ext = Utils::getFileExt($file,false);
		if (Utils::isImage($ext)) {
			return self::RenderImage($file);
		}else {
			return self::RenderFile($file);
		}
	}

}