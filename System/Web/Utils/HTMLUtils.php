<?php
namespace System\Web\Utils;
use System\Web\JS\CGAFJS;
use System\Captcha\Captcha;
use System\Web\UI\Controls\Button;
use System\Web\WebUtils;
use System\JSON\JSON;
use System\Web\UI\Items\MenuItem;
use AppManager;
use System\Web\UI\Controls\Anchor;
use Utils;
use System\Session\Session;
use Request;
use System\Template\TemplateHelper;
use IRenderable;
use System\Web\UI\JQ\HTMLEditor;

abstract class HTMLUtils {
	const FORM_MODE_NORMAL = 'normal';
	const FORM_MODE_TABLE = 'table';
	private static $_lastCSS;
	private static $_lastForm;
	private static $_formMode = 'normal';

	public static function setFormMode($value) {
		self::$_formMode = $value;
	}

	public static function getCookieFromHeader($head) {
		$cookies = array();
		if (!is_array($head))
			return $cookies;
		foreach ($head as $s) {
			if (preg_match('|^Set-Cookie:\s*([^=]+)=([^;]+);(.+)$|', $s, $parts))
				$cookies[$parts[1]] = $parts[2];
		}
		return $cookies;
	}

	public static function isEmail($email) {
		return preg_match('/^[^@\s<&>]+@([-a-z0-9]+\.)+[a-z]{2,}$/i', $email);
	}

	public static function renderLoginForm() {
		return TemplateHelper::getInstance()->render('login', true, false);
	}

	public static function renderAttr($attr) {
		if (is_string($attr) || $attr === null) {
			return $attr;
		}
		if (is_array($attr) && !count($attr)) {
			return '';
		}
		$r = ' ';
		foreach ($attr as $k => $v) {
			$v = \Convert::toString($v);
			switch (strtolower($k)) {
				case 'class':
					$v = str_replace('[', '', $v);
					$v = str_replace(']', '', $v);
					$r .= "$k=\"$v\" ";
					break;
				default:
					$r .= "$k=\"$v\" ";
			}
		}
		return $r;
	}

	private static function explodeAttr($attr) {
		if (!is_string($attr)) {
			return $attr;
		}
		$retval = array();
		if (!$attr) {
			return $retval;
		}
		$matches = array();
		preg_match_all('/\s([A-Za-z][0-9A-Za-z_\:\.-]*)\="([^"]*)"/i',
				" " . $attr, $matches);
		foreach ($matches[1] as $k => $v) {
			$retval[$v] = $matches[2][$k];
		}
		return $retval;
	}

	public static function mergeAttr($attr, $val) {
		if (!$attr) {
			$attr = '';
		}
		/*
		 * $attr = "<span class=\"test test test\" id=\"ss1\"/>";
		 */
		$attr = self::explodeAttr($attr);
		$val = self::explodeAttr($val);
		$retval = $attr;
		foreach ($val as $k => $v) {
			switch (strtolower($k)) {
				case 'class':
					$r = array();
					$n = isset($retval[$k]) ? explode(' ', $retval[$k])
							: array();
					$c = explode(' ', $v);
					foreach ($n as $v) {
						if (!in_array($v, $r)) {
							$r[] = $v;
						}
					}
					foreach ($c as $v) {
						if (!in_array($v, $r)) {
							$r[] = $v;
						}
					}
					$retval[$k] = implode(' ', $r);
					break;
				default:
					$retval[$k] = $v;
					break;
			}
		}
		return $retval;
	}

	public static function renderBoxed($title, $content, $attr = null,
			$allowNullContent = false) {
		if (!$content && !$allowNullContent) {
			return null;
		}
		$retval = '';
		$attr = self::mergeAttr($attr,
				array(
						'class' => 'ui-widget-content ui-corner-all ui-widget-box'
				));
		$retval .= '<div ' . self::renderAttr($attr) . '>';
		if ($title) {
			$retval .= ' <div class="ui-widget-header title"><h4>' . $title
					. '</h4></div>';
		}
		$retval .= ' <div class="container">' . $content . '</div>';
		$retval .= '</div>';
		return $retval;
	}

	protected static function attrToArray($attr) {
		$match = array();
		preg_match_all('/(\w+\s*)=\s*"[^"]*"/i', $attr, $match);
		$rval = array();
		foreach ($match[0] as $v) {
			$s = explode('=', $v);
			$rval[$s[0]] = $s[1];
		}
		return $rval;
	}

	public static function beginForm($action, $multipart = true,
			$showMessage = true, $msg = null, $attr = null) {
		$attr = $attr ? $attr : array();
		if (!is_array($attr)) {
			$attr = self::attrToArray($attr);
		}
		$attr = self::mergeAttr(
				array(
						'class' => 'form-horizontal',
						'id' => Utils::generateId('frm')
				), $attr);
		self::$_lastForm = $attr['id'];
		// application/x-www-form-urlencoded
		$retval = '<form method="post" action="' . $action . '" '
				. ($multipart ? 'enctype="multipart/form-data"' : "") . ' '
				. self::renderAttr($attr) . '>';
		if ($showMessage) {
			$retval .= '<div id="error-message" class="label label-important On" style="'
					. ($msg ? 'display:block' : 'display:none') . '">'
					. ($msg ? $msg : '&nbsp;') . '</div>';
		}
		return $retval;
	}

