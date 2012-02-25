<?php
namespace  System\Collections\Items;
class SearchItem extends \Object{
	public static  $DEFAULT_PROP=array('title','descr','link');
	private $_object = null;
	function __construct($o=null,$attr=null) {
		$this->_object = $o;
		$attr = $attr ? $attr :self::$DEFAULT_PROP;
		if ($o) {
			$oa =  self::$DEFAULT_PROP;
			foreach($attr as $k=>$v) {
				$oa[$k] =  $v;
			}
			$attr = $oa;
			if (is_object($o)) {
				$this->title =  $o->{$attr[0]};
				$this->descr =  $o->{$attr[1]};
				$this->link =  $o->{$attr[2]};
			}

		}
	}
	function renderAs($f) {
		switch ($f) {
			case 'html':
				$retval =  '<li>';
				$retval .= '<a href="'.$this->link.'">';
				$retval .= $this->title;
				$retval .= '</a>';
				$retval .= '<div>'.$this->descr.'</div>';
				$retval .= '</li>';
				return $retval;
				break;
			default:
				throw new \System\Exceptions\SystemException('unhandled output format '.$f);
		}
	}
	function getObject() {
		return $this->_object;
	}
}

?>