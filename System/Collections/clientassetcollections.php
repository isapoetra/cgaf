<?php
namespace System\Collections;

use System\Applications\IApplication;
use System\Assets\AssetHelper;
use System\Collections\Items\AssetItem;

class ClientAssetCollections extends Collection implements \IRenderable
{
    private $_appOwner;

    function __construct(IApplication $owner)
    {
        parent::__construct(null, false, "System\\Collections\\Items\\AssetItem");
        $this->_appOwner = $owner;
    }

    /**
     * @param $item
     * @return bool
     */
    function contains($item)
    {
        if (is_string($item)) {
            foreach ($this as $v) {
                if ($v->Resource == $item) {
                    return true;
                }
            }
            return false;
        }
        return parent::contains($item);
    }

    /**
     * @param mixed $item
     * @param null $group
     * @param null $type
     * @return int
     */
    function add($item, $group = null, $type = null)
    {
        if (is_array($item)) {
            foreach ($item as $value) {
                $this->add($value, $group, $type);
            }
            return $this->_c - 1;
        } elseif (is_string($item)) {
            return $this->add(new AssetItem($item, $group, $this->_appOwner, $type));
        }
        return parent::add($item);
    }

    /**
     * @param bool $return
     * @param null $type
     * @param bool $clear
     * @return string
     */
    function Render($return = false, $type = null, $clear = false)
    {
        $retval = '';
        /**
         * @var \System\Collections\Items\AssetItem $v
         */
        foreach ($this->toArray() as $v) {
            if ($type) {
                $item = $v->getLiveResourceByType($type);
                if ($item) {
                    $retval .= AssetHelper::renderAsset($item);
                }
            } else {
                $retval .= $v->Render(true);
            }
        }
        if ($clear) {
            $this->clear();
        }
        return $retval;
    }
}