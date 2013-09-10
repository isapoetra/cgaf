<?php
namespace System\Collections\Items;

use AppManager;
use Logger;
use System\Assets\AssetHelper;
use Utils;

class AssetItem extends \BaseObject implements \IRenderable, \IItem
{
    private $_resource;
    private $_liveResource = false;
    private $_group = null;
    //private $_type = 'js';
    private $_appOwner = null;
    private $_type;

    function __construct($resource, $group = null, $appOwner = null, $type = null)
    {
        parent::__construct();

        $this->_appOwner = $appOwner ? $appOwner : \AppManager::getInstance();
        $this->_type = $type;
        $this->Resource = $resource;
        $this->_group = $group;

    }

    function getResource()
    {
        return $this->_resource;
    }

    private function isType($type)
    {
        $ext = Utils::getFileExt($this->_resource, false);
        if ((strpos($ext, '/') !== false)) {
            return strpos($ext, $type) !== false || $this->_type === $type;
        }
        return strtolower($ext) === $type || $this->_type === $type;
    }

    /**
     * @param $res
     * @param $type
     * @return array|null|AssetItem
     */
    private function _getLiveResourceBytype($res, $type)
    {
        if (is_array($res)) {
            $retval = array();
            foreach ($res as $r) {
                $retval [] = $this->_getLiveResourceBytype($r, $type);
            }
            return $retval;
        } elseif (is_string($res)) {

            if (strpos($res, '.' . $type) !== false || $this->isType($type)) {
                //pp($res.$type);
                return new AssetItem ($res, $this->_group, $this->_type);
            }
        }
        return null;
    }

    function getLiveResourceByType($type)
    {
        $res = $this->LiveResource;
        return $this->_getLiveResourceByType($res, $type);
    }

    function equals($item)
    {
        if ($item instanceof AssetItem) {
            return $this->LiveResource === $item->LiveResource;
        }
        return $this->LiveResource === $item;
    }

    function setResource($value)
    {
        $this->_resource = $value;
        $this->_liveResource = false;
        if (strpos($value, '://') !== false) {
            $this->_liveResource = $value;
        }
        $this->getLiveResource();

    }

    function getLiveResource()
    {
        if ($this->_liveResource === false) {
            if (strpos($this->_resource, 'http:')) {
                ppd($this->_resource);
            }
            $this->_liveResource = $this->_appOwner->getLiveAsset($this->_resource);
            if (!$this->_liveResource) {
                Logger::Warning("Unable to Find resource for live " . $this->_resource);
            }
        }
        return $this->_liveResource;
    }

    function Render($return = false)
    {
        if (!$this->LiveResource) {
            pp($this->_resource);
            Logger::Warning('Unable to find asset live for ' . $this->_resource);
            return null;
        }
        $attr = array();
        if ($this->_group) {
            if (is_string($this->_group)) {
                $attr ['id'] = $this->_group;
            } else {
                $attr = $this->_group;
            }
        }
        $retval = AssetHelper::renderAsset($this->LiveResource, $attr);
        if (!$return) {
            \Response::write($retval);
        }
        return $retval;
    }
}