	public static function renderButton($type = 'submit', $text = '',
			$title = '', $attr = null, $showLabel = true, $img = '') {
		$btn = new \System\Web\UI\Controls\Button();
		$btn->setAttr('type', $type);
		$btn->setText($text);
		$btn->setTitle($title);
		$btn->setAttr($attr);
		$btn->setShowLabel($showLabel);
		$btn->setIcon($img);
		return $btn->render(true);
	}

	public static function renderFormAction() {
		$retval = '<div class="form-actions">';
		$retval .= self::renderButton('reset', __('reset', 'Reset'),
				__('reset.descr', 'Reset this form'),
				array(
						'class' => 'btn'
				), true, 'reset.png');
		$retval .= self::renderButton('submit', __('submit', 'Submit'),
				__('submit.descr', 'Submit this form'),
				array(
						'data-loading-text' => 'loading...',
						'class' => 'btn btn-primary'
				), true, 'submit.png');
		$retval .= '</div>';
		return $retval;
	}

	public static function getJSAsset($js, $live = true, $prefix = null) {
		$min = AppManager::getInstance()
				->getResource(Utils::changeFileExt($js, "min.js"), $prefix,
						$live);
		$js = AppManager::getInstance()->getResource($js, $prefix, $live);
		if ($min) {
			if (CGAF_DEBUG) {
				if (!$js) {
					$js = $min;
				}
			} else {
				$js = $min;
			}
		}
		return $js;
	}

	public static function endForm($renderAction = true, $renderToken = false,
			$ajaxmode = false, $ajaxConfig = null, $js = true) {
		$retval = "";
		if ($renderAction) {
			$retval .= self::renderFormAction();
		}
		if ($renderToken) {
			$retval .= self::renderHiddenField('__token',
					Session::get('__token'));
		}
		$retval .= "</form>";
		$id = self::$_lastForm;
		if ($js) {
			$ajaxConfig = $ajaxConfig ? $ajaxConfig : array();
			if (!$ajaxmode) {
				$ajaxConfig['ajaxmode'] = false;
			}
			$ajaxConfig = JSON::encodeConfig($ajaxConfig,
					array(
							'beforeSend',
							'complete',
							'error',
							'success'
					));
			//$ajaxConfig = str_replace ( '\n', "\n", $ajaxConfig );
			//$ajaxConfig = str_replace ( '\t', "\t", $ajaxConfig );
			$js = <<< JS
				$('#$id').gform($ajaxConfig);
JS;
			AppManager::getInstance()->addClientScript($js);
		}
		return $retval;
	}

	public static function renderScript($script) {
		$retval = "<script type=\"text/javascript\"  language=\"javascript\">$script</script> ";
		return $retval;
	}

	public static function renderCaptcha($captchaId = "__captcha", $attr = null,
			$showlabel = true) {
		$capt = Captcha::getInstance(\AppManager::getInstance());
		if ($capt) {
			return $capt->Render(true);
		} else {
			return null;
		}
	}

	public static function beginBox() {
		static $first;
		$r = "";
		if (!$first) {
			$d = AppManager::getInstance()->getAsset("box.css");
			if ($d !== null) {
				$r = "<style>" . self::parseCSS(file_get_contents($d))
						. "</style>";
			}
		}
		$first = true;
		return $r . '<div class="box">';
	}

	public static function endBox() {
		return "</div>";
	}
	/**
	 * Enter description here .
	 * ..
	 *
	 * @param $css unknown_type
	 * @deprecated
	 */

	public static function packCSS($css) {
		return WebUtils::PackCSS($css);
	}
	// public static function parseCSS($css, $file = false, $pack = null) {
	// $parsed =array();
	// if ($pack == null) {
	// $pack = ! CGAF_DEBUG;
	// }
	// $content = "";
	// $retval = "";
	// if (is_array($css)) {
	//
	// foreach ( $css as $k => $v ) {
	// self::$_lastCSS = dirname($v).DS;
	// $retval .= "\n/*" . basename($v) . "*/\n" .
	// self::parseCSS(file_get_contents($v),false, $pack);
	// }
	// if ($pack) {
	// $retval = self::packCSS($retval);
	// }
	// return $retval;
	// } elseif (is_string($file)) {
	// self::$_lastCSS = dirname($css) . DS;
	// $content = $file;
	// $file = true;
	// if (is_string($pack)) {
	// $pack = ! CGAF_DEBUG;
	// }
	// } else {
	// if ($file) {
	// $content = file_get_contents($css);
	// self::$_lastCSS = $content;
	// }else{
	// $content = $css;
	// }
	// }
	// if ($content) {
	// //preg_match_all('|url\((.+)[\,)](.)(.*)|',$content,$matches);
	// preg_match_all('/url\\(\\s*([^\\)\\s]+)\\s*\\)/',$content,$matches);
	//
	// if (isset($matches[1])) {
	// foreach($matches[1] as $v) {
	// $quoteChar = ($v[0] === "'" || $v[0] === '"') ? $v[0] : '';
	// $uri = ($quoteChar === '') ? $v : substr($v, 1, strlen($v) - 2);
	// $nval = AppManager::getInstance()->getLiveData($uri);
	// if (!$nval) {
	// $nval = AppManager::getInstance()->getLiveData(self::$_lastCSS .
	// Utils::ToDirectory($v));
	// }
	// if ($nval) {
	// if (!in_array($v,$parsed)) {
	// $content=str_replace($v,$nval,$content);
	// $parsed[]=$v;
	// }
	// }
	// }
	// $retval = $content;
	// }
	//
	// //$retval = preg_replace_callback('|url\((.+)[\,)](.)(.*)|',
	// "HTMLUtils::cssRegexCallback", $content);
	// //$retval = preg_replace_callback('|url\((.+)[\,)](.)(.*)|',
	// "HTMLUtils::cssRegexCallback", $content);
	//
	// }
	// if ($pack) {
	// $retval = self::packCSS($retval);
	// }
	// return $retval;
	// }
	/*
	 * public static function cssRegexCallback($matches) { $f = str_replace (
	 * "'", '', $matches [1] ); $f = str_replace ( "\"", '', $f ); $fname =
	 * null; $nval = AppManager::getInstance ()->getLiveData ( $f ); $retval =
	 * ''; if ($nval) { $retval = "url('$nval')"; } else { $fname =
	 * self::$_lastCSS . Utils::ToDirectory ( $f ); if (is_readable ( $fname ))
	 * { $retval = 'url(\'' . Utils::PathToLive ( $fname ) . '\')'; } else {
	 * $retval = 'url(\'' . Utils::PathToLive ( $fname ) . '\')';
	 * Logger::Warning ( 'resource not found ' . $fname . ' for ' .
	 * self::$_lastCSS ); } } return $retval . ' ' . $matches [2] . $matches
	 * [3]; }
	 */

