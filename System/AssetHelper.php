<?php
final class AssetHelper {
	public static function renderAsset($assets) {
		
		if (is_array($assets)) {
			$retval ='';
			foreach ($assets as $asset) {
					
				$retval .= AssetHelper::renderAsset($asset);
			}
			return $retval;
		}


		if (is_object($assets) && $assets instanceof IRenderable) {
			return $asset->render(true);
		}elseif (is_string($assets)) {
			$ext = Utils::getFileExt($assets,false);
			switch (strtolower($ext)) {
				case "js":
					return '<script language="javascript" type="text/javascript" src="' . $assets . '"></script>';
				case "css":
					return '<link rel="stylesheet" type="text/css" media="all" href="' . $r . '"/>';
				default:
					throw new SystemException("Unhandle asset type ".$ext);
			}
		}
		return null;
	}
}