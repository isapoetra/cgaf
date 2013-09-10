<?php
namespace System\Web\UI\Ext;

use System\Collections\Collection;

class ItemCollections extends Collection
{
    function __construct()
    {
        parent::__construct();
        $this->setItemClass(__NAMESPACE__ . '\\Component');
    }
}