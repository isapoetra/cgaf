<?php
namespace System\JSON;
class JSONData extends JSONResponse {
	function __construct($data, $id, $count = null, $fields = null) {
		parent::__construct();
		$field = array();
		if (count($data)) {
			$field = $fields ? $fields : array_keys(get_object_vars($data[0]));
		}
		$this->results = $count ? $count : count($data);
		$this->metadata["fields"] = array();
		foreach ($field as $v) {
			if ($v !== 'msg') {
				$this->metadata["fields"][] = array(
						"name" => (is_array($v["name"]) ? $v["name"] : $v));
			}
		}
		$this->setIgnore(array(
						"redirect"));
		$this->metadata["id"] = $id;
		$this->rows = $data;
	}
}
?>