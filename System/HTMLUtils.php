<?php

abstract class HTMLUtils {
	private static $_lastCSS;
	private static $_lastForm;
	public static function isEmail($email) {
		return preg_match ( '/^[^@\s<&>]+@([-a-z0-9]+\.)+[a-z]{2,}$/i', $email );
	}
	public static function renderAttr($attr) {

		if (is_string ( $attr ) || $attr === null) {
			return $attr;
		}
		if (is_array ( $attr ) && ! count ( $attr )) {
			return '';
		}
		$r = '';
		foreach ( $attr as $k => $v ) {
			switch (strtolower ( $k )) {
				default :
					$r .= "$k=\"$v\" ";
			}

		}
		return $r;
	}
	private static function explodeAttr($attr) {
		if (! is_string ( $attr )) {
			return $attr;
		}
		$retval = array ();
		$matches = array ();
		preg_match_all ( '/\s([A-Za-z][0-9A-Za-z_\:\.-]*)\="([^"]*)"/i', " " . $attr, $matches );
		foreach ( $matches [1] as $k => $v ) {
			$retval [$v] = $matches [2] [$k];
		}
		return $retval;
	}
	public static function mergeAttr($attr, $val) {
		if (! $attr) {
			$attr = '';
		}
		/*$attr = "<span class=\"test test test\" id=\"ss1\"/>";*/

		$attr = self::explodeAttr ( $attr );
		$val = self::explodeAttr ( $val );

		$retval = $attr;
		foreach ( $val as $k => $v ) {
			switch (strtolower ( $k )) {
				case 'class' :
					$r = array ();
					$n = isset ( $retval [$k] ) ? explode ( ' ', $retval [$k] ) : array ();
					$c = explode ( ' ', $v );
					foreach ( $n as $v ) {
						if (! in_array ( $v, $r )) {
							$r [] = $v;
						}
					}
					foreach ( $c as $v ) {
						if (! in_array ( $v, $r )) {
							$r [] = $v;
						}
					}
					$retval [$k] = implode ( ' ', $r );
					break;
				default :
					$retval [$k] = $v;
					break;
			}
		}
		return $retval;
	}
	public static function renderBoxed($title, $content, $attr = null, $allowNullContent = false) {
		if (! $content && ! $allowNullContent) {
			return null;
		}
		$retval = '';
		$attr = self::mergeAttr ( $attr, array (
			'class' => 'ui-widget-content ui-corner-all ui-widget-box' ) );
		$retval .= '<div ' . self::renderAttr ( $attr ) . '>';
		if ($title) {
			$retval .= ' <div class="ui-widget-header title"><h4>' . $title . '</h4></div>';
		}
		$retval .= ' <div class="container">' . $content . '</div>';
		$retval .= '</div>';
		return $retval;

	}
	protected static function attrToArray($attr) {
		$match = array ();
		preg_match_all ( '/(\w+\s*)=\s*"[^"]*"/i', $attr, $match );
		$rval = array ();
		foreach ( $match [0] as $v ) {
			$s = explode ( '=', $v );

			$rval [$s [0]] = $s [1];
		}
		return $rval;
	}
	public static function beginForm($action, $multipart = true, $showMessage = true, $msg = null, $attr = null) {
		$attr = $attr ? $attr : array ();
		if (! is_array ( $attr )) {
			$attr = self::attrToArray ( $attr );
		}

		if (! isset ( $attr ['id'] )) {
			$attr ['id'] = Utils::generateId ( 'frm' );
		}

		self::$_lastForm = $attr ['id'];
		$retval = '<form method="post" action="' . $action . '" ' . ($multipart ? 'enctype="application/x-www-form-urlencoded"' : "") . ' ' . self::renderAttr ( $attr ) . '>';
		if ($showMessage) {
			$retval .= '<div id="error-message" class="On" style="' . ($msg ? 'display:block' : 'display:none') . '">' . ($msg ? $msg : '&nbsp;') . '</div>';
		}
		return $retval;
	}
	public static function renderButton($type = 'submit', $text = '', $title = '', $attr = null, $showLabel = true, $img = '') {
		$btn = new HTMLButton ();
		$btn->setAttr ( 'type', $type );
		$btn->setText ( $text );
		$btn->setTitle ( $title );
		$btn->setAttr ( $attr );
		$btn->setShowLabel ( $showLabel );
		$btn->setIcon ( $img );
		return $btn->render ( true );
	}
	public static function renderFormAction() {
		$retval = '<div class="formAction">';

		$retval .= self::renderButton ( 'reset', __ ( 'reset', 'Reset' ), __ ( 'reset.descr', 'Reset this form' ), array (
			'class' => 'reset button' ), true, 'reset.png' );
		$retval .= self::renderButton ( 'submit', __ ( 'submit', 'Submit' ), __ ( 'submit.descr', 'Submit this form' ), array (
			'class' => 'submit button' ), true, 'submit.png' );
		$retval .= '</div>';
		return $retval;
	}

