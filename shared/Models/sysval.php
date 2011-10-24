<?php
class SysValModel extends MVCModel{
	public $sysval_id;
	public $sysval_key_id;
	public $sysval_title;
	public $sysval_value;
	function __construct() {
		parent::__construct(CGAF::getDBConnection(), "sysvals",array('syskey_id'),false);
	}
	function reset() {
		$this->setAlias("s");
		parent::reset();
		$this->clear("select");
		//$q->addTable('sysvals', "s");
		$this->leftJoin('syskeys', 'sk', 'syskey_id = sysval_key_id');
		$this->select('syskey_type, syskey_sep1, syskey_sep2, sysval_value');


	}
	function loadByTitle($title,$appId=null) {
		$this->Where("sk.app_id=".$this->quote($appId ? $appId : $this->getAppOwner()->getAppId()));
		$this->where("sysval_title =".$this->quote($title));
		return $this->loadObject();
	}
}