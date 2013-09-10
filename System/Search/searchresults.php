<?php
namespace System\Search;

use System\Collections\Collection;

class SearchResults extends Collection
{
    function __construct()
    {
        parent::__construct(null, false, '\\' . __NAMESPACE__ . '\\SearchResult');
    }
}
