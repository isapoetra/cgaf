<?php
namespace System\Models;
use System\MVC\Model;
class Menus extends Model {
	/**
	 * @FieldType int
	 * @FieldLength 11
	 *
	 * @var int
	 */
	public $menu_id;
	/**
	 * @FieldType varchar
	 * @FieldLength 50
	 * @FieldReference #__applications app_id no no
	 *
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
	 *
	 * @var String
	 */
	public $menu_position;
	/**
	 * @FieldType int
	 * @FieldLength 11
	 *
	 * @var int
	 */
	public $menu_action_type;
	/**
	 * @FieldType varchar
	 * @FieldLength 150
	 *
	 * @var String
	 */
	public $menu_action;
	/**
	 * @FieldType int
	 * @FieldLength 11
	 * @FieldDefaultValue 0
	 *
	 * @var int
	 */
	public $menu_index;
	/**
	 * @FieldType varchar
	 * @FieldLength 150
	 *
	 * @var String
	 */
	public $menu_icon;
	/**
	 * @FieldType varchar
	 * @FieldLength 150
	 *
	 * @var String
	 */
	public $tooltip;
	/**
	 * @FieldType smallint
	 * @FieldLength 6
	 * @FieldDefaultValue 1
	 *
	 * @var int
	 */
	public $menu_state;
	/**
	 * @FieldType varchar
	 * @FieldLength 45
	 *
	 * @var String
	 */
	public $xtarget;
	/**
	 * @FieldType int
	 * @FieldLength 11
	 * @FieldAllowNull false
	 * @FieldDefaultValue 0
	 *
	 * @var int
	 */
	public $menu_parent;
	/**
	 * @FieldType varchar
	 * @FieldLength 45
	 * @FieldAllowNull false
	 * @var String
	 */
	public $menu_class;
	/**
	 * @FieldType varchar
	 * @FieldLength 45
	 *
	 * @var String
	 */
	public $menu_tag;
	function __construct() {
		parent::__construct ( \CGAF::getDBConnection (), "menus", "menu_id,app_id", true, \CGAF::isInstalled () === false );
		$this->setThrowOnError(false);
		//$this->getConnection()->createDBObjectFromClass($this,'table',$this->getTableName(false,false));
	}
	function filterACL($o) {

		if (is_object ( $o )) {
			$acl = $this->getAppOwner ()->getACL ();
			$type = null;
			//pp($o);

			$action = explode ( "/", $o->menu_action );
			if (isset ( $action [1] )) {
				$url = parse_url ( $action [1] );
			}
			$access = count ( $action ) > 1 ? $url ['path'] : "view";
			switch (( int ) $o->menu_action_type) {
				case 4 : // javascript
				case 0 :
					return $o;
				case 3:
					$ctl = $this->getAppOwner ()->getController ( trim ( $action [0] ) ,false);

					if ($ctl && $ctl->isAllow ( $access )) {
						$ctl->initAction($action[1],$o);
						return $ctl->{$action[1]}($o);
					}
					break;
				case 2 :
					$action [1] = 'view';
				case 1 :
				default :
					$type = "controller";


					try {
						$ctl = $this->getAppOwner ()->getController ( trim ( $action [0] ) );
						if ($ctl && $ctl->isAllow ( $access )) {
							return $o;
						}
					} catch ( \Exception $e ) {
						// ppd($this->getAppOwner());
						// pp($e->getMessage());
					}
			}
			return null;
		} else if (is_array ( $o )) {
			$retval = array ();
			foreach ( $o as $v ) {
				$v = $this->filterACL ( $v );
				if ($v) {
					$retval [] = $v;
				}
			}
			return $retval;
		}
		return parent::filterACL ( $o );
	}
	function resetgrid($id = null) {
		$this->setAlias ( 'm' )
		->reset ()
		->clear ( 'field' )
		->select ( 'm.*,st.value status_name' )
		->join ( 'vw_cms_defaultstatus', 'st', 'st.key=m.menu_state', 'inner', true )
		->orderby ( 'm.menu_state' );
		return $this;
	}
}
