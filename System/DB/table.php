<?php
namespace System\DB;

use AppManager;
use CGAF;
use ReflectionClass;
use System\ACL\ACLHelper;
use System\Applications\IApplication;
use System\Exceptions\InvalidOperationException;
use Utils;

class Table extends DBQuery
{
    protected $_tableName;
    private $_pk;
    private $_includeAppId;
    protected $_notAllowNull = array();
    private $_infos;
    private $_appOwner;
    private $_alias;
    //private $_findResult;
    protected $_skipACL = false;
    protected $_isExpr = false;
    private $_filterACL = null;
    protected $_oldData;
    protected $_autoCreateTable = false;
    private $_cachedRows;

    /**
     * @param $connection
     * @param $tableName
     * @param string $pk
     * @param bool $includeAppId
     * @param null|bool $autoCreate
     */
    function __construct($connection, $tableName, $pk = "id",
                         $includeAppId = false, $autoCreate = null)
    {
        $this->_tableName = $tableName;
        if ($connection instanceof IApplication) {
            $this->_appOwner = $connection;
        } elseif (AppManager::isAppStarted()) {
            $this->_appOwner = AppManager::getInstance();
        }
        if ($autoCreate === null) {
            $autoCreate = $this->_appOwner->isInstalled() == false && $this->_appOwner->getConfig('app.installmode', false) == false;
        }
        $this->_autoCreateTable = $autoCreate;
        $pk = $pk ? $pk : array();
        $this->_pk = is_array($pk) ? $pk : explode(",", $pk);
        parent::__construct($connection);
        $this->_filterACL = \CGAF::isInstalled();
        $this->_includeAppId = $includeAppId;
        $this->Initialize();
    }

    protected function Initialize()
    {
        if (!$this->_infos) {
            $this->_infos = $this->getConnection()
                ->getObjectInfo($this->_tableName, "table", false);
        }
        if ($this->_infos == null && $this->_autoCreateTable) {
            $this->_createTable();
            $this->_infos = $this->getConnection()
                ->getObjectInfo($this->_tableName, "table", false);
        }
        $this->clear();
    }

    function setPKValue($value)
    {
        $pk = $this->getPK();
        if (is_string($value) || is_numeric($value)) {
            $value = explode(',', $value);
        }
        foreach ($pk as $k => $v) {
            $this->{$v} = $value[$k];
        }
        return $this;
    }

    /**
     *
     * @param bool $value
     * @return \System\DB\Table
     */
    public function setIncludeAppId($value)
    {
        $this->_includeAppId = $value;
        return $this;
    }

    /**
     * @param $alias
     * @return Table
     */
    function setAlias($alias)
    {
        $o = $this->quoteTable($this->getAlias()) . '.';
        $w = array();
        $na = $this->quoteTable($alias) . '.';
        foreach ($this->_where as $v) {
            $w[] = str_replace($o, $na, $v);
        }
        $this->_where = $w;
        $this->_alias = $alias;
        return $this;
    }

    function getAllowedRecords($uid, $fields = '*', $orderby = '',
                               $index = null, $extra = null)
    {
        $perms = ACLHelper::getInstance();
        $uid = intval($uid);
        if (!$uid) {
            exit(
                "FATAL ERROR<br />" . get_class($this)
                . "::getAllowedRecords failed");
        }
        $mod = DBUtil::toModule($this->_tbl, $this->_db);
        $deny = & $perms->getDeniedItems($mod, $uid);
        $allow = & $perms->getAllowedItems($mod, $uid);
        if (!$perms->checkModule($mod, "view", $uid)) {
            if (!count($allow)) {
                return array(); // No access, and no allow overrides, so nothing to show.
            }
        } else {
            $allow = array(); // Full access, allow overrides don't mean anything.
        }
        $this->_query->clear();
        $this->_query->addQuery($fields);
        $this->_query->addTable($this->_tbl);
        if (isset($extra['from'])) {
            $this->_query->addTable($extra['from']);
        }
        if (count($allow)) {
            $this->_query
                ->addWhere(
                    "$this->_tbl_key IN (" . implode(',', $allow) . ")");
        }
        if (count($deny)) {
            $this->_query
                ->addWhere(
                    "$this->_tbl_key NOT IN (" . implode(",", $deny)
                    . ")");
        }
        if (isset($extra['where'])) {
            $this->_query->addWhere($extra['where']);
        }
        if ($orderby) {
            $this->_query->addOrder($orderby);
        }
        return $this->_query->loadHashList($index);
    }

