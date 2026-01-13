<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord\Relations;

use Larium\ActiveRecord\CollectionInterface;
use Larium\ActiveRecord\Record;
use Traversable;

abstract class Collection extends Relation implements
    RelationCollectionInterface,
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

    public function getIterator(): CollectionInterface
    {
        return $this->all();
    }

    // ArrayAccess

    public function offsetExists($offset): bool
    {
        return $this->all()->offsetExists($offset);
    }

    public function offsetGet($offset): mixed
    {
        return $this->all()->offsetGet($offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->add($value, $offset);
    }

    public function offsetUnset($offset): void
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
