<?php
// PEAR Wrapper

class PEAR_Error {
}
class PEAR {
	private static $_initialized;
	public static function Init() {
		if (self::$_initialized) {
			return true;
		}

		//System::addIncludePath(dirname(__FILE__));
		System::addIncludePath(CGAF_VENDOR_PATH.DS.'PEAR/');
		//ppd(get_include_path());
		return self::$_initialized;
	}
	public static function load($package) {
		return CGAF::Using('Vendor.PEAR.'.$package,true);
	}
	public static function isError($data, $code = null) {
		if ($data instanceof PEAR_Error) {
			if (is_null($code)) {
				return true;
			} elseif (is_string($code)) {
				return $data->getMessage() == $code;
			} else {
				return $data->getCode() == $code;
			}
		}
		return false;
	}
	public static function raiseError($e,$level=null) {

		throw new Exception($e);

	}
}
PEAR::Init();