    function getAlias()
    {
        return $this->_alias ? $this->_alias : $this->_tableName;
    }

    /**
     * @return null|IApplication
     */
    function getAppOwner()
    {
        if ($this->_appOwner == null) {
            $this->_appOwner = AppManager::getInstance();
        }
        return $this->_appOwner;
    }

    protected function _createTable()
    {
        if ($this->_tableName == null) {
            return;
        }
        $retval = false;
        try {
            $retval = $this->getConnection()
                ->createDBObjectFromClass($this, 'table', $this->_tableName);
        } catch (\Exception $ex) {
            ppd($ex->getMessage());
        }
        return $retval;
    }

    /**
     *
     * @param $o
     */
    function bind($o)
    {
        Utils::bindToObject($this, $o);
        return $this;
    }

    /**
     * @param string $what
     * @return Table
     */
    function clear($what = 'all')
    {
        parent::clear($what);
        $this->_filterACL = $this->_filterACL === null ? CGAF::getConfig(
            'installed') : $this->_filterACL;
        $this->_skipACL = false;
        if ($what == 'all') {
            $fields = $this->getFields(true, false);
            foreach ($fields as $field) {
                if (!$field)
                    continue;
                if ($this->_infos) {
                    if (is_object($this->_infos)) {
                        $f = $this->_infos->getFieldInfo($field);
                    } else {
                        $f = isset($this->_infos[$field]) ? $this
                            ->_infos[$field] : null;
                    }
                    if ($f) {
                        $this->$field = $f->default_value;
                    }
                }
            }
        }
        $this->addTable($this->_tableName, $this->getAlias(), $this->_isExpr);
        return $this;
    }

    public function setFilterACL($value)
    {
        $this->_filterACL = $value;
        return $this;
    }

    protected function getAllField()
    {
        $fields = $this->getFields(false, false);
        if (count($fields) > 0) {
            $retval = array();
            foreach ($fields as $f) {
                $retval[] = $this->quoteField($f);
            }
            return implode(',', $retval);
        }
        return parent::getAllField();
    }

    public function setSkipACL($value)
    {
        $this->_skipACL = $value;
    }

    protected function filterACL($o)
    {
        if (is_array($o)) {
            $retval = array();
            foreach ($o as $v) {
                if ($o = $this->filterACL($o)) {
                    $retval[] = $o;
                }
            }
            return $retval;
        }
        return $o;
    }

    protected function getRowClass()
    {
        return '\stdclass';
    }

    /**
     * @param $id
     * @return Table
     */
    function whereId($id)
    {
        if (is_string($id) || is_numeric($id)) {
            $id = explode(",", $id);
        }
        foreach ($this->_pk as $k => $pk) {
            if (!isset($id[$k])) {
                throw new DBException('error.invalidid', $k);
            }
            $this->Where($this->quoteField($pk) . "=" . $this->quote($id[$k]));
        }
        return $this;
    }

    /**
     * @param null $id
     * @param bool $bindtothis
     * @return object
     */
    function load($id = null, $bindtothis = false)
    {
        $id = $id !== null ? $id : $this->getPKValue(false);
        if ($id !== null) {
            $this->clear();
            $this->whereId($id);
        }
        $rc = $bindtothis ? $this : $this->getRowClass();
        $retval = $this->loadObject($rc);
        $this->_cachedRows[$id] = $retval;
        return $retval;
    }

    function loadBy($fieldName, $value)
    {
        $this->clear();
        $this->where($fieldName . '=' . $this->quote($value));
        return $this->prepareOutput($this->loadObject($this->getRowClass()));
    }