	public static function renderTextArea($title, $id, $value = null,
			$attr = null, $editMode = true) {
		return self::renderFormField($title, $id, $value, $attr, $editMode,
				"textarea");
	}

	public static function renderDateInput($title, $id, $value = null,
			$attr = null, $editMode = true, $message = null, $input = 'date') {
		$attr = HTMLUtils::mergeAttr($attr,
				array(
						'autocomplete' => 'off',
						'class' => 'input-xlarge',
						'data-input' => $input
				));
		$groupClass = null;
		if ($message) {
			$groupClass = 'error';
			$message = '<span class="help-inline">'
					. \Convert::toString($message) . '</span>';
		}
		if ($value) {
			$value = new \CDate($value);
			$value = $value->format(__('client.dateFormat'));
		}
		return self::renderFormField($title, $id, $value, $attr, $editMode,
				'text', null, null, $groupClass, $message);
	}

	public static function renderTextBox($title, $id, $value = '', $attr = null,
			$editMode = true) {
		$cls = isset($attr['class']) ? $attr['class'] : '';
		
		if (strpos($cls, 'input') === false) {
			$attr = self::mergeAttr($attr,
					array(
							'class' => 'input-xlarge'
					));
		}
		return self::renderFormField($title, $id, $value, $attr, $editMode);
	}

	public static function renderPassword($title, $id, $value = '',
			$attr = null, $editMode = true, $add = null) {
		$attr = HTMLUtils::mergeAttr($attr,
				array(
						'autocomplete' => 'off'
				));
		return self::renderFormField($title, $id, $value, $attr, $editMode,
				"password", 'left', 'controls', null, $add);
	}

	public static function renderCheckbox($title, $id, $value = false,
			$attr = null, $editMode = true) {
		if ($value) {
			$attr = self::mergeAttr($attr,
					array(
							'checked' => 'checked'
					));
		}
		return self::renderFormField($title, $id, $value, $attr, $editMode,
				"checkbox");
	}

	public static function renderHiddenField($id, $value, $attr = null) {
		return self::renderFormField(null, $id, $value, $attr, true, 'hidden');
	}

	public static function renderHTMLEditor($title, $id, $value = null,
			$attr = null, $editmode = true) {
		return self::renderFormField($title, $id, $value, $attr, $editmode,
				'htmleditor');
	}

	public static function renderEditor($title, $id, $value, $attr = null,
			$editmode = true) {
		return self::renderFormField($title, $id, $value, $attr, $editmode,
				'htmleditor');
	}

	private static function renderLabel($for, $title) {
		if (!$title) {
			return;
		}
		$retval = '';
		if (self::$_formMode === self::FORM_MODE_TABLE) {
			$retval .= '<th>';
		}
		$retval .= '<label class="control-label" for="' . $for . '">' . $title
				. '</label>';
		if (self::$_formMode === self::FORM_MODE_TABLE) {
			$retval .= '</th>';
		}
		return $retval;
	}

	public static function renderLookup($title, $id, $value = null,
			$attrs = null) {
		$attrs = self::mergeAttr($attrs,
				array(
						'data-provide' => 'typeahead'
				));
		return self::renderTextBox($title, $id, $value, $attrs);
	}

	public static function renderAutoComplete($title, $id, $srcLookup,
			$value = null, $attrs = null) {
		$attrs = self::mergeAttr($attrs,
				array(
						'srcLookup' => $srcLookup,
						'data-input' => 'autocomplete'
				));
		return self::renderTextBox($title, $id, $value, $attrs);
	}

	private static function toClassAttr($v) {
		$v = str_replace('[', '_', $v);
		$v = str_replace(']', '', $v);
		return $v;
	}

