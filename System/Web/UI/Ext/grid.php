<?php
namespace System\Web\UI\Ext;
class JExtGridComponent extends Component {

	function __construct() {
		parent::__construct ( array (
			"xtype" => "grid" ) );
	}
}

class Grid extends Control {
	public $mode = "js";
	protected $_defaultHeight = 250;
	protected $_data;
	protected $_column;
	protected $_fields;
	protected $_rowPerPage = 10;
	protected $_singleSelect = true;
	protected $_fieldId;
	protected $_autoFilter = true;
	protected $_winCallBack = "";
	protected $_RenderWhenNoData = false;
	protected $_reportString;
	protected $_viewMode = "window";

	protected $_showbutton = array (
		"add",
		"edit",
		"delete",
		"view" );

	function __construct($data = null, $column = null, $fields = null) {
		if ($this->_class == null)
		$this->_class = "Ext.grid.GridPanel";
		parent::__construct ( $this->_class );
		$this->addEventHandler ( "onrenderreport" );
		$this->_data = $data;
		$this->_column = $column;
		$this->_fields = $fields;


		//ppd($this->_controlScript);
		$this->addIgnoreConfigStr ( array (
			"getRowClass",
			"bbarplugins",
			"plugins",
			"sm",
			"renderer",
			"ds",
			"view" ) );
		$this->setConfigs ( array (
			"autoHeight" => true,
			"frame" => true,
			"autoScroll" => true ) );
		$this->_winCallBack = "{changed:function(){grid=Ext.getCmp('" . $this->_id . "');grid.store.load();}}";
		// stupid ie
		if (Request::ISIE ()) {
			$this->removeConfig ( "autoHeight" );
			$this->setConfig ( "height", $this->_defaultHeight, true );
		}
	}

	function setViewMode($value) {
		$this->_viewMode = $value;
	}

	function setReportString($value) {
		$this->_reportString = $value;
	}

	function getStore() {
		return $this->_config ["store"];
	}

	function setRenderWhenNoData($value) {
		$this->_RenderWhenNoData = $value;
	}

	function addShowButton($btn) {
		if (is_array ( $btn )) {
			foreach ( $btn as $b ) {
				$this->addShowButton ( $b );
			}
			return;
		}
		if (! in_array ( $btn, $this->_showbutton )) {
			$this->_showbutton [] = $btn;
		}
	}

	function setAutoFilter($value) {
		$this->_autoFilter = $value;
	}

	function setFiltersCols($value) {
		$arrfilter = array ();
		foreach ( $value as $v ) {
			if (is_object ( $v )) {
				$v = get_object_vars ( $v );
			}
			if (isset ( $v ["filter"] )) {
				$f = $v ["filter"];
				//boolean :D
				if (is_bool ( $f ) && $f) {
					$arrfilter [] = array (
						"type" => "string",
						"dataIndex" => $v ["dataIndex"] );
				} elseif (is_array ( $f )) {
					$arrfilter [] = $f;
				}
			} elseif ($this->_autoFilter && isset ( $v ["dataIndex"] )) {
				$arrfilter [] = array (
					"type" => "string",
					"dataIndex" => $v ["dataIndex"] );
			}
		}
		JExt::LoadPlugin ( 'gridfilters/GridFilters.js' );
		$filters = new JExtCustom ( "Ext.ux.grid.GridFilters", array (
			"filters" => $arrfilter ), null, false );
		$this->addClientScript ( "filters=" . $filters->render ( true ), "start" );
		if (! isset ( $this->_config ["plugins"] )) {
			$this->setConfig ( "plugins", "filters" );
		}
	}

	function setShowButton($button) {
		if ($button === null) {
			$button = array ();
		}
		$this->_showbutton = $button;
	}

	function setViewReport($value) {
		if ($value && in_array ( "reports", $this->_showbutton )) {
			//$winc =$this->getWinConfig("reports");
			$this->addTopBar ( array (
			array (
					"id" => "bReports",
					"text" => 'Reports',
					"tooltip" => 'Render Reports',
					"iconCls" => 'x-rpt-print',
					"handler" => "function(a,b){var grid=Ext.getCmp('" . $this->_id . "');if (grid) {sm=grid.getSelectionModel().getSelected();if(!sm){Ext.MessageBox.alert('Error','No Data Selected');return false} doLoadPageContent('{$this->_reportURL}&{$this->_fieldId}='+sm.data.{$this->_fieldId})}}" ) ) );
		}

		//"handler" => "function(){doLoadPageContent('{$this->_reportURL}')}")));
	}

