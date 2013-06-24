<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord\Relations;

use Larium\ActiveRecord\Record;
use Larium\ActiveRecord\CollectionInterface;

/**
 * OneToMany relation class
 * Required options:
 * - `record_name`
 * - `foreign_key`
 *
 */
class HasMany extends Collection
{

    /**
     * eagerLoad 
     * 
     * @access public
     * @return void
     */
    public function eagerLoad()
    {
        $collection = $this->find()->fetchAll();
        
        $attribute = $this->getRelationAttribute();

        //Loop through parent records and assign related objects
        foreach ($this->parent as $parent) {
            
            // select all records related to $parent.
            $select = $collection->select(
                $parent->{$this->getPrimaryKey()},
                $this->getForeignKey()
            );

            // Assign records to parent
            $parent->getRelation($this->attribute)->assign($select);

            // For selected records, assing inversed relation too.
            foreach ($select as $s) {
                $s->getRelation($attribute)->assign($parent);
            }
        }

        return $collection;
    }

    /**
     * Return a Collection with records of the mapped relationship.
     * 
     * @param boolean $reload 
     * @access public
     *
     * @return void
     */
    public function all($reload = false)
    {
        if (true === $reload || null === $this->result_set) {
            
            $collection = $this->find()->fetchAll();
            
            $attribute = $this->getRelationAttribute();

            foreach($collection as $item) {
                $item->getRelation($attribute)->assign($this->parent);
            }

            $this->result_set = $collection;

        }

        return $this->result_set;
    }

    public function find()
    {
        if (null == $this->query) {
            
            $relation_class = $this->relation_class;
            
            $this->query = $relation_class::find();

            if ($this->options->through) {
                
                $relation_class = $this->relation_class;
                $through = $this->options->through;

                $this->query->select(array(
                    $relation_class::$table . ".*",
                    $through::$table . "." . $this->getForeignKey())
                )->innerJoin(
                    $through::$table,
                    $through::$table . "." . $this->options->relation_foreign_key,
                    $relation_class::$table . "." . $this->getPrimaryKey()
                )->where(
                    array(
                       $through::$table . "." . $this->getForeignKey() => $this->getPrimaryKeyValue()
                    )
                );

            } else {

                $this->query->where(
                    array(
                        $this->getForeignKey() => $this->getPrimaryKeyValue()
                    )
                );               
            }
            $this->options->setQuery($this->query);
        }
        
        return $this->query;
    }

    public function add(Record $record, $offset = null)
    {
        $relation_class = $this->relation_class; 
        $ids = $this->all()->toArray($relation_class::$primary_key);
        
        $inversed_mutator = "set" . $this->getParentClass();
        $record->$inversed_mutator($this->parent);
        if (!in_array($record->{$relation_class::$primary_key}, $ids)) {
            $record->{$this->getForeignKey()} = $this->getPrimaryKeyValue();
            if ($record->save()) {
                $this->all()->offsetSet($offset, $record);
            }
        }
    }

    public function delete(Record $record, $offset = null)
    {

        $key = $offset ?: $this->getDeleteKey($record);

        switch ($this->options->cascade) {
            case 'nullify':
                $record->{$this->getForeignKey()} = null;
                $record->save();
                break;
            case 'delete':
            default:
                $record->destroy();
                break;
        }

        unset($this->result_set[$key]);
    }

    protected function assign($collection_or_record)
    {
        if ($collection_or_record instanceof CollectionInterface) {

            $this->result_set = $collection_or_record;

        } else if ($collection_or_record instanceof Record) {

            if (null === $this->result_set) {
                $this->result_set = Record::getAdapter()
                    ->createCollection(array(), get_class($collection_or_record));
            }

            $relation_class = $this->relation_class;
            $pk =$relation_class::$primary_key;
            $ids = $this->result_set->toArray($pk);

            if (!in_array($collection_or_record->$pk, $ids)) {
                $this->result_set[] = $collection_or_record;
            }
        }
    }

    public function setRelated(CollectionInterface $collection)
    {
        $this->assign($collection);
    }

    public function getPrimaryKey()
    {
        if (null == $this->primary_key){
            $record = $this->getParentClass();
            $this->primary_key = $record::$primary_key;
        }

        return $this->primary_key;
    }

    public function getPrimaryKeyValue()
    {

        if ($this->parent instanceof Record) {

            return $this->parent->{$this->getPrimaryKey()};
        } elseif ($this->parent instanceof CollectionInterface) {

            return array_unique($this->parent->toArray($this->getPrimaryKey())); 
        }
    }

    public function count()
    {
        return $this->all()->count();
    }
}