	public static function getJSAsset($js, $live = true, $prefix = null) {
		$min = AppManager::getInstance ()->getResource ( Utils::changeFileExt ( $js, "min.js" ), $prefix, $live );
		$js = AppManager::getInstance ()->getResource ( $js, $prefix, $live );
		if ($min) {
			if (CGAF_DEBUG) {
				if (! $js) {
					$js = $min;
				}
			} else {
				$js = $min;
			}
		}
		return $js;

	}

	public static function endForm($renderAction = true, $renderToken = false, $ajaxmode = false) {
		$retval = "";
		if ($renderAction) {
			$retval .= self::renderFormAction ();
		}
		if ($renderToken) {
			$retval .= self::renderHiddenField ( '__token', Session::get ( '__token' ) );
		}
		$retval .= "</form>";

		if ($ajaxmode) {
			$id = self::$_lastForm;
			$js = <<< JS
$('#$id').gform();
JS;
			$retval .= self::renderScript ( $js );
		}
		return $retval;
	}

	public static function renderScript($script) {
		$retval = "<script type=\"text/javascript\"  language=\"javascript\">$script</script> ";
		return $retval;
	}

	public static function renderCaptcha($captchaId = "__captcha", $attr = null, $showlabel = true) {

		$capt = AppManager::getInstance ()->getController ( 'captcha' );
		if ($capt) {
			return $capt->renderContainer ( $captchaId, $attr, $showlabel );
		} else {
			return null;
		}
	}

	public static function beginBox() {
		static $first;
		$r = "";
		if (! $first) {
			$d = AppManager::getInstance ()->getAsset ( "box.css" );
			if ($d !== null) {
				$r = "<style>" . self::parseCSS ( file_get_contents ( $d ) ) . "</style>";
			}
		}
		$first = true;
		return $r . '<div class="box">';
	}

	public static function endBox() {
		return "</div>";
	}
	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $css
	 * @deprecated
	 */
	public static function packCSS($css) {
		return WebUtils::PackCSS($css);
	}

	//	 public static function parseCSS($css, $file = false, $pack = null) {
	//		$parsed =array();
	//		if ($pack == null) {
	//			$pack = ! CGAF_DEBUG;
	//		}
	//		$content = "";
	//		$retval = "";
	//		if (is_array($css)) {
	//
	//			foreach ( $css as $k => $v ) {
	//				self::$_lastCSS = dirname($v).DS;
	//				$retval .= "\n/*" . basename($v) . "*/\n" . self::parseCSS(file_get_contents($v),false, $pack);
	//			}
	//			if ($pack) {
	//				$retval = self::packCSS($retval);
	//			}
	//			return $retval;
	//		} elseif (is_string($file)) {
	//			self::$_lastCSS = dirname($css) . DS;
	//			$content = $file;
	//			$file = true;
	//			if (is_string($pack)) {
	//				$pack = ! CGAF_DEBUG;
	//			}
	//		} else {
	//			if ($file) {
	//				$content = file_get_contents($css);
	//				self::$_lastCSS = $content;
	//			}else{
	//				$content = $css;
	//			}
	//		}
	//		if ($content) {
	//			//preg_match_all('|url\((.+)[\,)](.)(.*)|',$content,$matches);
	//			preg_match_all('/url\\(\\s*([^\\)\\s]+)\\s*\\)/',$content,$matches);
	//
	//			if (isset($matches[1])) {
	//				foreach($matches[1] as $v) {
	//					$quoteChar = ($v[0] === "'" || $v[0] === '"') ? $v[0] : '';
	//					$uri = ($quoteChar === '') ? $v : substr($v, 1, strlen($v) - 2);
	//					$nval = AppManager::getInstance()->getLiveData($uri);
	//					if (!$nval) {
	//						$nval = AppManager::getInstance()->getLiveData(self::$_lastCSS . Utils::ToDirectory($v));
	//					}
	//					if ($nval) {
	//						if (!in_array($v,$parsed)) {
	//							$content=str_replace($v,$nval,$content);
	//							$parsed[]=$v;
	//						}
	//					}
	//				}
	//				$retval = $content;
	//			}
	//
	//			//$retval = preg_replace_callback('|url\((.+)[\,)](.)(.*)|', "HTMLUtils::cssRegexCallback", $content);
	//			//$retval = preg_replace_callback('|url\((.+)[\,)](.)(.*)|', "HTMLUtils::cssRegexCallback", $content);
	//
	//		}
	//		if ($pack) {
	//			$retval = self::packCSS($retval);
	//		}
	//		return $retval;
	//	}


