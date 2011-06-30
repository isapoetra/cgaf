<?php
interface IItem {
	/**
	 * 
	 * check if item equals with param ...
	 * @param mixed $item
	 * @return boolean
	 */
	function equals($item);
}