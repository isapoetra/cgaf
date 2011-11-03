<?php
namespace System\Documents\ODF\Dio\Document;
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
use System\Documents\ODF\Dio\Element;
use System\Documents\ODF\Dio\Document;
class Styles extends Document {
	protected $fonts;
	protected $styles;
	protected $astyles;
	function __construct($root = null) {
		if (!$root)
			$root = new Element('office:document-styles', null, Document::NS_OFFICE);
		parent::__construct($root);
		$this->fonts = $this->_root->addOffice_FontFaceDecls();
		$this->styles = $this->_root->addOffice_Styles();
		$this->astyles = $this->_root->addOffice_AutomaticStyles();
	}
	function __get($name) {
		switch ($name) {
		case 'fonts':
		case 'styles':
		case 'astyles':
			return $this->$name;
			break;
		default:
			return parent::__get($name);
		}
	}
}