	/*public static function cssRegexCallback($matches) {
		$f = str_replace ( "'", '', $matches [1] );
		$f = str_replace ( "\"", '', $f );
		$fname = null;
		$nval = AppManager::getInstance ()->getLiveData ( $f );
		$retval = '';
		if ($nval) {
		$retval = "url('$nval')";

		} else {
		$fname = self::$_lastCSS . Utils::ToDirectory ( $f );

		if (is_readable ( $fname )) {
		$retval = 'url(\'' . Utils::PathToLive ( $fname ) . '\')';
		} else {
		$retval = 'url(\'' . Utils::PathToLive ( $fname ) . '\')';
		Logger::Warning ( 'resource not found ' . $fname . ' for ' . self::$_lastCSS );
		}
		}
		return $retval . ' ' . $matches [2] . $matches [3];
		}
		*/
	public static function renderTextArea($title, $id, $value, $attr = null, $editMode = true) {
		return self::renderFormField ( $title, $id, $value, $attr, $editMode, "textarea" );
	}

	public static function renderTextBox($title, $id, $value, $attr = null, $editMode = true) {
		return self::renderFormField ( $title, $id, $value, $attr, $editMode );
	}

	public static function renderPassword($title, $id, $value = '', $attr = null, $editMode = true) {
		return self::renderFormField ( $title, $id, $value, $attr, $editMode, "password" );
	}
	public static function renderCheckbox($title, $id, $value, $attr = null, $editMode = true) {
		if ($value) {
			$attr = self::mergeAttr ( $attr, array (
				'checked' => 'checked' ) );
		}

		return self::renderFormField ( $title, $id, $value, $attr, $editMode, "checkbox" );
	}
	public static function renderHiddenField($id, $value) {
		return self::renderFormField ( null, $id, $value, null, true, 'hidden' );
	}

	public static function renderFormField($title, $id, $value, $attr = null, $editMode = false, $type = "text") {
		$renderlabel = true;
		$retval = "";
		switch ($type) {
			case "checkbox" :
				if ($value) {
					$attr = self::mergeAttr ( $attr, array (
						'checked' => 'checked' ) );
				}
				break;
		}
		$attr = self::renderAttr ( $attr );
		//ppd($attr);
		switch ($type) {
			case 'htmleditor' :
				if ($editMode) {
					$editor = new TJQHTMLEditor ( $id, null );
					$editor->setValue ( $value );
					$editor->setConfig ( $attr );
					return ($title ? '<label for="' . $id . '">' . $title . '</label>' : '') . $editor->Render ( true );
				}
			case 'textarea' :

				if ($editMode) {
					$retval .= "<textarea  id=\"$id\" name=\"$id\" $attr>$value</textarea>";
				} else {
					$retval .= "<span id=\"$id\" $attr>" . $value . "</span>";
				}
				break;
			case "checkbox" :

				if ($editMode) {

					$retval .= "<input type=\"$type\" value=\"$value\" id=\"$id\" name=\"$id\" $attr/>";
				} else {
					$retval .= "<span id=\"$id\" $attr>" . Utils::bool2yesno ( $value ) . "</span>";
				}
				break;
			case 'hidden' :
				$renderlabel = false;
			case "text" :
			case "password" :
			default :
				if ($editMode) {
					$retval .= "<input type=\"$type\" value=\"$value\" id=\"$id\" name=\"$id\" $attr/>";
				} else {
					$retval .= "<span id=\"$id-label\" $attr>$value</span>";
					$retval .= "<input type=\"hidden\" value=\"$value\" id=\"$id\" name=\"$id\" $attr/>";

				}
				break;
		}
		if ($renderlabel) {
			$retval = "<div class=\"$id ui-input-container\">" . ($title ? "<label for=\"$id\" class=\"field\">$title</label>" : "") . $retval . '</div>';
		}
		return $retval;
	}

