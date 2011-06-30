<?php

class JQGrid extends JQControl {
	private $_rowperpage;
	private $_currentPage;
	private $_model;
	private $_baseURL;
	private $_columns;
	private $_actions = array ();
	private $_renderDefaultAction = false;
	private $_autoGenerateColumn = false;
	private $_baseLang;
	private $_navOptions = array ();
	private $_sortName;
	private $_callback;
	private $_openEditInOverlay = true;
	private $_directConfig = array (
			'loadComplete',
			'loadSuccess'
	);
	private $_route;
	function __construct($id, $template, $model, $columns = null, $baseurl = null) {
		parent::__construct($id, $template);
		$this->_rowperpage = Request::get("_rp", 10);
		$this->_currentPage = ( int ) Request::get("_page", 0);
		$this->_model = $model;
		$this->_route = MVCHelper::getRoute();
		$this->_columns = $columns;
		if (! $columns) {
			$this->_autoGenerateColumn = true;
		}
		$this->_baseURL = $baseurl ? $baseurl : Request::getOrigin();
	}

	function setNavigationItem($items) {
		$this->_navItems = $items;
	}

	function setopenEditInOverlay($value) {
		$this->_openEditInOverlay = $value;
	}

	function setBaseLang($lang) {
		$this->_baseLang = $lang;
	}

	function setRenderDefaultAction($value) {
		$this->_renderDefaultAction = $value;
	}

	function setAutoGenenerateColumn($value) {
		$this->_autoGenerateColumn = $value;
	}

	function addAction($action) {
		$this->_actions [] = $action;
	}

	function setCallback($callback) {
		$this->_callback = $callback;
		return $this;
	}

	function parseValue($row, $value, $field) {
		static $pk;
		if (! $pk) {
			$pk = $this->_model->getPK();
		}

		$retval = $value;
		foreach ( $row as $k => $v ) {
			$retval = str_ireplace("#$k#", htmlentities($v), $retval);
		}

		$pkval = array ();
		foreach ( $pk as $k => $p ) {
			if (! is_numeric($k)) {
				if ($p == $k) {
					$pkval [] = $row->$p;
				}
			} else {
				if ($p == $p) {
					$pkval [] = $row->$p;
				}
			}
		}
		$retval = str_ireplace("#PK_VALUE#", implode(",", $pkval), $retval);
		if ($this->_callback) {
			$retval = call_user_func_array($this->_callback, array (
					$field,
					$row,
					$value,
					$retval
			));
		}
		return $retval;

	}
	function setRoute($c) {
		$this->_route = $c;
	}
	protected function getDefaultAction() {
		$route = $this->_route;
		$ctl = AppManager::getInstance()->getController($route ["_c"]);
		if ($ctl) {
			$field = $this->_model->getPKValue();
			$baseurl = BASE_URL . "/" . $route ["_c"];
			$retval = array ();
			if ($ctl->isAllow('detail')) {
				$retval [] = HTMLUtils::renderLink($baseurl . '/detail/?id=#PK_VALUE#', 'Detail', null, 'detail-small.png');
			}

			if ($ctl->isAllow('edit',$field)) {
				$retval [] = HTMLUtils::renderLink($baseurl . '/edit/?id=#PK_VALUE#', 'Edit', null, 'edit-small.png');
			}
			if ($ctl->isAllow(ACLHelper::ACCESS_MANAGE)) {
				$retval [] = HTMLUtils::renderLink($baseurl . '/del/?id=#PK_VALUE#', 'Delete', array (
						'rel' => '#confirm',
						'title' => __('delete.confirm', 'Delete this data')
				), 'del-small.png');
			}
		}
		return $retval;
	}

