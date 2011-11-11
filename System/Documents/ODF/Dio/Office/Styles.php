<?php
namespace System\Documents\ODF\Dio\Office;
/* Dio - PHP OpenDocument Generator
 * Copyright (C) 2008  Étienne BERSAC <bersace03@gmail.com>
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Affero General Public License
 * as published by the Free Software Foundation, either version 3 of
 * the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this program.  If not, see
 * <http://www.gnu.org/licenses/>.
 */
use System\Documents\ODF\Dio\Document;
use System\Documents\ODF\Dio\Element;
class StyleUnknownException extends \Exception {
}
class Styles extends Element {
	protected $styles = array();
	function __construct($automatic = false) {
		parent::__construct('office:' . ($automatic ? 'automatic-' : '') . 'styles', null, Document::NS_OFFICE);
	}
	function addStyle($arg0) {
		$args = func_get_args();
		$style = parent::__call('addStyle', $args);
		$this->styles[$style->display_name] = $style;
		return $style;
	}
	function _postAppendChild() {
		$this->registerNameSpace('style', Document::NS_STYLE, true);
		foreach ($this->styles as $style)
			$this->appendChild($style);
	}
	function getStyle($name) {
		if (!isset($this->styles[$name]))
			throw new StyleUnknownException("Style " . $name . " is not defined.");
		return $this->styles[$name];
	}
	function __get($name) {
		if (isset($this->styles[$name]))
			return $this->styles[$name];
		else
			return NULL;
	}
}
