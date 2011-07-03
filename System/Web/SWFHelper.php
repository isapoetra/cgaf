<?php

abstract class SWFHelper {

	private static function SWFToolsPath() {
		static $path;
		if ($path === null) {
			$path = CGAF::getConfig('SWFToolsPath');
		}
		return $path;
	}

	private static function getCommand($cmd) {
		$f = Utils::ToDirectory(self::SWFToolsPath() . DS . $cmd);
		return Utils::QuoteFileName($f);
	}

	public static function swfRender($source, $dest) {
		if (is_file($source)) {
			$pdfCmdLIne = self::getCommand('swfrender');
			$cmd = sprintf("$pdfCmdLIne %s -o %s", Utils::QuoteFileName($source), Utils::QuoteFileName($dest));
			$retval=0;
			@system($cmd,$retval);
			if ($retval != 0) {
				return false;
			}
			return $dest;

		}
		return null;
	}

	public static function pdf2swf($source, $dest, $maxPage = null) {
		//$pdfCmdLIne = Utils::QuoteFileName(CGAF::getConfig('PDF2SWFCommandLine', "pdf2swf"));
		$pdfCmdLIne = self::getCommand('pdf2swf');
		$options = '-f -z -G -T 9'.(CGAF_DEBUG ? ' -v' : '') . ($maxPage !== null ? ' -p 1-' . $maxPage : '');
		$logFile = Utils::ToDirectory(CGAF_PATH . '/log/pdf2swf.log');
		Utils::makeDir(dirname($logFile));

		$cmd = sprintf("$pdfCmdLIne  %s -o %s $options >> %s", Utils::QuoteFileName($source), Utils::QuoteFileName($dest), Utils::QuoteFileName($logFile));
		file_put_contents($logFile,$cmd,FILE_APPEND);
		$out=null;
		$r=0;
		@exec($cmd,$out,$r);
		if ($r != 0) {
			if (CGAF_DEBUG) {
				throw new SystemException('error while converting file,see '.basename($logFile));
			}
			return false;
		}
		return $dest;
	}
}