	protected function renderJSON($return = true) {
		//$fields = $this->_model->getFields(false);
		$page = ( int ) Request::get("page", 0);
		$rpp = ( int ) Request::get("rows", 10);
		$ob = Request::get("sidx", "date_added", false);
		$o = Request::get("sord", "asc");
		$search = Request::get("_search", 'false') == 'true';
		$alias = $this->_model->getAlias();
		$this->_model->resetgrid($this->getId())->orderby("$alias.$ob $o");

		$where = null;
		if ($search) {
			$operator = array (
					"eq" => "=",
					'ne' => '<>',
					'lt' => '<',
					'gt' => '>',
					'ge' => '>=',
					'le' => '<='
			);
			$ope = Request::get("searchOper", "eq");
			$ss = Request::get("searchString");
			$sf = $this->_model->quoteTable(Request::get("searchField", null, false));
			$prefix = "";
			if (array_key_exists($ope, $operator)) {
				$ope = $operator [$ope];
				$ss = $this->_model->quote($ss);
			} else {
				switch ($ope) {
					case 'bw' :
						$ope = 'like';
						$ss = $this->_model->quote($ss . '%');
						break;
					case "bn" :
						$ope = 'like';
						$ss = $this->_model->quote($ss . '%');
						$prefix = "not";
						break;
					case "en" :
						$prefix = "not";
					case 'ew' :
						$ope = 'like';
						$ss = $this->_model->quote('%' . $ss);
						break;
					case "ni" :
						$prefix = 'not';
					case "in" :
						$ope = "in ";
						$ss = '(' . $this->_model->quote($ss) . ')';
						break;
					case 'nc' :
						$prefix = 'not';
					case 'cn' :
						$ope = 'like';
						$ss = $this->_model->quote('%' . $ss . '%');
						break;
					default :
						$ss = $this->_model->quote($ss);
						break;
				}
			}
			$where = "$prefix ($sf $ope $ss)";
			$this->_model->where($where);
		}
		try {
			$all = $this->_model->loadAll($page - 1, $rpp);
		} catch ( Exception $e ) {
			$this->_model->clear('orderby')->orderby("$ob $o");
			$all = $this->_model->loadAll($page - 1, $rpp);
		}
		//ppd($this->_model->lastSQL());
		$rows = array ();
		$idx = 0;
		foreach ( $all as $x ) {
			$row = array ();

			foreach ( $this->_columns as $k => $col ) {
				$val = null;
				if ($k === "__action") {
					$val = $this->parseValue($x, $col, $k);
				} else {
					if (is_array($col)) {
						if (isset($col ['eval'])) {
							$val = $this->parseValue($x, $col ['eval'], $k);
							eval("\$val = (" . $val . ');');
						}
					}
					if ($val == null) {
						$col = is_array($col) ? (isset($col ["value"]) ? $col ["value"] : "#$k#") : $col;

						if (is_numeric($k) && is_string($col) && ! strpos($col, '#')) {
							$k = $col;
							$col = "#$col#";
						}
						$val = $this->parseValue($x, $col, $k);
					}

				}
				if (is_array($val)) {
					$row [] = implode('&nbsp;', $val);
				} else {
					$row [] = $val;
				}
			}
			//$rows [$idx] ['forceFit'] = true;
			$rows [$idx] ['id'] = $all = $this->_model->getPKValue(false, $x);
			$rows [$idx] ['cell'] = $row;
			$idx ++;
		}
		$count = $this->_model->getRowCount();
		if ($count > 0) {
			$total_pages = ceil($count / $rpp);
		} else {
			$total_pages = 0;
		}

		$retval = new stdClass();
		$retval->records = $count;
		$retval->page = $page;
		$retval->total = $total_pages;
		$retval->rows = $rows;
		return json_encode($retval);
	}

	function getSortName() {
		if (! $this->getConfig('sortname')) {
			foreach ( $this->_columns as $k => $col ) {
				if ($k == '__action')
					continue;
				$this->setConfig('sortname', $k);
			}
		}
		return $this->getConfig('sortname');
	}

