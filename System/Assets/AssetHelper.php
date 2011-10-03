<?php
namespace System\Assets;
use System\Web\JS\JSUtils;

use \CGAF;
use \Utils;
use \System\Configurations\Configuration;

final class AssetHelper {

	public static function renderAsset($assets) {
		if (!$assets) {
			return;
		}
		if (is_array($assets)) {
			$retval = '';
			foreach ($assets as $asset) {
				$retval .= self::renderAsset($asset);
			}
			return $retval;
		}

		if (is_object($assets) && $assets instanceof IRenderable) {
			return $asset->render(true);
		} elseif (is_string($assets)) {
			$ext = Utils::getFileExt($assets, false);
			switch (strtolower($ext)) {
			case 'js':
				return JSUtils::renderJSTag($assets);
			case 'css':
				return '<link rel="stylesheet" type="text/css" media="all" href="' . $assets . '"/>';
			default:
				if (stripos($assets, "js") !== false) {
					return JSUtils::renderJSTag($assets);
					//'<script language="javascript" type="text/javascript" src="' . $assets . '"></script>';
				} elseif (stripos($assets, "css") !== false) {
					return '<link rel="stylesheet" type="text/css" media="all" href="' . $assets . '"/>';
				}
				throw new SystemException("Unhandle asset type " . $ext);
			}
		}
		return null;
	}
}