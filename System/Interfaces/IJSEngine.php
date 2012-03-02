<?php
use System\Applications\IWebApplication;
interface IJSEngine {
  /**
   * @abstract
   * @param System\Applications\IWebApplication $app
   * @return bool
   */
  public function initialize(IWebApplication &$app);
}
