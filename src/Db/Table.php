<?php
/**
 * Db Table
 *
 */

namespace Core\Db;

use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\AbstractTableGateway;

class Table extends AbstractTableGateway
{

    protected $table;

    protected $rowClass;

    protected $primaryKey;


    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;

        $this->getTableName();

        $rowClass = ($this->rowClass) ? $this->rowClass : 'Core\Db\Model';

        $this->resultSetPrototype = new ResultSet();
        $this->resultSetPrototype->setArrayObjectPrototype(new $rowClass());
//        $this->resultSetPrototype->setArrayObjectPrototype(new Model());

        $this->initialize();
    }

    public function getRowClass()
    {
        return $this->rowClass;
    }

    public function getTableName()
    {
        if (is_null($this->table)) {
            $class_name = strtolower(get_class($this));

            $this->table = str_replace('\\model\\table\\', '_', $class_name);
        }

        return $this->table;
    }

    public function beginTransaction()
    {
        return $this->getAdapter()->getDriver()->getConnection()->beginTransaction();
    }

    public function getPrimaryKey()
    {
        if (is_null($this->primaryKey)) {
            $tmp = explode('\\', get_class($this));
            $class_name = strtolower($tmp[count($tmp) - 1]);
            $class_name = substr($class_name, 0, -1);
            $this->primaryKey = $class_name . '_id';
        }
        return $this->primaryKey;
    }

    public function find($id)
    {
        $where = array($this->getPrimaryKey() => $id);
        $rowset = $this->select($where);
        return $rowset->current();
    }

    /**
     *
     * @return \Zend\Db\Sql\Select
     */
    public function getSelect($table = null)
    {
        return $this->getSql()->select($table);
    }

    /**
     *
     * @param \Zend\Db\Sql\Select|NULL $select
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function fetchAll($select = null)
    {
        if (is_null($select)) {
            $select = $this->getSelect();
        }

        return $this->selectWith($select);
    }

    public function fetchRow($select = null)
    {
        if (is_null($select)) {
            $select = $this->getSelect();
        } elseif (is_array($select)) {
            $tmp = $this->getSelect();
            foreach ($select as $key => $value) {
                $tmp->where("{$key} = '{$value}'");
            }
            $select = $tmp;

        }

        return $this->selectWith($select)->current();
    }

    public function createRow($params = array(0))
    {
        $this->insert($params);
        return $this->fetchRow(array($this->getPrimaryKey() => $this->getLastInsertValue()));
    }


}

?>
