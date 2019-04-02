<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord\Relations;

use Larium\ActiveRecord\Record;

abstract class Collection extends Relation implements 
    RelationCollectionInterface, 
    \IteratorAggregate, 
    \ArrayAccess
{
    
    public function first()
    {
        return $this->all()->first();
    }

    public function last()
    {
        return $this->all()->last();
    }

    public function isEmpty()
    {
        return $this->getIterator()->isEmpty();
    }
    
    // IteratorAggregate
    
    public function getIterator()
    {
        return $this->all();
    }

    // ArrayAccess

    public function offsetExists($offset) 
    {
        return $this->all()->offsetExists($offset);
    }

    public function offsetGet($offset)
    {
        return $this->all()->offsetGet($offset); 
    }

    public function offsetSet($offset, $value)
    {
        $this->add($value, $offset);
    }

    public function offsetUnset($offset)
    {
        $object = $this[$offset];
        $this->delete($object, $offset);
    }

    protected function getDeleteKey(Record $record)
    {
        foreach($this->all() as $key => $element) {
            if ($element === $record) {
                return $key;
            }
        }
    }
}
