<?php
namespace System\Web\UI\Ext;

/*
 * File    : columntree.php
 * Created : Iwan Sapoetra - May 8, 2008
 * Project : CGAFCore
 * Package : package_name
 *
 */
use System\Web\UI\Ext\Control;

if (!defined("CGAF"))
    die("Restricted Access");
class JExtColumnTree extends Control
{

    function __construct()
    {
        parent::__construct("Ext.tree.ColumnTree");
        $this->_controlScript = array("id" => "columntree", "url" => \CGAF::findLiveFile("treecolumn.js", "js"));
        $this->addIgnoreConfigStr(array("columns", "loader", "root"));
    }
}

?>
