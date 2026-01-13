<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord;

use Larium\ActiveRecord\Record;
use Larium\ActiveRecord\CollectionInterface;

class Collection extends \IteratorIterator implements
    CollectionInterface,
    \Countable
{

    public static $BATCH_SIZE = 1000;

    private $page_size    = 0;
    private $current_page = 0;

    /**
     * Deleted elements from result set
     *
     *
     * @var array
     */
    private $deleted = array();

    /**
     * The class name of the record which execute query.
     *
     * @var string
     */
    protected $record;

    /**
     * The result set of query.
     *
     * An array that contains elements for iteration.
     *
     * @var array
     */
    protected $results = array();


    public function __construct(\Iterator $iterator, $record=null)
    {
        parent::__construct($iterator);
        $this->record = $record;
        $this->hydrate();

    }

    public function current(): mixed
    {
        return current($this->results);
    }

    public function key(): mixed
    {
        return key($this->results);
    }

    public function next(): void
    {
        if ($this->key() >= ($this->current_page + 1) * static::$BATCH_SIZE ) {
            $this->current_page++;
            $this->hydrate();
        }
        next($this->results);

    }

    public function rewind(): void
    {
        if ($this->current_page !== 0 )
            $this->hydrate();
        $this->current_page = 0;
        reset($this->results);
    }

    public function valid(): bool
    {
        return (current($this->results) !== false);
    }

    public function count(): int
    {
        return count($this->results);
    }

    public function offsetExists($key): bool
    {
        if ($key > $this->_current_max_offset()) {
            $this->current_page = floor($key/static::$BATCH_SIZE);
            $this->hydrate();
        }
        return isset($this->results[$key]);
    }

    public function offsetGet($key): mixed
    {
        if ($key > $this->_current_max_offset()
            || $key < $this->_current_max_offset() - static::$BATCH_SIZE)
        {
            $this->current_page = floor($key/static::$BATCH_SIZE);
            $this->hydrate();
        }
        return $this->results[$key];
    }

    public function offsetSet($key, $value): void
    {
        if ($key === null) {
            $k = array_search($key, $this->deleted);
            if (false!==$k) unset($this->deleted[$k]);
            $this->results[] = $value;
            return ;
        } else {
            $this->results[$key] = $value;
            return;
        }
    }

    public function offsetUnset($key): void
    {
        $this->deleted[] = $key;
        unset($this->results[$key]);
    }

    public function add($value)
    {
        return $this->offsetSet(null, $value);
    }

    /**
     * Returns first result in set
     */
    public function first()
    {
        return reset($this->results) ?: null;
    }

    /**
     * Returns last result in set
     */
    public function last()
    {
        return end($this->results) ?: null;
    }

    public function isEmpty()
    {
        return empty($this->results);
    }

    public function map(\Closure $block)
    {
        return new self(new \ArrayIterator(array_map($block, array_keys($this->results), $this->results)));
    }

    public function filter(\Closure $block)
    {
        return new self(new \ArrayIterator(array_filter($this->results, $block)));
    }

    public function each_with_index(\Closure $block)
    {
        foreach ($this->results as $key => $value) {
            $block($key, $this->results[$key]);
        }
    }

    public function each(\Closure $block)
    {
        foreach ($this->results as $key => $value) {
            $block($this->results[$key]);
        }
    }

    public function getResults()
    {
        return $this->results;
    }

    public function toArray($keyColumn=null, $valueColumn=null)
    {
        // Both empty
        if (null === $keyColumn && null === $valueColumn) {
            $return = array();

            foreach($this->results as $k=>$row) {

                if ($v = $row->getAttributes()) {
                    $return[$k] = $v;
                }

            }

            // Key column name
        } elseif (null !== $keyColumn && null === $valueColumn) {
            $return = array();
            foreach ($this->results as $k=>$row) {
                if (isset($row->$keyColumn))
                    $return[$k] = $row->$keyColumn;
            }

            // Both key and value columns filled in
        } else {
            $return = array();
            foreach ($this->results as $row) {
                $return[$row->$keyColumn] = $row->$valueColumn;
            }
        }

        return $return;
    }

    public function toJson($keyColumn=null, $valueColumn=null)
    {
        return json_encode($this->toArray($keyColumn, $valueColumn));
    }

    public function select($search_value, $field_value, \Closure $closure = null)
    {

        $array = array_filter(
            $this->results,
            function($row) use ($search_value, $field_value, $closure){
                if ($closure) {
                    $closure($row);
                }

                return $search_value == $row->$field_value;
            });

        return new static(new \ArrayIterator(array_values($array)), $this->record);
    }

    /**
     * @param $search_value
     * @param $field_value
     * @return Record|null
     */
    public function detect($search_value, $field_value)
    {
        $array = array_filter($this->results, function($row) use ($search_value, $field_value){
            return $search_value == $row->$field_value;
        });

        return reset($array) ?: null;
    }

    public function delete($search_value, $field_value)
    {
        $array = array();
        $array = array_filter($this->results, function($row) use ($search_value, $field_value){
            return $search_value == $row->$field_value;
        });
        if (!empty($array)) {
            $key = key($array);
            unset($this->results[$key]);
        }
    }

    public function getRecord()
    {
        return $this->record;
    }

    private function _current_max_offset()
    {
        return ($this->current_page + 1) * static::$BATCH_SIZE;
    }

    // Should be called once per page
    private function hydrate()
    {
        $record = $this->record;
        /** @var \ArrayAccess $rows */
        $rows   = $this->getInnerIterator();
        $offset = $this->current_page * static::$BATCH_SIZE;
        $this->results = array();
        for ( $i=$offset; $i <= ($this->current_page + 1) * static::$BATCH_SIZE; $i++ ) {
            if (in_array($i, $this->deleted)) continue;
            if ($rows->offsetExists($i)) {
                $this->results[$i] = ($rows[$i] instanceof Record)
                    ? $rows[$i]
                    : $record::initWith($rows[$i]);
            }
        }
    }

}