    public function setAppOwner($owner)
    {
        $this->_appOwner = $owner;
    }

    protected function prepare($type = null)
    {
        if ($this->_includeAppId) {
            if ($this->_appOwner === null) {
                try {
                    if (AppManager::isAppStarted()) {
                        $this->_appOwner = AppManager::getInstance();
                    } else {
                        $this->_appOwner = '__cgaf';
                    }
                } catch (\Exception $e) {
                    throw $e;
                }
            }
            $this
                ->where(
                    $this->quoteField('app_id') . "="
                    . $this
                        ->quote(
                            is_object($this->_appOwner) ? $this
                                ->_appOwner
                                ->getAppId()
                                : $this->_appOwner));
        }
        return parent::prepare();
    }

    function LoadAll($page = -1, $rowPerPage = -1)
    {
        return $this->loadObjects(null, $page, $rowPerPage);
    }

    protected function prepareOutput($o)
    {
        if (!$this->_filterACL) {
            return $o;
        }
        return $this->filterACL($o);
    }

    function getFields($force = false, $withValue = false, $quote = true)
    {
        if ($this->_fields == null || $force || $withValue) {
            $c = new ReflectionClass($this);
            $props = $c->getProperties();
            $this->_fields = array();
            foreach ($props as $prop) {
                $name = $prop->getName();
                if ($name[0] != "_") {
                    if ($withValue) {
                        $this->_fields[$name] = $quote ? $this
                            ->toFieldString($name, $this->$name)
                            : $this->$name;
                    } else {
                        $this->_fields[] = $name;
                    }
                }
            }
        }
        return $this->_fields;
    }

    public function toFieldString($fieldName, $value)
    {
        if ($info = $this->getFieldInfo($fieldName)) {
            return $value !== null ? $info->toString($value) : null;
        }
        return $value !== null ? $this->quote($value) : null;
    }

    protected function isAllowNull($field)
    {
        if ($this->_infos != null) {
            $fields = $this->getFieldInfo($field);
            if (!is_object($fields)) {
                ppd($field);
            }
            return $fields->allow_null;
        }
        foreach ($this->_notAllowNull as $v) {
            if ($v === $field) {
                return false;
            }
        }
        return true;
    }

    function addError($msg, $id = null)
    {
        if ($id) {
            $old = isset($this->_lastError[$id]) ? $this->_lastError[$id] : null;
            $msg = $old . PHP_EOL . $msg;
            $this->_lastError[$id] = $msg;
        } else {
            $this->_lastError[] = __($msg);
        }
    }

    protected function getCheckMode($mode = null)
    {
        if (!$mode) {
            $mode = $this->getPKValue() !== null ? self::MODE_INSERT
                : self::MODE_UPDATE;
        }
        return $mode;
    }

    function check($mode = null)
    {
        $retval = false;
        $mode = $this->getCheckMode($mode);
        switch (strtolower($mode)) {
            case self::MODE_INSERT:
                $fields = $this->getFields();
                foreach ($fields as $f) {
                    if ($this->isAllowNull($f))
                        continue;
                    if (($this->$f === null || trim($this->$f) === '')) {
                        $this
                            ->addError(
                                sprintf(
                                    __("nulledvalue",
                                        "Invalid Value for field %s"),
                                    __(
                                        $this->_tableName . '.'
                                        . $f, $f)));
                        return false;
                    }
                }
                $retval = true;
                break;
            case self::MODE_UPDATE:
                $fields = $this->getFields();
                foreach ($fields as $f) {
                    if (!in_array($f, $this->_pk) && $this->$f === null
                        && !$this->isAllowNull($f)
                    ) {
                        $this
                            ->addError(
                                vsprintf(
                                    __("nulledvalue",
                                        "null Value for field %s"),
                                    __($this->_tableName . '.' . $f)));
                        return false;
                    }
                }
                $retval = true;
                break;
            case self::MODE_DELETE;
                break;
            default:
                ppd($mode);
        }
        return count($this->_lastError) == 0;
    }