	public static function renderFormField($title, $id, $value, $attr = null,
			$editMode = false, $type = "text", $labelPosition = 'left',
			$containerClass = 'controls', $groupClass = 'control-group',
			$additional = null) {
		$renderlabel = true;
		$retval = "";
		$prefix = null;
		$suffix = null;
		$labelPosition = $labelPosition ? $labelPosition : 'left';
		$containerClass = $containerClass ? $containerClass : 'controls';
		$groupClass = 'control-group ' . $groupClass;
		switch ($type) {
			case "checkbox":
				if ($value) {
					$attr = self::mergeAttr($attr,
							array(
									'checked' => 'checked'
							));
				}
				break;
		}
		$rattr = $attr;
		if (is_array($attr)) {
			if (isset($attr['__prefix'])) {
				$prefix = $attr['__prefix'];
				unset($attr['__prefix']);
			}
			if (isset($attr['__suffix'])) {
				$suffix = $attr['__suffix'];
				unset($attr['__suffix']);
			}
		}
		$attr = self::renderAttr($attr);
		// pp($attr);
		switch ($type) {
			case 'htmleditor':
				if ($editMode) {
					$editor = new HTMLEditor($id, null);
					$editor->setValue($value);
					$editor->setConfig($rattr);
					return self::renderLabel($id, $title)
							. $editor->Render(true);
				}
			case 'textarea':
				if ($editMode) {
					$retval .= "<textarea  id=\"$id\" name=\"$id\" $attr>$value</textarea>";
				} else {
					$retval .= '<span id="' . $id
							. '" class="textarea-value-only" ' . $attr . '>'
							. $value . '</span>';
				}
				break;
			case "checkbox":
				if ($editMode) {
					$retval .= "<input type=\"$type\" value=\"$value\" id=\"$id\" name=\"$id\" $attr/>";
				} else {
					$retval .= "<span id=\"$id\" $attr>"
							. Utils::bool2yesno($value) . "</span>";
				}
				break;
			case 'hidden':
				$renderlabel = false;
			case "text":
			case "password":
			default:
				if ($editMode) {
					$retval .= "<input type=\"$type\" value=\"$value\" id=\"$id\" name=\"$id\" $attr/>";
				} else {
					$retval .= "<span id=\"$id-label\" $attr>$value</span>";
					$retval .= "<input type=\"hidden\" value=\"$value\" id=\"$id\" name=\"$id\" $attr/>";
				}
				break;
		}
		$retval .= $suffix;
		if ($renderlabel) {
			$lbl = self::renderLabel($id, $title);
			if (self::$_formMode === self::FORM_MODE_NORMAL) {
				$s = "<div class=\"$groupClass " . self::toClassAttr($id)
						. "\" id=\"" . self::toClassAttr($id) . "-container\">";
				$s .= ($labelPosition === 'left' ? $lbl : '');
				$s .= '<div class="' . $containerClass . '">';
				$s .= $retval;
				$s .= $additional;
				$s .= '</div>';
				$s .= ($labelPosition === 'right' ? $lbl : '');
				$s .= '</div>';
				$retval = $s;
			} else {
				$retval = ($labelPosition === 'left' ? $lbl : '')
						. "<td class=\"$id ui-input-container\" id=\"$id-container\">"
						. $retval . '</td>'
						. ($labelPosition === 'right' ? $lbl : '');
			}
		}
		return $retval;
	}

	public static function renderRadioGroups($title, $id, $items,
			$selected = null, $nonevalue = '-1', $baseLang = null) {
		$retval = "";
		if ($title) {
			$retval .= '<label>' . $title;
		}
		$baseLang = $baseLang ? $baseLang : $id;
		foreach ($items as $item) {
			if (is_object($item)) {
				$key = $item->key;
				$value = $item->value;
				$tt = @$item->descr;
				$ttitle = @$item->title ? @$item->title : ($tt ? $tt : $value);
			} else {
				$key = $item['key'];
				$value = isset($item['value']) ? $item['value'] : $key;
				$tt = isset($item['descr']) ? $item['descr']
						: (isset($item['title']) ? $item['title'] : $key);
				$ttitle = isset($item['title']) ? $item['title']
						: (isset($item['descr']) ? $item['descr'] : $item['key']);
			}
			$sel = '';
			if ($key == $selected) {
				$sel = 'checked="checked"';
			}
			$retval .= '<label><input title="' . $tt . '" type="radio" id="'
					. $id . '_' . $key . '" name="' . $id . '" value="' . $key
					. '" ' . $sel . '/>' . $ttitle . '</label><br/>';
		}
		if ($title) {
			$retval .= '</label>';
		}
		return $retval;
	}

	public static function renderSelect($title, $id, $items, $selected = null,
			$nonevalue = '-1', $baseLang = null, $attr = null, $editmode = true,
			$content = null) {
		$retval = "";
		if ($title) {
			if (self::$_formMode == self::FORM_MODE_NORMAL) {
				$retval .= '<div class="control-group '
						. (isset($attr['class']) ? $attr['class'] : '') . '">';
				$retval .= '<label class="control-label field" for="' . $id
						. '">' . $title . '</label>';
				$retval .= '<div class="controls">';
			} elseif (self::$_formMode == self::FORM_MODE_TABLE) {
				$retval .= '<th class="field" for="' . $id . '">' . $title
						. '</th>';
			}
		}
		if ($editmode) {
			if (self::$_formMode === self::FORM_MODE_TABLE) {
				$retval .= '<td>';
			}
			$retval .= '<select id="' . $id . '" name="' . $id . '" '
					. self::renderAttr($attr) . '>';
			$baseLang = $baseLang ? $baseLang : null;
			foreach ($items as $item) {
				if (is_object($item)) {
					$key = isset($item->key) ? $item->key : $item->title;
					$value = $item->value ? $item->value : $key;
					$tt = isset($item->descr) && $item->descr ? $item->descr
							: $value;
				} else {
					$key = $item['key'];
					$value = $item['value'];
					$tt = isset($item['descr']) ? $item['descr']
							: $item['value'];
				}
				$sel = '';
				if ($key == $selected) {
					$sel = 'selected="selected"';
				}
				$retval .= "<option $sel value=\"$key\" title=\""
						. htmlentities($tt) . "\">"
						. __($baseLang ? $baseLang . '.' . $value : $value,
								$value) . "</option>";
			}
			if ($nonevalue !== false && $nonevalue !== null) {
				$retval .= "<option value=\"$nonevalue\" title=\"None\">"
						. __($baseLang . '.None', 'None') . "</option>";
			}
			$retval .= '</select>';
			if (self::$_formMode === self::FORM_MODE_TABLE) {
				$retval .= '</td>';
			} elseif ($title) {
				$retval .= '</div></div>';
			}
			//ppd($retval);
		} else {
			$val = self::getSelectValue($items, $selected);
			$retval .= self::renderHiddenField($id, $selected);
			$retval .= '<span>' . $val . '</span>';
		}
		$retval .= $content;
		return $retval;
	}