	function assignStandard($m = null, $keyid = null) {
		if ($keyid) {
			$this->_fieldId = $keyid;
		}
		$m = $m ? $m : Request::get( "_m" );
		$perms = ACLHelper::getInstance();
		if ($perms->checkModule ( $m, "edit" )) {
			$this->setcanEdit ( true );
		}
		if ($perms->checkModule ( $m, "add" )) {
			$this->setcanAdd ( true );
		}
		if ($perms->checkModule ( $m, "delete" )) {
			$this->setCanDelete ( true, $m );
		}
		if ($perms->checkModule ( $m, "access" )) {
			$sm = $this->getConfig ( "sm" );
			if (! $sm) {
				$sm = $this->Setconfig ( "sm", new JExtCustom ( "Ext.grid.RowSelectionModel", array (
					"singleSelect" => true ) ) );
			}
			if ($sm instanceof JExtComponent) {
				if ($this->_viewMode == "window") {
					$winc = $this->getWinConfig ( "view" );
					$this->addEvent ( "rowdblclick", "function(grid,idx,e){sm= grid.getSelectionModel().getSelected();if (sm) {data = sm.data;uri = '{$this->_viewURL}&{$this->_fieldId}='+data.{$this->_fieldId};doCreateRemoteWindow({" . ($winc ? $winc . "," : "") . "autoLoad:{url:uri}})}}" );
				} else {
					$this->addEvent ( "rowdblclick", "function(grid,idx,e){sm= grid.getSelectionModel().getSelected();if (sm) {data = sm.data;uri = '{$this->_viewURL}&{$this->_fieldId}='+data.{$this->_fieldId};doLoadPageContent(uri)}}" );
				}
			}
		}
		$this->setViewReport ( true );
	}

	function setParent($value) {
		parent::setParent ( $value );
		$this->_RenderWhenNoData = true;
	}

	function setFieldId($value) {
		$this->_fieldId = $value;
		$this->_baseURI = Request::getQuery ( array (
			"_a",
			"_task",
			"_dc",
		$this->_fieldId ), true );
	}

	function setSingleSelect($value) {
		$this->_singleSelect = $value;
	}

	function setData($value) {
		$this->_data = $value;
	}

	function setColumn($value) {
		$this->_column = $value;
	}

	function addColumns($arrc) {
		foreach ( $arrc as $v ) {
			$this->addColumn ( $v );
		}
	}

	function setcanAdd($value) {
		if (! $value) {
			Utils::arrayRemoveValue ( $this->_showbutton, "add" );
			return;
		}
		if ($value && in_array ( "add", $this->_showbutton )) {
			$winc = $this->getWinConfig ( "add" );
			$this->addTopBar ( array (
			array (
					"id" => "badd",
					"text" => 'Add',
					"tooltip" => 'Add a new row',
					"iconCls" => 'add',
					"handler" => "function(){doCreateRemoteWindow({" . ($winc ? $winc . "," : "") . "modal:true,autoScroll:true,autoLoad:{url:'" . $this->_addEditURL . "'}},null,{$this->_winCallBack});}" ) ) );
		}
	}

	function setCanDelete($value, $m = null) {
		if (! $value) {
			Utils::arrayRemoveValue ( $this->_showbutton, "delete" );
		}
		if ($value && in_array ( "delete", $this->_showbutton )) {
			$m = ModuleManager::getModuleInfo ( $m );
			$delaction = "GExt.loadUrl('{$this->_deleteURL}&del='+sm.data.{$this->_fieldId},{success:function(){grid.store.load()}})";
			$this->addTopBar ( array (
			array (
					"id" => "bDelete",
					"text" => 'Delete',
					"tooltip" => 'Delete row',
					"iconCls" => 'delete',
					"handler" => "function(a,b){var grid=Ext.getCmp('" . $this->_id . "');if (grid) {sm=grid.getSelectionModel().getSelected();if(!sm){Ext.MessageBox.alert('Error','No Data Selected');return false} Ext.Msg.confirm('Confirm Delete','Delete Selected Data?',function(b) { if (b=='yes'){ $delaction }})}}" ) ) );
		}
	}