	function prepareRender() {
		if (! $this->_model) {
			throw new SystemException('unable to initialize grid,Model not found');
		}
		$id = $this->getId();

		$this->_columns = $this->_columns ? $this->_columns : array ();
		if ($this->_autoGenerateColumn) {
			$columns = $this->_model->getGridColumns($this->getId());
			$this->_columns = array_merge($columns, $this->_columns);
		}

		if ($this->_renderDefaultAction) {
			$act = $this->getDefaultAction();
			if (isset($this->_columns ["__action"])) {
				$this->_columns ["__action"] = array_merge_recursive($act, $this->_columns ["__action"]);
			} else {
				$this->_columns ["__action"] = $act;
			}
		}

		$cols = array ();
		$colmodels = array ();
		$hasaction = false;
		foreach ( $this->_columns as $k => $col ) {
			$title = is_array($col) ? (isset($col ["title"]) ? $col ["title"] : $k) : (is_numeric($k) ? $col : $k);
			$title = __(($this->_baseLang ? $this->_baseLang . '.' :''). $title, $title);
			$sort = true;
			$fit = true;
			$fixed = false;
			$editable = true;
			$width = is_array($col) ? (isset($col ['width']) && $col ['width'] ? $col ['width'] : 50) : 50;

			if ($k !== "__action") {
				$cols [] = $title;
				$models = array (
						'name' => $k,
						'label' => $k,
						'index' => $k,
						'editable' => $editable,
						'sortable' => $sort,
						'width' => $width
				);
				if (is_array($col) && isset($col ['colmodel'])) {
					$models = array_merge($models, $col ['colmodel']);
				}
				$colmodels [] = $models;
			} else {
				$hasaction = array (
						'name' => $k,
						'label' => $k,
						'index' => $k,
						'editable' => false,
						'sortable' => false,
						'width' => 250
				);
			}
		}
		if ($hasaction) {
			$cols [] = "Actions";
			$colmodels [] = $hasaction;
			$old = $this->_columns ['__action'];
			unset($this->_columns ['__action']);
			$this->_columns ['__action'] = $old;
		}
		$params = array (
				'_json',
				'1'
		);
		$params = array_merge(Request::gets(), $params);

		$baseurl = URLHelper::addParam($this->_baseURL, array (
				'_grid' => $id
		));
		$editurl = $this->getConfig("editurl", URLHelper::addParam($baseurl, array (
				'_gridAction' => 'edit'
		)));
		$addurl = $this->getConfig("addurl", URLHelper::addParam($baseurl, array (
				'_gridAction' => 'add'
		)));
		$dataurl = $this->getConfig("dataurl", URLHelper::add($baseurl, null, $params));

		$route = MVCHelper::getRoute();
		$ctl = AppManager::getInstance()->getController($route ["_c"]);

		$navConfig = array (
				'add' => $ctl ? $ctl->isAllow(ACLHelper::ACCESS_WRITE) : true,
				'edit' => $ctl ? $ctl->isAllow(ACLHelper::ACCESS_UPDATE) : true,
				'del' => $ctl ? $ctl->isAllow(ACLHelper::ACCESS_MANAGE) : true,
				'editfunc' => 'function(row) {var gr = jQuery("#' . $id . '").jqGrid(\'getGridParam\',\'selrow\'); ' . ($this->_openEditInOverlay ? '$.openOverlay({url:\'' . $editurl . '&id=\'+gr})' : 'document.location=\'' . $editurl . '&id=\'+gr;') . '}',
				'addfunc' => 'function(row) { ' . ($this->_openEditInOverlay ? '$.openOverlay({url:\'' . $addurl . '\',onClosed:function(){$(\'#' . $this->getId() . '\').trigger(\'reloadGrid\')}}) ' : 'document.location=\'' . $addurl . '\'') . '}'
		);

		$this->setNavConfig($navConfig, null, false);
		$this->setConfig(array (
				'forceFit' => False,
				'shrinkToFit' => False,
				'sortname' => $this->getSortName(),
				'sortorder' => 'desc',
				'rowList' => array (
						10,
						20,
						30,
						50,
						100
				),
				'url' => $dataurl,
				'editurl' => $editurl,
				'datatype' => 'json',
				'colNames' => $cols,
				'colModel' => $colmodels,
				'viewrecords' => true,
				'rowNum' => $this->_rowperpage,  //$this->_model->getRowCount(),
				'pager' => "#$id-pager"
		), null, false);
		foreach ( $this->_configs as $k => $config ) {
			if (is_string($config) && $config [0] === '$') {
				$this->_directConfig [] = $k;
			}
		}
	}

	function setNavConfig($configName, $value = null, $overwrite = true) {
		if (is_array($configName)) {
			foreach ( $configName as $k => $v ) {
				$this->setNavConfig($k, $v, $overwrite);
			}
		} else {
			if ($overwrite || ! isset($this->_navOptions [$configName])) {
				$this->_navOptions [$configName] = $value;
			}
		}
		return $this;
	}

	private function getCols() {
	}

	public static function loadScript($appOwner = null) {
		if ($appOwner === null) {
			$appOwner = AppManager::getInstance();
		}
		$tpl = $appOwner->getTemplate();
		$tpl->addAsset('jqGrid/js/i18n/grid.locale-' . $appOwner->getLocale()->getLocale() . '.js', null, 'grid-locale');
		$tpl->addAsset('jqGrid/jqGrid.xml', null, 'jqGrid');

	}

	function RenderScript($return = true) {
		$id = $this->getId();
		$cols = array ();
		if (! Request::isAJAXRequest()) {
			self::loadScript($this->getTemplate()->getAppOwner());
		}

		$configs = JSON::encodeConfig($this->_configs, $this->_directConfig);
		$apid = String::replace(array (
				'-' => ''
		), $id) . 'api';

		$scripts = "var {$apid}=$(\"#$id\").jqGrid($configs);";

		$scripts .= "$(\"#$id\").jqGrid('navGrid','#$id-pager'," . JSON::encodeConfig($this->_navOptions, array (
				'editfunc',
				'addfunc'
		)) . ");";
		$scripts .= "var w={$apid}.closest('.ui-jqgrid').parent().parent().innerWidth()-50;";
		$scripts .= "{$apid}.setGridWidth(w,false);";
		$scripts .= "{$apid}.setGridHeight({$apid}.closest('.ui-jqgrid').parent().parent().innerHeight());";

		$retval = "<table id=\"$id\"></table><div id=\"$id-pager\"></div>";
		if (! $return) {
			$this->getTemplate()->addClientScript($scripts);
			Response::write($retval);
		} else {
			$retval .= HTMLUtils::renderScript($scripts);
		}
		return $retval;
	}
}