	public static function getSelectValue($items, $val) {
		foreach ($items as $item) {
			if (is_object($item)) {
				$key = $item->key;
				$value = $item->value;
				$tt = $item->descr;
			} else {
				$key = $item['key'];
				$value = $item['value'];
				$tt = $item['descr'];
			}
			if ((string) $key === (string) $val) {
				return $value;
			}
		}
		return null;
	}

	public static function renderPagination($currentPage, $pageCount, $dataUrl,
			$rowperpage = 10) {
		$rowperpage = Request::get("_rp", $rowperpage);
		$dataUrl = $dataUrl ? $dataUrl : \URLHelper::getOrigin();
		//$retval = '<div class="clearfix">';
		//$retval .= "<span>" . sprintf ( "Page %s of %s", $currentPage + 1, $pageCount ) . "</span>&nbsp;";
		$retval = "<div  class=\"pagination\">";
		$retval .= '<ul>';
		$max = $pageCount >= 10 ? 10 : $pageCount;
		for ($i = 0; $i < $max; $i++) {
			$class = "";
			$title = ($i + 1);
			if ($i == $currentPage) {
				$class = "active";
				$title = "$title";
			}
			$uri = \URLHelper::addParam($dataUrl, "_cp=$i&_rpp=$rowperpage");
			$retval .= '<li  class="' . $class . '"><a href="' . $uri . '">'
					. $title . '</a></li>';
		}
		if ($pageCount > 10) {
			$uri = \URLHelper::addParam($dataUrl,
					"_page=$pageCount&_rp=$rowperpage");
			$retval .= '</li><a href="' . $uri . '" class="' . $class . '">'
					. $title . '</a></li>';
		}
		$retval .= '</ul>';
		$retval .= '</div>';
		//$retval .= '</div>';
		return $retval;
	}

