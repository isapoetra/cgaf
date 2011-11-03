<?php
namespace System\Documents\ODF\Dio\Style\Properties;
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
namespace System\Documents\ODF\Dio\Style\Properties;
use System\Documents\ODF\Dio\Document;
use System\Documents\ODF\Dio\Element;
class TableCell extends Element {
	function __construct() {
		parent::__construct('style:table-cell-properties', null, Document::NS_STYLE);
	}
	function setDecimalPlaces($place) {
		$this->setAttribute('style:decimal-places', $place);
	}
}