	function setCanEdit($value) {
		if (! $value) {
			Utils::arrayRemoveValue ( $this->_showbutton, "edit" );
			return;
		}
		if (! $this->_fieldId) {
			throw new SystemException ( __FUNCTION__ . " please set field id" );
		}
		if ($value && in_array ( "edit", $this->_showbutton )) {
			$winc = $this->getWinConfig ( "edit" );
			$this->addTopBar ( array (
			array (
					"id" => "bEdit",
					"text" => 'Edit',
					"tooltip" => 'Edit row',
					"iconCls" => 'edit',
					"handler" => "function(a,b){var grid=Ext.getCmp('" . $this->_id . "');if (grid) {sm=grid.getSelectionModel().getSelected();if(!sm){Ext.MessageBox.alert('Error','No Data Selected');return false} doCreateRemoteWindow({" . ($winc ? $winc . "," : "") . "autoLoad:{url:'{$this->_addEditURL}&{$this->_fieldId}='+sm.data.{$this->_fieldId}}},null,{$this->_winCallBack})}}" ) ) );
		}
	}

	protected function getFields() {
		if (! $this->_fields && $this->_data) {
			if (is_object ( $this->_data )) {
				$fld = get_object_vars ( $this->_data );
				$this->_fields = array ();
				foreach ( array_keys ( $fld ) as $k ) {
					$this->_fields [] = array (
						"name" => "$k" );
				}
			} elseif (isset ( $this->_data [0] ) && count ( $this->_data [0] )) {
				$this->_fields = array ();
				if (is_array ( $this->_data [0] )) {
					$list = $this->_data [0];
				} elseif (is_object ( $this->_data [0] )) {
					$list = get_object_vars ( $this->_data [0] );
				}
				foreach ( array_keys ( $list ) as $k ) {
					$this->_fields [] = array (
						"name" => "$k" );
				}
			}
		}
		return $this->_fields;
	}

	function preRender() {
		parent::preRender ();
		if (!JExt::isExtAllLoaded()) {
			$this->_controlScript = array (
			'id' => get_class ( $this ),
			'url' => JExt::PluginURL() . 'grid/grid.js' );
		}
		$fld = $this->getFields ();
		$fields = array ();
		if ($fld) {
			foreach ( $fld as $v ) {
				$fields [] = JSON::encode ( $v );
			}
		}

		if (! $this->getConfig ( "columns" ) && $this->_column) {
			foreach ( $this->_column as $k => $c ) {
				if (is_array ( $c )) {
					$c ["header"] = __ ( $c ["header"] );
				} elseif (is_object ( $c )) {
					$c->header = __ ( $c->header );
				}
				$this->_column [$k] = $c;
			}
			$this->setConfig ( "columns", $this->_column );
			$this->setFiltersCols ( $this->_column );
		}
		if (! $this->getConfig ( "store" ) && ! $this->getConfig ( "ds" )) {
			if ($this->_data !== null) {
				$fields = implode ( ",\n", $fields );
				if (is_array ( $this->_data )) {
					$storeid = "store$this->id";
					$js = "var $storeid =new Ext.data.JsonStore({";
					$js .= "fields: [$fields]";
					$js .= "});\n$storeid.loadData(" . JSON::encode ( $this->_data ) . ");\n";
					$this->addClientScript ( $js );
				} elseif (is_string ( $this->_data )) {
					$storeid = new JJsonStore ( $this->_data, array (
						"fields" => new JExtJS ( "[$fields]" ) ) );
				}
				$this->setConfig ( "store", $storeid );
			} else if (! $this->_RenderWhenNoData) {
				JExt::addToolbar ( 'badd', 'Add', "function(){doCreateRemoteWindow({modal:true,autoScroll:true,height:200,autoLoad:{url:'" . $this->_addEditURL . "'}});}" );
				Response::Write ( __ ( "No Data" ) );
				return false;
			}
		}
		$this->prepareRender ();
		//ppd($this->_config);
		return "";
	}

	public function addColumn($obj) {
		$this->_column [] = $obj;
	}

	function &addItem($obj, $key = null) {
		return $this->addColumn ( $obj );
	}

	//TODO:: move to report
	function getReportData($data, $col) {
		if (isset ( $data [$col ["dataIndex"]] )) {
			$retval = $data [$col ["dataIndex"]];
			if (isset ( $col ["renderer"] )) {
				switch (strtolower ( $col ["renderer"] )) {
					case "GExt.renderer.projectcolor" :
						break;
					case "GExt.renderer.currency" :
						$retval = Utils::formatCurrency ( $retval );
						break;
					case "GExt.renderer.progress" :
						break;
					default :
						ppd ( strtolower ( $col ["renderer"] ) );
					;
					break;
				}
				return $retval;
			} else {
				return $data [$col ["dataIndex"]];
			}
		} else {
			return "&nbsp;";
		}
	}

