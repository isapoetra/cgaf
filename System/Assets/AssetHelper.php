<?php
namespace System\Assets;
use System\Exceptions\SystemException;

use System\Web\JS\JSUtils;
use \CGAF;
use \Utils;
use \System\Configurations\Configuration;

final class AssetHelper
{
    public static function renderAsset($assets, $attr = null)
    {
        if (!$assets) {
            return;
        }
        if (is_array($assets)) {
            $retval = '';
            foreach ($assets as $asset) {
                $retval .= self::renderAsset($asset, $attr);
            }
            return $retval;
        }
        $retval = null;
        if (is_object($assets) && $assets instanceof \IRenderable) {
            $retval = $assets->render(true);
        } elseif (is_string($assets)) {
            $ext = Utils::getFileExt($assets, false);
            switch (strtolower($ext)) {
                case 'js':
                    $retval = JSUtils::renderJSTag($assets, true, $attr);
                    break;
                case 'css':
                    $retval = '<link rel="stylesheet" type="text/css" media="all" href="' . $assets . '"/>';
                    break;
                default:
                    if (stripos($assets, "js") !== false) {
                        $retval = JSUtils::renderJSTag($assets, true, $attr);
                        //'<script language="javascript" type="text/javascript" src="' . $assets . '"></script>';
                    } elseif (stripos($assets, "css") !== false) {
                        $retval = '<link rel="stylesheet" type="text/css" media="all" href="' . $assets . '"/>';
                    } else {
                        throw new SystemException("Unhandle asset type " . $ext);
                    }
            }
        }
        $retval = (CGAF_DEBUG ? PHP_EOL : '') . $retval;
        return $retval;
    }
}
