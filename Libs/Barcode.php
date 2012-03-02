<?php
class Barcode implements \IRenderable {
	const QR_CODE = 'qr';
	private static $_defaultQRConfig = array(
			'errorCorrectionLevel' => 0,
			'size' => 3,
			'margin' => 4,
			'outputFormat' => 'png',
			'outputFile' => null);
	function Render($return = false) {
	}
	private static function initQR() {
		if (!defined('QR_CACHEABLE')) {
			define('QR_CACHEABLE', true); // use cache - more disk reads but less CPU power, masks and format templates are stored there
			$cpath = \CGAF::getInternalCacheManager()->getCachePath('qr');
			define('QR_CACHE_DIR', $cpath); // used when QR_CACHEABLE === true
			define('QR_LOG_DIR', \Utils::makeDir($cpath . 'log/')); // default error logs dir
			define('QR_FIND_BEST_MASK', true); // if true, estimates best mask (spec. default, but extremally slow; set to false to significant performance boost but (propably) worst quality code
			define('QR_FIND_FROM_RANDOM', false); // if false, checks all masks available, otherwise value tells count of masks need to be checked, mask id are got randomly
			define('QR_DEFAULT_MASK', 2); // when QR_FIND_BEST_MASK === false
			define('QR_PNG_MAXIMUM_SIZE', 1024);
		}
	}
	public static function generateQRCode($data, $configs = array()) {
		using('Libs.qrcode.*');
		self::initQR();
		$configs = array_merge(self::$_defaultQRConfig, $configs);
		$enc = QRencode::factory($configs['errorCorrectionLevel'], $configs['size'], $configs['size']);
		switch ($configs['outputFormat']) {
		case 'png':
			$enc->encodePNG($data, $configs['outputFile'], false);
		}
	}
	public static function generate($type, $data, $configs = array()) {
		switch ($type) {
		case self::QR_CODE:
			return self::generateQRCode($data, $configs);
			break;
		default:
			;
			break;
		}
	}
}
