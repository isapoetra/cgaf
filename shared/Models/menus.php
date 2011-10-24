<?php
namespace System\Models;
use System\MVC\Model;
use \CGAF;
class Menus extends Model {
	/**
	 * @FieldType int
	 * @FieldLength 11
	 * @var int
	 */
	public $menu_id;
	/**
	 * @FieldType varchar
	 * @FieldLength 50
	 * @var String
	 */
	public $app_id;
	/**
	 * @FieldType varchar
	 * @FieldLength 150
	 * @var String
	 */
	public $caption;
	/**
	 * @FieldType varchar
	 * @FieldLength 20
	 * @var String
	 */
	public $menu_position;
	/**
	 * @FieldType int
	 * @FieldLength 11
	 * @var int
	 */
	public $menu_action_type;
	/**
	 * @FieldType varchar
	 * @FieldLength 150
	 * @var String
	 */
	public $menu_action;
	/**
	 * @FieldType int
	 * @FieldLength 11
	 * @var int
	 */
	public $menu_index;
	/**
	 * @FieldType varchar
	 * @FieldLength 150
	 * @var String
	 */
	public $menu_icon;
	/**
	 * @FieldType varchar
	 * @FieldLength 150
	 * @var String
	 */
	public $tooltip;
	/**
	 * @FieldType smallint
	 * @FieldLength 6
	 * @var String
	 */
	public $menu_state;
	/**
	 * @FieldType varchar
	 * @FieldLength 45
	 * @var String
	 */
	public $xtarget;
	/**
	 * @FieldType int
	 * @FieldLength 10
	 * @var int
	 */
	public $menu_parent;
	/**
	 * @FieldType varchar
	 * @FieldLength 45
	 * @var String
	 */
	public $menu_class;
	/**
	 * @FieldType varchar
	 * @FieldLength 45
	 * @var String
	 */
	public $menu_tag;

	function __construct($appOwner) {
		parent::__construct(CGAF::getDBConnection(), "menus", "menu_id,app_id", true);

		$this->setAppOwner($appOwner);
	}

	function filterACL($o) {
		if (is_object($o)) {
			$acl = $this->getAppOwner()->getACL();

			$type = null;
			$action = explode("/", $o->menu_action);
			switch (( int ) $o->menu_action_type) {
				case 4: // javascript
				case 0:
					return $o;
				case 2 :
					$action [1] = 'view';
				case 1 :
				default :
					$type = "controller";
					if (isset($action [1])) {
						$url = parse_url($action [1]);

					}
					$access = count($action) > 1 ? $url ['path'] : "view";
					if ($acl->isAllow(trim($action [0]), $type, $access)) {
						return $o;
					} else {
						//test from controller
						try {
							$ctl = $this->getAppOwner()->getController($action [0]);
							if ($ctl && $ctl->isAllow($access)) {
								return $o;
							}
						} catch (\Exception $e ) {

						}
					}
			}
			return null;
		} else
		if (is_array($o)) {
			$retval = array ();
			foreach ( $o as $v ) {
				$v = $this->filterACL($v);
				if ($v) {
					$retval [] = $v;
				}
			}
			return $retval;
		}
		return parent::filterACL($o);
	}

	function resetgrid($id = null) {
		$this->setAlias('m')->reset()->clear('field')->select('m.*,st.value status_name')->join('vw_cms_defaultstatus', 'st', 'st.key=m.menu_state', 'inner', true)->orderby('m.menu_state');
		return $this;
	}
}