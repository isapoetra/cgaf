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
class Body extends Element {
	protected $content;
	protected $type;
	function __construct($type) {
		parent::__construct('office:body', null, Document::NS_OFFICE);
		$this->type = $type;
	}
	function _postAppendChild() {
		$cclass = self::getContentClass($this->type);
		$content = new $cclass;
		$this->content = $this->appendChild($content);
	}
	static function getContentClass($type) {
		$class = __NAMESPACE__ . '\\';
		switch ($type) {
		case Document::TYPE_TEXT:
			$class .= 'Text';
			break;
		case Document::TYPE_SPREADSHEET:
			$class .= 'Spreadsheet';
			break;
		case Document::TYPE_CHART:
			$class .= 'Chart';
			break;
		default:
			throw new \Exception("Unkown type '" . $type . "'.");
		}
		return $class;
	}
	function __get($name) {
		switch ($name) {
		case 'automaticStyles':
		case 'astyles':
			$name = 'astyles';
		case 'content':
			return $this->$name;
			break;
		}
	}
}