	public static function renderTable($id, $rows, $dataUrl, $langPrefix = null) {
		$currentPage = Request::get("_page", 0);
		$rowperpage = count($rows) - 1;
		$pageCount = $rows["rowCount"] > 0 ? $rows["rowCount"] / $rowperpage : 0;
		$retval = "<div id=\"$id\">";
		$retval .= "<table>";
		$retval .= "<tr>";
		$cols = $rows[0];
		foreach ($cols as $col => $v) {
			$retval .= "<th>" . __("$langPrefix.$col.title", $col) . "</th>";
		}
		$retval .= "</tr>";
		foreach ($rows as $row) {
			$retval .= "<tr>";
			foreach ($cols as $col => $v) {
				$retval .= "<td>" . $v . "</td>";
			}
			$retval .= "</tr>";
		}
		$retval .= "</table>";
		$retval .= self::renderPagination($currentPage, $rows["rowCount"],
				$dataUrl, $rowperpage);
		$retval .= "</div>";
		if (Request::isSupport("javascript")) {
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
		if (!$msg) {
			return '';
		}
		if (is_array($msg)) {
			$retval = '<div class="alert">';
			foreach ($msg as $m) {
				$retval .= self::renderError($m);
			}
			$retval .= '</div>';
			return $retval;
		}
		return '<div class="label label-important">' . $msg . '</div>';
	}

	public static function renderLink($link, $title, $attr = null, $icon = null,
			$descr = null, $showlabel = true) {
		$icon = $icon ? $icon : 'cleardot.gif';
		if (!$attr) {
			$attr = array();
		}
		$icon = AppManager::getInstance()->getLiveAsset($icon, 'images');
		$attr = $descr ? self::mergeAttr('title="' . $descr . '"', $attr)
				: $attr;
		$icon = $icon ? '<img src="' . $icon . '"/>' : '';
		return '<a href="' . $link . '" ' . self::renderAttr($attr)
				. '><span class="tdl"></span>' . $icon
				. ($showlabel ? '<span class="title">' . $title . '</span></a>'
						: '');
	}

	public static function renderButtonLink($link, $title = null, $attr = null,
			$icon = null, $descr = null) {
		if (is_array($link)) {
			if (!count($link)) {
				return null;
			}
			$retval = '<ul ' . self::renderAttr($attr) . '>';
			foreach ($link as $k => $v) {
				$v['attr'] = isset($v['attr']) ? $v['attr'] : null;
				$v['title'] = isset($v['title']) ? $v['title'] : null;
				$retval .= self::renderButtonLink($v['url'], $v['title'],
						$v['attr'], $v['icon'], $v['descr']);
			}
			$retval .= '</ul>';
			return $retval;
		} else {
			$btn = new Button();
			$btn->setURL($link);
			$btn->setIcon($icon);
			$btn->setAttr($attr);
			$btn->setText($title);
			$btn->setTitle($title);
			return $btn->Render(true);
		}
	}

	public static function renderSpanImg($title, $attr) {
		return '<span ' . $attr . '><img src="/Data/images/cleardot.gif"/>'
				. $title . '</span>';
	}

	public static function renderLinks($items, $attr = null, $replacer = null) {
		$retval = '';
		if (!is_array($items)) {
			return null;
		}
		if (!count($items)) {
			return null;
		}
		$retval = '<ul ' . self::renderAttr($attr) . '>';
		foreach ($items as $k => $item) {
			$ir = '';
			if ($item instanceof MenuItem) {
				$item->setReplacer($replacer);
				$retval .= $item->Render(true);
			} elseif ($item instanceof \IRenderable) {
				$ir = $item->Render(true);
			} elseif (is_array($item)) {
				$nitem = new MenuItem($k, @$item['title'], $item['url'],
						isset($item['selected']) ? $item['selected'] : false);
				$nitem->setDescr(isset($item['descr']) ? $item['descr'] : '');
				$nitem->setIcon(isset($item['icon']) ? $item['icon'] : '');
				$nitem->setReplacer($replacer);
				if (isset($item['attr'])) {
					$nitem->setAttrs($item['attr']);
				}
				$retval .= $nitem->Render(true);
			}
			if ($ir) {
				$retval .= '<li>' . $ir . '</li>';
			}
		}
		$retval .= '</ul>';
		return $retval;
	}

	public static function explodeStyle($style) {
		if (!$style) {
			return array();
		}
		if (is_array($style)) {
			return $style;
		}
		$style .= ';';
		$styles = explode(';', $style);
		$retval = array();
		foreach ($styles as $v) {
			if (!$v)
				continue;
			$v = explode(':', $v);
			$title = $v[0];
			array_shift($v);
			$retval[$title] = implode('', $v);
		}
		return $retval;
	}

	public static function mergeStyle($old, $new) {
		if (!$old) {
			$old = '';
			// $old = 'border:1px solid
			// green;background:url(http://test.test.com/)';
		}
		$old = self::explodeStyle($old);
		$new = self::explodeStyle($new);
		$r = array_merge($old, $new);
		$retv = '';
		foreach ($r as $k => $v) {
			$retv .= "$k:$v;";
		}
		return $retv;
	}

	public static function renderTRField($row, $field = null, $baselang = null) {
		$retval = '';
		$field = $field ? $field : array_keys(get_object_vars($row));
		foreach ($field as $f) {
			$title = __(($baselang ? $baselang . '.' : '') . $f, $f);
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

	public static function renderCoordinate($value, $xfield = 'xcoord',
			$yfield = 'ycoord', $editmode = true) {
		$retval = '<fieldset><legend>Coordinate</legend>';
		$retval .= '<table width="100%" cellspacing="0" cellpadding="0" class="input-coordinate">';
		$retval .= '<tr>';
		$retval .= '<td>'
				. self::renderTextBox(__('longitude', 'Longitude'), $xfield,
						$value[$xfield],
						array(
								'class' => 'required number'
						), $editmode) . '</td>';
		$retval .= '<td>'
				. self::renderTextBox(__('latitude', 'Latitude'), $yfield,
						$value[$yfield],
						array(
								'class' => 'required number'
						), $editmode) . '</td>';
		if (AppManager::getInstance()
				->getConfig('app.enablegooglemap', CGAF_DEBUG)) {
			$retval .= '<tr><td colspan="2" align="right"><button>Pick From Map</button></td></tr>';
		}
		$retval .= '</tr></table></fieldset>';
		return $retval;
	}

	public static function renderMenu($items, $selected = null, $class = null,
			$repl = null, $menuid = null) {
		if (!$items) {
			return '';
		}
		// ppd($items);
		$menuid = $menuid ? $menuid : Utils::generateId('menu');
		$retval = '';
		// $retval = '<div class="nav-collapse" data-role="navbar">';
		$retval .= "<ul class=\"nav $class\" id=\"" . $menuid . "\">";
		foreach ($items as $k => $item) {
			if (!($item instanceof MenuItem)) {
				$item = new MenuItem(
						Utils::bindToObject(new \stdClass(), $item, true));
			}
			if ($repl) {
				$item->setReplacer($repl);
			}
			if ($selected) {
				if ($item->getId() === $selected) {
					$item->setSelected(true);
				}
			}
			$retval .= $item->render(true);
		}
		$retval .= "</ul>";
		// $retval .= '</div>';
		return $retval;
	}

	private static function _optimizeHTML($html) {
		/*
		 * CGAF::loadLibs("HTMLPurifier"); $config =
		 * HTMLPurifier_Config::createDefault(); $purifier = new
		 * HTMLPurifier($config); return $purifier->purify($html);
		 */
		$h1count = preg_match_all('/(<script.*>)(.*)(<\/script>)/imxsU', $html,
				$patterns);
		$html = preg_replace('/(<script.*>)(.*)(<\/script>)/imxsU', '', $html);
		$direct = '';
		echo '<pre>';
		var_dump($patterns);
		die();
		foreach ($patterns as $p) {
			ppd($p);
		}
		ppd($html);
	}

	public static function OptimizeHTML($html) {
		if (!class_exists('tidy', false)) {
			return self::_optimizeHTML($html);
		}
		$tidy = new \tidy();
		$config = array(
				'indent' => true,
				'output-xhtml' => true,
				'wrap' => 200,
				'clean' => true,
				'show-body-only' => false
		);
		$tidy->parseString($html, $config, 'utf8');
		$tidy->cleanRepair();
		return tidy_get_output($tidy);
	}

	public static function Render($o) {
		if (is_array($o)) {
			$retval = '';
			foreach ($o as $p) {
				$retval .= self::Render($p);
			}
			return $retval;
		}
		return \Convert::toString($o);
	}

	public static function renderDateRange($configs, $includeTime = true) {
		$retval = '<div>';
		$ds = $configs['start']['value'] ? new \Cdate(
						$configs['start']['value']) : null;
		$retval .= self::renderTextBox($configs['start']['title'],
				$configs['start']['id'], $ds ? $ds->format('m/d/Y') : null,
				array(
						'data-input' => 'date',
						'data-time' => $includeTime,
						'data-end-date' => $configs['end']['id']
				));
		if ($includeTime) {
			$retval .= self::renderTextBox(null,
					$configs['start']['id'] . '_time', $ds->format('h:i:s'),
					array(
							'data-input' => 'time'
					));
		}
		$ed = new \Cdate($configs['start']['value']);
		$retval .= self::renderTextBox($configs['end']['title'],
				$configs['end']['id'], $ed ? $ed->format('m/d/Y') : null,
				array(
						'data-input' => 'date',
						'data-time' => $includeTime,
						'data-start-date' => $configs['end']['id']
				));
		if ($includeTime) {
			$retval .= self::renderTextBox(null,
					$configs['end']['id'] . '_time', $ed->format('h:i:s'),
					array(
							'data-input' => 'time'
					));
		}
		$retval .= '</div>';
		$id1 = $configs['start']['id'];
		$id2 = $configs['end']['id'];
		return $retval;
	}

	public static function renderConfig($title, $id, $config) {
		$content = '';
		foreach ($config as $k => $v) {
			$content .= self::renderTextBox(__($id . '.' . $k, $k),
					$id . '_' . $k, $v);
		}
		return HTMLUtils::renderBoxed('Database Configuration', $content);
	}

	public static function removeTag($input) {
		return lx_externalinput_clean::basic($input);
	}

	public static function attributeToArray($attr) {
		if (is_array($attr)) {
			return $attr;
		}
		$retval = array();
		if (is_string($attr)) {
			$comp = explode(';', $attr);
			foreach ($comp as $v) {
				$nv = explode('=', $v);
				$retval[$nv[0]] = str_replace('"', '', $nv[1]);
			}
		} elseif (is_object($attr)) {
			foreach ($attr as $k => $v) {
				$retval[$k] = \Convert::toString($v);
			}
		}
		return $retval;
	}

	public static function renderFormImageUpload($id, $server) {
		$app = \AppManager::getInstance();
		$app
				->addClientAsset(
						array(
								'jquery.ajaxupload.js',
								'cgaf/css/upload.css'
						));
		$js = <<< EOT
$.ajaxUploadSettings.name = 'uploads[]';
$('#dropzone-$id').ajaxUploadPrompt({
		url : '$server',
		beforeSend : function () {
			$('#dropzone-$id, #result').hide();
		},
		onprogress : function (e) {
			if (e.lengthComputable) {
				var percentComplete = e.loaded / e.total;
				// Show in progressbar
				$( "#progressbar" ).progressbar({
					value: percentComplete*100,
					complete: function () {
						$(this).progressbar( "destroy" );
					}
				});
			}
		},
		error : function () {
		},
		success : function (data) {
			data = $.parseJSON(data);
			var html = '';
			if (data.error) {
				html += '<h2>Error</h2>';
				html += '<p>' + data.error + '</p>';
			}
			if (data.success) {
				html += '<h2>Success</h2>';
				for (var i = 0, len = data.success.length; i < len ; i++) {
					html += '<p>' + data.success[i].filename + '</p>';
				}
			}
			if (data.failed) {
				html += '<h2>Failed</h2>';
				html += '<p>Files failed: ' + data.failed + '</p>';
			}
			$("#dropzone-$id, #progressbar" ).progressbar( "destroy" );
			$("#dropzone-$id, #result").html(html);
			$("#dropzone-$id, #result").show();
		}
	});
EOT;
		$app->addClientScript($js);
		return '<div class="upload-container upload-' . $id . '">'
				. '<div id="dropzone-' . $id . '" class="drop-zone">'
				. '  Click here to choose images to upload' . '   <br />'
				. '   <span>Max 20 files, total 8mb and only image files</span>'
				. '  <div id="progressbar"></div>'
				. '  <div id="result"></div>' . '</div><hr class="divider"/>'
				. '<input type="file" id="' . $id
				. '" accept="image/gif, image/jpeg, image/png"></div>';
	}

	public static function renderMarkitupEditor($title, $id, $value = null,
			$configs = array(), $type = "html") {
		CGAFJS::loadPlugin('markitup/jquery.markitup');
		$mark = array(
				'js/jQuery/plugins/markitup/cgaf/' . $type . '/default/sets.js',
				'js/jQuery/plugins/markitup/cgaf/' . $type
						. '/default/style.css'
		);
		$appOwner = \AppManager::getInstance();
		$appOwner
				->AddClientScript(
						'$(\'#' . $id . '\').markItUp('
								. JSON::encodeConfig($configs) . ')');
		$appOwner->addClientAsset($mark);
		return self::renderTextArea($title, $id, $value);
	}
}
// +----------------------------------------------------------------------+
// | Copyright (c) 2001-2008 Liip AG |
// +----------------------------------------------------------------------+
// | Licensed under the Apache License, Version 2.0 (the "License"); |
// | you may not use this file except in compliance with the License. |
// | You may obtain a copy of the License at |
// | http://www.apache.org/licenses/LICENSE-2.0 |
// | Unless required by applicable law or agreed to in writing, software |
// | distributed under the License is distributed on an "AS IS" BASIS, |
// | WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or |
// | implied. See the License for the specific language governing |
// | permissions and limitations under the License. |
// +----------------------------------------------------------------------+
// | Author: Christian Stocker <christian.stocker@liip.ch> |
// +----------------------------------------------------------------------+

class lx_externalinput_clean {
	// this basic clean should clean html code from
	// lot of possible malicious code for Cross Site Scripting
	// use it whereever you get external input
	// you can also set $filterOut to some use html cleaning, but I don't know
	// of any code, which could
	// exploit that. But if you want to be sure, set it to eg.
	// array("Tidy","Dom");

	static function basic($string,
			$filterIn = array(
					"Tidy",
					"Dom",
					"Striptags"
			), $filterOut = "none") {
		$string = self::tidyUp($string, $filterIn);
		$string = str_replace(
				array(
						"&amp;",
						"&lt;",
						"&gt;"
				),
				array(
						"&amp;amp;",
						"&amp;lt;",
						"&amp;gt;"
				), $string);
		// fix &entitiy\n;
		$string = preg_replace('#(&\#*\w+)[\x00-\x20]+;#u', "$1;", $string);
		$string = preg_replace('#(&\#x*)([0-9A-F]+);*#iu', "$1$2;", $string);
		$string = html_entity_decode($string, ENT_COMPAT, "UTF-8");
		// remove any attribute starting with "on" or xmlns
		$string = preg_replace(
				'#(<[^>]+[\x00-\x20\"\'\/])(on|xmlns)[^>]*>#iUu', "$1>",
				$string);
		// remove javascript: and vbscript: protocol
		$string = preg_replace(
				'#([a-z]*)[\x00-\x20\/]*=[\x00-\x20\/]*([\`\'\"]*)[\x00-\x20\/]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iUu',
				'$1=$2nojavascript...', $string);
		$string = preg_replace(
				'#([a-z]*)[\x00-\x20\/]*=[\x00-\x20\/]*([\`\'\"]*)[\x00-\x20\/]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iUu',
				'$1=$2novbscript...', $string);
		$string = preg_replace(
				'#([a-z]*)[\x00-\x20\/]*=[\x00-\x20\/]*([\`\'\"]*)[\x00-\x20\/]*-moz-binding[\x00-\x20]*:#Uu',
				'$1=$2nomozbinding...', $string);
		$string = preg_replace(
				'#([a-z]*)[\x00-\x20\/]*=[\x00-\x20\/]*([\`\'\"]*)[\x00-\x20\/]*data[\x00-\x20]*:#Uu',
				'$1=$2nodata...', $string);
		// remove any style attributes, IE allows too much stupid things in
		// them, eg.
		// <span style="width: expression(alert('Ping!'));"></span>
		// and in general you really don't want style declarations in your UGC
		$string = preg_replace('#(<[^>]+[\x00-\x20\"\'\/])style[^>]*>#iUu',
				"$1>", $string);
		// remove namespaced elements (we do not need them...)
		$string = preg_replace('#</*\w+:\w[^>]*>#i', "", $string);
		// remove really unwanted tags
		do {
			$oldstring = $string;
			$string = preg_replace(
					'#</*(applet|meta|xml|blink|link|style|script|embed|object|iframe|frame|frameset|ilayer|layer|bgsound|title|base)[^>]*>#i',
					"", $string);
		} while ($oldstring != $string);
		return self::tidyUp($string, $filterOut);
	}

	static function tidyUp($string, $filters) {
		if (is_array($filters)) {
			foreach ($filters as $filter) {
				$return = self::tidyUpWithFilter($string, $filter);
				if ($return !== false) {
					return $return;
				}
			}
		} else {
			$return = self::tidyUpWithFilter($string, $filters);
		}
		// if no filter matched, use the Striptags filter to be sure.
		if ($return === false) {
			return self::tidyUpModuleStriptags($string);
		} else {
			return $return;
		}
	}

	static private function tidyUpWithFilter($string, $filter) {
		if (is_callable(array(
				"self",
				"tidyUpModule" . $filter
		))) {
			return call_user_func(
					array(
							"self",
							"tidyUpModule" . $filter
					), $string);
		}
		return false;
	}

	static private function tidyUpModuleStriptags($string) {
		return strip_tags($string);
	}

	static private function tidyUpModuleNone($string) {
		return $string;
	}

	static private function tidyUpModuleDom($string) {
		$dom = new \DOMDocument();
		@$dom->loadHTML("<html><body>" . $string . "</body></html>");
		$string = '';
		foreach ($dom->documentElement->firstChild->childNodes as $child) {
			$string .= $dom->saveXML($child);
		}
		return $string;
	}

	static private function tidyUpModuleTidy($string) {
		if (class_exists("\\tidy", false)) {
			$tidy = new \tidy();
			$tidyOptions = array(
					"output-xhtml" => true,
					"show-body-only" => true,
					"clean" => true,
					"wrap" => "350",
					"indent" => true,
					"indent-spaces" => 1,
					"ascii-chars" => false,
					"wrap-attributes" => false,
					"alt-text" => "",
					"doctype" => "loose",
					"numeric-entities" => true,
					"drop-proprietary-attributes" => true,
					"enclose-text" => false,
					"enclose-block-text" => false
			);
			$tidy->parseString($string, $tidyOptions, "utf8");
			$tidy->cleanRepair();
			return (string) $tidy;
		} else {
			return false;
		}
	}
}
?>