	public static function renderRadioGroups($title, $id, $items, $selected = null, $nonevalue = '-1', $baseLang = null) {
		$retval = "";
		if ($title) {
			$retval .= '<label>' . $title;
		}
		$baseLang = $baseLang ? $baseLang : $id;
		foreach ( $items as $item ) {
			if (is_object ( $item )) {
				$key = $item->key;
				$value = $item->value;
				$tt = $item->descr;
				$ttitle = $item->title ? $item->title : ($tt ? $tt : $value);
			} else {
				$key = $item ['key'];
				$value = $item ['value'];
				$tt = $item ['descr'];

				$ttitle = isset ( $item ['title'] ) ? $item ['title'] : (isset ( $item ['descr'] ) ? $item ['descr'] : $item ['key']);
			}
			$sel = '';
			if ($key == $selected) {
				$sel = 'checked="checked"';
			}
			$retval .= '<label><input title="' . $tt . '" type="radio" id="' . $id . '_' . $key . '" name="' . $id . '" value="' . $key . '" ' . $sel . '/>' . $ttitle . '</label><br/>';
		}
		if ($title) {
			$retval .= '</label>';
		}
		return $retval;
	}

	public static function renderSelect($title, $id, $items, $selected = null, $nonevalue = '-1', $baseLang = null, $attr = null, $editmode = true, $content = null) {
		$retval = "";
		if ($title) {
			$retval .= '<label class="field" for="' . $id . '">' . $title . '</label>';
		}
		if ($editmode) {
			$retval .= '<select id="' . $id . '" name="' . $id . '" ' . self::renderAttr ( $attr ) . '>';
			$baseLang = $baseLang ? $baseLang : $id;
			foreach ( $items as $item ) {
				if (is_object ( $item )) {
					$key = isset ( $item->key ) ? $item->key : $item->title;
					$value = $item->value ? $item->value : $key;
					$tt = isset ( $item->descr ) && $item->descr ? $item->descr : $value;
				} else {
					$key = $item ['key'];
					$value = $item ['value'];
					$tt = $item ['descr'];
				}
				$sel = '';
				if ($key == $selected) {
					$sel = 'selected="selected"';
				}
				$retval .= "<option $sel value=\"$key\" title=\"" . htmlentities ( $tt ) . "\">" . __ ( $baseLang . '.' . $value, $value ) . "</option>";
			}
			if ($nonevalue !== false && $nonevalue !== null) {
				$retval .= "<option value=\"$nonevalue\" title=\"None\">" . __ ( $baseLang . '.None', 'None' ) . "</option>";
			}
			$retval .= '</select>';
		} else {
			$val = self::getSelectValue ( $items, $selected );
			$retval .= self::renderHiddenField ( $id, $selected );
			$retval .= '<span>' . $val . '</span>';

		}
		$retval .= $content;

		return $retval;
	}

	public static function getSelectValue($items, $val) {
		foreach ( $items as $item ) {
			if (is_object ( $item )) {
				$key = $item->key;
				$value = $item->value;
				$tt = $item->descr;
			} else {
				$key = $item ['key'];
				$value = $item ['value'];
				$tt = $item ['descr'];
			}
			if (( string ) $key === ( string ) $val) {
				return $value;
			}
		}
		return null;
	}

