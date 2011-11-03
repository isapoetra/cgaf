<?php
namespace System\Documents\ODF\Dio\Text;
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
use System\Documents\ODF\Dio\Office\Text;
class H extends Text {
	protected $level;
	function __construct($content = '', $level = 1) {
		parent::__construct('text:h', $content);
		$this->level = $level;
	}
	function _postAppendChild() {
		$style = 'Heading_' . $this->level;
		$this->setStyle($style);
	}
}