	function RenderReport($renderer) {
		$str = "{\$css}<table class=\"rpt\" width=\"100%\">";
		$str .= "<tr>";
		$str .= "<td class=\"title\" colspan=\"" . count ( $this->_column ) . "\">{\$reportTitle}<td>";
		$str .= "</tr>";
		$str .= "<tr>";
		//ppd($this->_column);
		foreach ( $this->_column as $col ) {
			$str .= "<td class=\"rptHead\">" . $col ["header"] . "</td>";
		}
		$str .= "</tr>";
		foreach ( $this->_data as $data ) {
			$str .= "<tr>";
			foreach ( $this->_column as $col ) {
				$str .= "<td>" . $this->getReportData ( $data, $col ) . "</td>";
			}
			$str .= "</tr>";
		}
		$str .= "</table>";
		$renderer->assign ( array (
			"css" => "",
			"footer" => "" ) );
		$this->_reportString = $str;
		if ($this->raiseEvent ( "onRenderReport", $renderer )) {
			return $renderer->RenderString ( $this->_reportString );
		}
		return false;
	}

	function Render($return = false, &$handle = false) {
		if ($this->_renderMode) {
			$this->preRender ();
			switch (strtolower ( $this->_renderMode )) {
				case "report" :
					$rpt = new WebReport ();
					$rpt->attachEventHandler ( "onRenderContent", array (
					$this,
						"renderReport" ) );
					return $rpt->Render ( $return, $handle );
					break;
				default :
					return parent::Render ( $return, $handle );
				break;
			}
		} else {
			return parent::Render ( $return, $handle );
		}
	}
}

class JExtEditorGrid extends JExtGrid {

	function __construct($data = null, $column = null, $fields = null) {
		$this->_class = "GExt.grid.EditorGridPanel";
		parent::__construct ( $data, $column, $fields );
	}
}

class TTableGrid extends JExtControl {

	function __construct($tableid) {
		parent::__construct ( "GExt.grid.TableGrid" );
		$this->id = $tableid;
	}

	function Render($return = false, &$handle = false) {
		$retval = "var grid = new GExt.grid.TableGrid('$this->id', {stripeRows: true ;});grid.render();";
		JSUtils::showJSScript ( $retval );
	}
}

class JExtGroupingGrid extends JExtGrid {

	function __construct($dataurl, $field, $column = null) {
		parent::__construct ( null, $column, null );
		$this->setConfigs ( array (
			"view" => new JExtCustom ( "Ext.grid.GroupingView", array (
				"forceFit" => true,
				"showGroupName" => true,
				"hideGroupedColumn" => true ), null, false ) ) );
		$this->_config ["store"] = new JGroupingStore ( $dataurl, null, $field );
	}

	function setForceFit($value) {
		return $this->apply ( "view", "forceFit", $value );
	}

	function setAutoScroll($value) {
		$this->applyConfig ( "autoScroll", $value, false );
		return $this->apply ( "view", "autoScroll", $value );
	}
}

class JExtGroupingSummaryGrid extends JExtGroupingGrid {

	function __construct($store, $configs) {
		parent::__construct ( null, null, null );
		$this->_config ["store"] = $store;
		$this->setConfigs ( $configs, false );
	}

	function PreRender() {
		$initconfigs = array (
			"id" => $this->_config ["id"],
			"store" => $this->_config ["store"],
			"columns" => $this->_config ["columns"],
			"view" => new JExtCustom ( "Ext.grid.GroupingView", array (
				"showGroupName" => false,
				"enableNoGroups" => false,  // REQUIRED!
				"hideGroupedColumn" => true ), null, false ),
			"plugins" => new JExtJS ( "new Ext.grid.GroupSummary()" ) );
		$cfg = $this->_config;
		$this->_config = array ();
		$this->setConfigs ( $initconfigs, false );
		$this->setConfigs ( $cfg );
		return parent::preRender ();
	}
}

class JExtGridCheckboxSelectionModel extends JExtCustom {

	function __construct($config) {
		parent::__construct ( "Ext.grid.CheckboxSelectionModel", $config, null, false );
	}
}
?>