	public static function renderPagination($currentPage, $pageCount, $dataUrl, $rowperpage = 10) {
		$rowperpage = Request::get ( "_rp", $rowperpage );
		$retval = "<div  class=\"navigation\">";
		$retval .= "<span>" . sprintf ( "Page %s of %s", $currentPage + 1, $pageCount ) . "</span>&nbsp;";
		$retval .= "<div class=\"pagenumber\">";
		$max = $pageCount >= 10 ? 10 : $pageCount;
		for($i = 0; $i < $max; $i ++) {
			$class = "";
			$title = ($i + 1);
			if ($i == $currentPage) {
				$class = "selected";
				$title = "[$title]";
			}
			echo "<a href=\"$dataUrl/?_cp=$i&_rpp=$rowperpage\" class=\"$class\" onclick=\"paginate(this,'#profileinfogrid')\">$title</a>";
		}
		if ($pageCount > 10) {
			echo "...<a href=\"$dataUrl/?_page=$pageCount&_rp=$rowperpage\" class=\"$class\" onclick=\"paginate(this,'#profileinfogrid')\">$pageCount<a>";
		}
		$retval .= "</div>";
		$retval .= "</div>";
		return $retval;
	}

	public static function renderTable($id, $rows, $dataUrl, $langPrefix = null) {
		$currentPage = Request::get ( "_page", 0 );
		$rowperpage = count ( $rows ) - 1;
		$pageCount = $rows ["rowCount"] > 0 ? $rows ["rowCount"] / $rowperpage : 0;
		$retval = "<div id=\"$id\">";
		$retval .= "<table>";
		$retval .= "<tr>";
		$cols = $rows [0];
		foreach ( $cols as $col => $v ) {
			$retval .= "<th>" . __ ( "$langPrefix.$col.title", $col ) . "</th>";
		}
		$retval .= "</tr>";
		foreach ( $rows as $row ) {
			$retval .= "<tr>";
			foreach ( $cols as $col => $v ) {
				$retval .= "<td>" . $v . "</td>";
			}
			$retval .= "</tr>";
		}
		$retval .= "</table>";

		$retval .= self::renderPagination ( $currentPage, $rows ["rowCount"], $dataUrl, $rowperpage );
		$retval .= "</div>";
		if (Request::isSupport ( "javascript" )) {
			$retval .= "<script type=\"text/javascript\">";
			$retval .= "<!--";
			$retval .= "$(function() {";
			$retval .= "$('#$id').grid({";
			$retval .= "pageCount:$pageCount,";
			$retval .= "pagingUrl:'$dataUrl'";
			$retval .= "});";
			$retval .= "});";
			$retval .= "//-->";
			$retval .= "</script>";
		}
		return $retval;
	}

	public static function renderError($msg) {
		return '<div class="error">' . $msg . '</div>';
	}

	public static function renderLink($link, $title, $attr = null, $icon = null, $descr = null) {
		$icon = $icon ? $icon : 'cleardot.gif';
		if (! $attr) {
			$attr = array ();
		}

		$icon = AppManager::getInstance ()->getLiveData ( $icon, 'images' );

		if (! $icon) {
			$icon = AppManager::getInstance ()->getLiveData ( 'cleardot.gif', 'images' );
		}
		$attr = $descr ? self::mergeAttr ( 'title="' . $descr . '"', $attr ) : $attr;
		return '<a href="' . $link . '" ' . self::renderAttr ( $attr ) . '><img src="' . $icon . '"/><span>' . $title . '</span></a>';
	}
	public static function renderButtonLink($link, $title = null, $attr = null, $icon = null, $descr = null) {
		if (is_array ( $link )) {
			if (! count ( $link )) {
				return null;
			}

			$retval = '<ul ' . self::renderAttr ( $attr ) . '>';
			foreach ( $link as $k => $v ) {
				$v ['attr'] = isset ( $v ['attr'] ) ? $v ['attr'] : null;
				$v ['title'] = isset ( $v ['title'] ) ? $v ['title'] : null;

				$retval .= self::renderButtonLink ( $v ['url'], $v ['title'], $v ['attr'], $v ['icon'], $v ['descr'] );
			}
			$retval .= '</ul>';
			return $retval;
		} else {
			$btn = new HTMLButton ();
			$btn->setURL ( $link );
			$btn->setIcon ( $icon );
			$btn->setAttr ( $attr );
			$btn->setText ( $title );
			$btn->setTitle ( $title );
			return $btn->Render ( true );
		}
	}
	public static function renderSpanImg($title, $attr) {
		return '<span ' . $attr . '><img src="/Data/images/cleardot.gif"/>' . $title . '</span>';
	}

