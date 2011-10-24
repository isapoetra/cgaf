<?php

interface ISearchProvider {
	function name();
	/**
	 *
	 * @param $s
	 * @param $config
	 * @return TSearchItemCollection
	 */
	function search($s,$config);
}

?>