<?php
namespace System\Web\UI\Ext\Tree;
use System\Web\UI\JExt;

use System\ACL\ACLHelper;

use System\Web\UI\Ext\CustomComponent;

use System\Web\UI\Ext\ExtJS;
use System\Web\UI\Ext\Control;
class Panel extends Control {
	protected $_fieldId;
	protected $_viewMode = "window";
	protected $_renderStandardButton = true;
	protected $_reorderAction = "reorder";
	protected $_showButton = array(
			"add",
			"edit",
			"delete",
			"view",
			"reports");
	function __construct($configs) {
		$this->_ignoreQuery[] = "parent_id";
		parent::__construct("Ext.tree.TreePanel");
		$this->_controlScript = array(
				"id" => "tree",
				"url" => "tree.js");
		$this->addIgnoreConfigStr(array(
						"root"));
		if (isset($configs["renderStandardButton"])) {
			$this->_renderStandardButton = $configs["renderStandardButton"];
			$this->removeConfig("renderStandardButton");
		}
		if (!isset($configs["root"])) {
			$configs["root"] = new CustomComponent("Ext.tree.AsyncTreeNode", array(
					"text" => 'root',
					"id" => "0",
					"expanded" => true));
		}
		$this->setConfigs(array(
						"trackMouseOver" => true,
						"useArrows" => true,
						"enableDD" => true,
						"animate" => true,
						"height" => "400",
						"containerScroll" => true,
						"rootVisible" => false,
						"expanded" => true));
		parent::setConfigs($configs, false);
		$this->_config["tbar"][] = array(
				"iconCls" => "x-tbar-loading",
				"tooltip" => __("Refresh"),
				"handler" => "function(){tree=Ext.getCmp('{$this->getId()}');tree.loader.load(tree.getRootNode());}");
		$this->_config["tbar"][] = array(
				"iconCls" => "bExpandAll",
				"tooltip" => __("Expand All"),
				"handler" => "function(){ tree=Ext.getCmp('{$this->getId()}');tree.expandAll(); }");
		$this->_config["tbar"][] = array(
				"iconCls" => "bCollapseAll",
				"tooltip" => __("Collapse All"),
				"handler" => "function(){ tree=Ext.getCmp('{$this->getId()}');tree.collapseAll(); }");
		$this->_config["tbar"][] = new ExtJS("new Ext.menu.Separator()");
	}
	function addShowButton($btn) {
		if (is_array($btn)) {
			foreach ($btn as $b) {
				$this->addShowButton($b);
			}
			return;
		}
		if (!in_array($btn, $this->_showButton)) {
			$this->_showButton[] = $btn;
		}
	}
	function setShowButton($value) {
		if (!is_array($value)) {
			$value = array();
		}
		$this->_showButton = array();
	}
	function addTopBar($obj, $multi = true) {
		if (!isset($this->_config["tbar"])) {
			$this->_config["tbar"] = array();
		}
		if (is_array($obj) && $multi) {
			foreach ($obj as $v) {
				$this->_config["tbar"][] = $v;
			}
		} else {
			$this->_config["tbar"][] = $obj;
		}
	}
	function setReorderAction($value) {
		$this->_reorderAction = $value;
	}
	function setRenderStandardButton($value) {
		$this->_renderStandardButton = $value;
	}
	function setFieldID($value) {
		$this->_fieldId = $value;
	}
	function preRender() {
		$buttons = array();
		if ($this->_renderStandardButton) {
			$minfo = \ModuleManager::getModuleInfo();
			$m = $minfo->mod_id;
			$perms = ACLHelper::getInstance();
			//pp($_GET);
			$id = $this->_fieldId ? $this->_fieldId : "id";
			$renderto = \Request::get("renderTo");
			$removed = false;
			if (isset($this->_config["tbar"]) && $this->_config["tbar"] === null) {
				$this->removeConfig("tbar");
				$removed = true;
			}
			if (!$removed) {
				if ($perms->checkModule($m, "add") && in_array("add", $this->_showButton)) {
					$winc = $this->getWinConfig("add");
					$add = "function(){var tree=Ext.getCmp('{$this->getId()}');doCreateRemoteWindow({autoLoad:{url:'{$this->_addEditURL}'}" . ($winc ? ",$winc" : "") . "},null,{changed:function(){tree.loader.load(tree.getRootNode())} })}";
					$buttons[] = array(
							"text" => "new",
							"iconCls" => JExt::ICON_ADD,
							"handler" => $add);
					$buttons[] = array(
							"text" => "add Child",
							"iconCls" => JExt::ICON_ADD . "child",
							"handler" => "function(){ var tree=Ext.getCmp('{$this->getId()}'); s = tree.getSelectionModel().getSelectedNode();if (s) { doCreateRemoteWindow({autoLoad:{url:'{$this->_addEditURL}&parent_id='+s.id}"
									. ($winc ? ",$winc" : "") . "},'$renderto',{changed:function(){tree.loader.load(s.parentNode)} })}}");
					$buttons[] = new ExtJS("new Ext.menu.Separator()");
				}
				if (in_array("view", $this->_showButton)) {
					$winc = $this->getWinConfig("view");
					$buttons[] = array(
							"text" => "view",
							"iconCls" => JExt::ICON_VIEW,
							"handler" => "function(){ tree=Ext.getCmp('{$this->getId()}');s = tree.getSelectionModel().getSelectedNode();if (s) { doCreateRemoteWindow({autoLoad:{url:'{$this->_viewURL}&$id='+s.id},modal:true,autoScroll:true"
									. ($winc ? "," . $winc : "") . "},'$renderto')}}");
					$action = $this->_viewMode == "window" ? "doCreateRemoteWindow({autoLoad:{url:'{$this->_viewURL}&$id='+node.id},modal:true,autoScroll:true" . ($winc ? "," . $winc : "") . "},'$renderto')"
							: "doLoadPageContent('{$this->_viewURL}&$id='+node.id);";
					$this->addEvent("dblclick", "function(node,e){" . $action . "}");
				}
				if ($perms->checkModule($m, "edit") && in_array("edit", $this->_showButton)) {
					$winc = $this->getWinConfig("edit");
					$buttons[] = array(
							"text" => "edit",
							"iconCls" => JExt::ICON_EDIT,
							"handler" => "function(){ tree=Ext.getCmp('{$this->getId()}');s = tree.getSelectionModel().getSelectedNode();if (s) { doCreateRemoteWindow({autoLoad:{url:'{$this->_addEditURL}&$id='+s.id},modal:true,autoScroll:true"
									. ($winc ? "," . $winc : "") . "},'$renderto',{changed:function(){tree.loader.load(s.parentNode)} });}}");
				}
				if ($perms->checkModule($m, "delete") && in_array("delete", $this->_showButton)) {
					$buttons[] = array(
							"text" => "delete",
							"iconCls" => JExt::ICON_DELETE,
							"handler" => "function(){G.confirm('" . __("Delete This Data")
									. "', function(){tree=Ext.getCmp('{$this->getId()}');s = tree.getSelectionModel().getSelectedNode();if (s) { G.loadUrl('{$this->_deleteURL}&del='+s.id,{success:function(e){tree.loader.load(s.parentNode)}})}})}");
				}
				if (in_array("reports", $this->_showButton) && in_array("reports", $this->_showButton)) {
					$this->_config["tbar"][] = array(
							"text" => 'Reports',
							"tooltip" => 'Render Reports',
							"iconCls" => 'x-rpt-print',
							"handler" => "function(){doLoadPageContent('{$this->_reportURL}')}");
				}
			}
			if ($this->getConfig("enableDD") && $this->_reorderAction) {
				$this
						->addEvent("beforemovenode",
								"function(tree, node, oldParent, newParent, index ) { G.confirm('Move Node?',function(){ Ext.Ajax.request({method:'GET',url:tree.loader.baseUrl+'&_a={$this->_reorderAction}',scope:this,params:{id:node.id,newparent:newParent.id,index:index}})});} ");
			}
		}
		$this->addTopBar($buttons);
		parent::preRender();
	}
}