	public static function renderLinks($items, $attr = null) {
		$retval = '';
		if (! is_array ( $items )) {
			return null;
		}
		if (! count ( $items )) {
			return null;
		}
		$retval = '<ul ' . self::renderAttr ( $attr ) . '>';
		foreach ( $items as $item ) {
			$ir = '';
			if ($item instanceof IRenderable) {
				$ir = $item->Render ( true );
			} elseif (is_array ( $item )) {
				$item ['attr'] = isset ( $item ['attr'] ) ? $item ['attr'] : '';
				$item ['icon'] = isset ( $item ['icon'] ) ? $item ['icon'] : '';
				$item ['descr'] = isset ( $item ['descr'] ) ? $item ['descr'] : '';
				$item ['title'] = isset ( $item ['title'] ) ? $item ['title'] : $item ['url'];
				$ir = self::renderLink ( $item ['url'], $item ['title'], $item ['attr'], $item ['icon'], $item ['descr'] );
			}
			if ($ir) {
				$retval .= '<li>' . $ir . '</li>';
			}
		}
		$retval .= '</ul>';
		return $retval;
	}
	public static function explodeStyle($style) {
		if (! $style) {
			return array ();
		}
		if (is_array ( $style )) {
			return $style;
		}
		$style .= ';';

		$styles = explode ( ';', $style );
		$retval = array ();
		foreach ( $styles as $v ) {
			if (! $v)
			continue;
			$v = explode ( ':', $v );
			$title = $v [0];
			array_shift ( $v );
			$retval [$title] = implode ( '', $v );
		}
		return $retval;

	}
	public static function mergeStyle($old, $new) {
		if (! $old) {
			$old = '';

			//$old = 'border:1px solid green;background:url(http://test.test.com/)';
		}
		$old = self::explodeStyle ( $old );
		$new = self::explodeStyle ( $new );
		$r = array_merge ( $old, $new );
		$retv = '';
		foreach ( $r as $k => $v ) {
			$retv .= "$k:$v;";
		}
		return $retv;

	}

	public static function renderTRField($row, $field = null, $baselang = null) {
		$retval = '';
		$field = $field ? $field : array_keys ( get_object_vars ( $row ) );
		foreach ( $field as $f ) {
			$title = __ ( ($baselang ? $baselang . '.' : '') . $f, $f );
			$value = $row->$f;
			$retval .= <<< EOT
			<tr>
				<th>$title</th>
			<td>$value</td>
		</tr>
EOT;
		}
		return $retval;
	}
	public static function renderCoordinate($value, $xfield = 'xcoord', $yfield = 'ycoord', $editmode = true) {
		$retval = '<fieldset><legend>Coordinate</legend>';
		$retval .= '<table width="100%" cellspacing="0" cellpadding="0" class="input-coordinate">';
		$retval .= '<tr>';
		$retval .= '<td>' . self::renderTextBox ( __ ( 'longitude', 'Longitude' ), $xfield, $value [$xfield], array (
			'class' => 'required number' ), $editmode ) . '</td>';
		$retval .= '<td>' . self::renderTextBox ( __ ( 'latitude', 'Latitude' ), $yfield, $value [$yfield], array (
			'class' => 'required number' ), $editmode ) . '</td>';
		if (AppManager::getInstance ()->getConfig ( 'app.enablegooglemap', CGAF_DEBUG )) {
			$retval .= '<tr><td colspan="2" align="right"><button>Pick From Map</button></td></tr>';
		}
		$retval .= '</tr></table></fieldset>';
		return $retval;
	}

	public static function renderMenu($items, $selected = null, $class = null, $repl = null) {
		if (! $items) {
			return '';
		}
		$retval = "<ul class=\"$class menu\">";
		foreach ( $items as $k => $item ) {
			if (! ($item instanceof MenuItem)) {
				$item =  new MenuItem (Utils::bindToObject ( new stdClass (), $item, true ));
			}
			if ($repl) {
				$item->setReplacer ( $repl );
			}
			if ($selected) {
				if ($item->getId() === $selected) {
					$item->setSelected(true);
				}
			}
			$retval .= $item->render ( true );
		}
		$retval .= "</ul>";
		return $retval;
	}
	public static function OptimizeHTML($html) {
		$tidy = new tidy();
		$config = array( 'indent' => true, 'output-xhtml' => true, 'wrap' => 200, 'clean' => true, 'show-body-only' => false );
		$tidy->parseString( $html, $config, 'utf8' );
		$tidy->cleanRepair(  );
		return tidy_get_output($tidy);
	}
}
?>
