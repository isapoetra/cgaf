<?php

use System\Applications\IApplication;

interface IJSEngine
{
    /**
     * @abstract
     * @param System\Applications\IApplication $app
     * @return bool
     */
    public function initialize(IApplication &$app);
}
