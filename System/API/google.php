<?php
class googleApi {
	public  static function plusOne($size='small') {
		AppManager::getInstance()->addClientAsset('https://apis.google.com/js/plusone.js');		
		return '<g:plusone size="'.$size.'"></g:plusone>';
	}
}