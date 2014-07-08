<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord\Mock;

use Larium\Database\ResultIteratorInterface;

class ResultIterator implements ResultIteratorInterface, \ArrayAccess
{

    protected $result_set;

    private $index = 0;

    private $fetch_style = Adapter::FETCH_OBJ;

    private $object = '\\stdClass';

    private $arg;

    private $fetch_methods = array(
        Adapter::FETCH_OBJ   => 'fetch_object',
        Adapter::FETCH_ASSOC => 'fetch_array',
    );

    /**
     *
     * @param array $result_set
     * @param int   $fetch_style AdapterInterface::FETCH_OBJ or
     *                           AdapterInterface::FETCH_ASSOC
     * @param mixed $object      the name of the class to instantiate
     *                           when fetch style is
     *                           AdapterInterface::FETCH_OBJ
     *
     * @return ResultIterator
     */
    public function __construct(
        array $result_set,
        $fetch_style = Adapter::FETCH_OBJ,
        $object = '\\stdClass'
    ) {

        $this->result_set = $result_set;
        $this->fetch_style = $fetch_style ?: $this->fetch_style;
        $this->object = $object ?: '\\stdClass';
    }

    public function current()
    {
        return $this->result_set[$this->index];
    }

    public function key()
    {
        return $this->index;
    }

    public function next()
    {
        $this->index++;
    }

    public function rewind()
    {
        $this->index = 0;
    }

    public function valid()
    {
        return $this->key() < $this->count();
    }

    public function count()
    {
        return count($this->result_set);
    }

    public function offsetExists($key)
    {
        return $key < $this->count();
    }

    public function offsetGet($key)
    {
        return $this->result_set[$key];
    }

    public function offsetSet($key, $value)
    {
        return false;
    }

    public function offsetUnset($key)
    {
        return false;
    }

    public function getResultSet()
    {
        return $this->result_set;
    }

    public function getResult()
    {
        $result = array();

        foreach ($this as $row) {
            $result[] = $row;
        }

        return $result;
    }
}