    function getPK()
    {
        return $this->_pk;
    }

    function getPKValue($asArray = false, $o = null)
    {
        $retval = array();
        $o = $o ? $o : $this;
        foreach ($this->_pk as $pk) {
            if (@$o->$pk !== null) {
                $retval[$pk] = $o->$pk;
            }
        }
        return $asArray ? $retval
            : (empty($retval) ? null : implode(",", $retval));
    }

    /**
     * @param $field
     * @return null | DBFieldInfo
     */
    function getFieldInfo($field)
    {
        if (is_object($this->_infos)) {
            return $this->_infos->getField($field);
        }
        return isset($this->_infos[$field]) ? $this->_infos[$field] : null;
    }

    function resetToDefaultValue($fields)
    {
        if (is_array($fields)) {
            foreach ($fields as $field) {
                $this->resetToDefaultValue($field);
            }
        }
        $fi = $this->getFieldInfo($fields);
        if ($fi) {
            $this->$fields = $fi->default_value;
        }
    }

    function store($throw = true)
    {
        $this->_lastError = array();
        $mode = self::MODE_INSERT;
        $pks = $this->getPKValue();
        $this->_oldData = null;
        if ($pks !== null && $pks !== '') {
            $q = new DBQuery($this->getConnection());
            $q->addTable($this->_tableName, $this->getAlias(), $this->_isExpr);
            $id = explode(",", $pks);
            foreach ($this->_pk as $k => $pk) {
                if (!isset($id[$k])) {
                    pp($id);
                    ppd($this->_pk);
                }
                $q->Where($pk . "=" . $this->quote($id[$k]));
            }
            $o = $q->loadObject();
            if ($o != null) {
                $this->_oldData = $o;
                $diff = Utils::isDiff($o, $this);
                if ($diff) {
                    //ppd($this->sysmessage);
                    foreach ($o as $k => $v) {
                        $f = $this->getFieldInfo($k)->default_value;
                        if ($this->$k === null || $this->$k === $f) {
                            $this->$k = $v ? $v : $f;
                            //$diff = true;
                        }
                    }
                } else {
                    if ($throw && CGAF_DEBUG) {
                        throw new UnchangedDataException(
                            __("store.nothingchanged",
                                'Unable to store unchanged data'));
                    } else {
                        return true;
                    }
                }
                $mode = self::MODE_UPDATE;
            }
        }
        if (!$this->check($mode)) {
            if ($throw) {
                throw new DBException("store.failed",
                    ',' . implode($this->_lastError, " "));
            } else {
                return false;
            }
        }
        if ($mode == self::MODE_INSERT) {
            $pk = $this->getPK();
            foreach ($pk as $p) {
                if ($this->$p === null || $this->$p === '') {
                    $this->$p = null;
                }
            }
        }
        $fields = $this->getFields(true, true);
        if ($fields != null) {
            if ($mode == self::MODE_INSERT) {
                $this->clear();
            } else {
                $this->clear('table');
            }
            foreach ($fields as $k => $v) {
                if ($mode == self::MODE_INSERT) {
                    if ($v !== null) {
                        $this->addInsert($k, $v, true);
                    }
                } else {
                    if ($v !== null) {
                        $this->Update($k, $v, "=", true);
                    } else {
                        $this->Update($k, 'null', "=", true);
                    }
                }
            }
        }
        if ($mode == self::MODE_UPDATE) {
            $pk = $this->getPKValue(true);
            $this->setAlias($this->_tableName);
            foreach ($pk as $k => $v) {
                $this->Where($this->quoteField($k) . "=" . $this->quote($v));
            }
        }
        $retval = $this->exec();
        if ($mode == self::MODE_INSERT) {
            $id = $retval->getLastInsertId();
            if ($id) {
                $this->load($id, true);
            } else {
                $this->load($pks, true);
            }
        } else {
            $this->load(null, true);
        }
        $this->afterStore($mode, $this->_oldData);
        return $retval;
    }

    protected function afterStore($mode, $old = null)
    {
    }

    protected function onDelete($id)
    {
    }

