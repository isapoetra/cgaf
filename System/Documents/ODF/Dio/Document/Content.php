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
use System\Documents\ODF\Dio\Office\Body;
use System\Documents\ODF\Dio\Document;
use System\Documents\ODF\Dio\Element;
class Content extends Document {
	protected $scripts;
	protected $fonts;
	protected $astyles;
	protected $body;
	private $_type;
	function __construct($type) {
		parent::__construct(new Element('office:document-content', null, Document::NS_OFFICE));
		$this->_type = $type;
		$this->scripts = $this->_root->addOffice_Scripts();
		$this->fonts = $this->_root->addOffice_FontFaceDecls();
		$this->astyles = $this->_root->addOffice_AutomaticStyles();
		$this->body = $this->_root->addOffice_Body($type);
	}
	function copyFonts($fonts) {
		foreach ($fonts as $f) {
			$font = $this->fonts->addFontFace($f->name, $f->adornments, $f->family, $f->pitch, $f->svgfamily);
		}
	}
	function loadXML($source, $options = 0) {
		parent::loadXML($source,$options);
		$this->_root = $this->getElementsByTagName('document-content')->item(0);
		//$this->normalizeDocument();
		$this->body = $this->_root->getElementsByTagName('body')->item(0);
	}
	function __get($name) {
		switch ($name) {
		case 'fonts':
			return $this->fonts;
		case 'body':
			return $this->body;
		case 'content':
			return $this->body->content;
		case 'astyles':
		case 'automaticStyles':
			return $this->astyles;
			break;
		default:
			return parent::__get($name);
		}
	}
}
