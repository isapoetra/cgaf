<?php

interface ISearchEngine {
	function doSearch(IController $controler);
	/**
	 * @return IApplication
	 */
	function getAppOwner();
}

?>