    /**
     * Unused parameter,just for compatibility with E_STRICT
     * (non-PHPdoc)
     * @see System\DB.DBQuery::drop()
     *
     */

    public function drop($object = null, $what = "table")
    {
        return parent::drop($this->getTableName(false, false), 'table');
    }

    public function delete()
    {
        $q = new DBQuery($this->getConnection());
        $q->addTable($this->_tableName, null, $this->_isExpr);
        $q->setMode('delete');
        $pk = $this->getPKValue(true);
        if ($pk) {
            foreach ($pk as $k => $v) {
                $q->Where("$k=" . $this->quote($v));
            }
        } elseif (count($this->_where)) {
            foreach ($this->_where as $v) {
                $q->Where($v[0], $v[1]);
            }
        } else {
            throw new InvalidOperationException(
                'deleting all data is not aloowed');
        }
        if ($q->exec()) {
            $this->onDelete($pk);
            return true;
        }
        return false;
    }

    public function getRowCount()
    {
        $q = new DBQuery($this->getConnection());
        $q->addTable($this->_tableName, $this->getAlias(), $this->_isExpr);
        $q->select("count(*) as count")->where($this->getWhere());
        $q->setJoin($this->_join);
        $o = $q->loadObject();
        return $o->count;
    }

    function find($field, $val)
    {
        return $this->clear()->where($field . '=' . $this->quote($val))
            ->loadObject();
    }

    function search($text, $field = null, $config = null, $clear = true)
    {
        if (!$text) {
            return array();
        }
        $field = $field === null ? $this->getFields(false, false) : $field;
        if (is_string($field)) {
            $field = array(
                $field
            );
        }
        if ($clear) $this->clear();
        foreach ($field as $f) {
            if (is_array($f)) {
                //TODO search config based on field;
            } else {
                $this
                    ->where(
                        $f . ' like \'%' . $this->quote($text, false)
                        . '%\'', ' or');
            }
        }
        return $this->loadObjects(null, \Request::get('__p', 0), \Request::get('__rpp', 10));
    }

    protected function getGridColsWidth()
    {
        return array(
            'app_id' => 230
        );
    }

    function getGridColumns($gridId)
    {
        $retval = array();
        $o = $this->resetgrid($gridId)->loadObject();
        if ($o) {
            $fields = $this->getFields();
            foreach ($o as $field => $v) {
                $info = $this->getFieldInfo($field);
                $fi = array(
                    "value" => "#{$field}#"
                );
                if ($info) {
                    $fi['width'] = $info->field_width;
                }
                $retval[$field] = $fi;
            }
        } else {
            if ($this->getLastError()) {
                throw new DBException($this->getLastError());
            }
            $fields = $this->getFields();
            foreach ($fields as $field) {
                if (!is_array($field)) {
                    $retval[$field] = array(
                        "value" => "#{$field}#"
                    );
                } else {
                    //TODO trouble when rowcount ==0 && data come from direct query
                    //ppd($fields);
                }
            }
        }
        $cw = $this->getGridColsWidth();
        //ppd($retval);
        foreach ($retval as $k => $v) {
            if (array_key_exists($k, $cw)) {
                $retval[$k]['width'] = $cw[$k];
            }
        }
        return $retval;
    }

    public function loadCached($id)
    {
        if ($id === null)
            return null;
        $pk = $this->getPK();
        if (!isset($this->_cachedRows[$id])) {
            $this->load($id, false);
        }
        return isset($this->_cachedRows[$id]) ? $this->_cachedRows[$id] : null;
    }

    public function quoteField($fields)
    {
        if (is_string($fields)) {
            if (strpos($fields, '.') > 0) {
                return parent::quoteField($fields);
            }
            if (strpos($fields, ',') === false) {
                $fields = $this->getAlias() ? parent::quoteField(
                        $this->getAlias()) . '.'
                    . parent::quoteField($fields)
                    : parent::quoteField($fields);
            } else {
                return $fields;
            }
            return $fields;
        }
        return parent::quoteField($fields);
    }
}

?>