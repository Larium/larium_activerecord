<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord\Relations;

use Larium\ActiveRecord\Record;

/**
 * ManyToMany relation class
 * Required options:
 * - `record_name`
 * - `foreign_key`
 * - `relation_foreign_key`
 * - `join_table`
 *
 */
class ManyToMany extends HasMany
{
    const THE_PARENT_ID = "the_parent_id";

    protected $relation_foreign_key;

    protected $join_table;

    public function __construct($attribute, $parent, $options=array())
    {
        parent::__construct($attribute, $parent, $options);
        
        $this->options->setRelationClass(__CLASS__);

        $this->relation_foreign_key = $this->options->relation_foreign_key;
        $this->join_table = $this->options->join_table;
    }

    /**
     * eagerLoad 
     * 
     * @access public
     * @return void
     */
    public function eagerLoad()
    {
        $collection = $this->find()->fetchAll();

        // Get the name of attribute that has been assigned for the inversed 
        // relation.
        $attribute = $this->getRelationAttribute();

        $map = array();
        $relation_class = $this->relation_class;
        $pk = $relation_class::$primary_key;

        foreach ($this->parent as $parent) {
 
            $select = $collection->select(
                $parent->{$this->getPrimaryKey()},
                self::THE_PARENT_ID
            );

            $parent->getRelation($this->attribute)->assign($select);
            
            foreach ($select as $s) {
                $s->getRelation($attribute)->assign($parent);
            }
        }

        $group = array();
        foreach ($collection as $item) {
            if (!isset($group[$item->$pk])) {
                $group[$item->$pk] = $item;
            }

            $group[$item->$pk]->getRelation($attribute)->assign(
                $this->parent->detect($item->{self::THE_PARENT_ID}, $this->getPrimaryKey())
            );
        }

        return $collection; 
    }

    public function all($reload = false)
    {
        if (true === $reload || null === $this->result_set) {
            
            $this->result_set = $this->find()->fetchAll();
        }

        return $this->result_set;
    }

    public function find()
    {
        if (null == $this->query) {
            
            $relation_class = $this->relation_class;
            
            $this->query = $relation_class::find()
                ->select("{$relation_class::$table}.*, {$this->join_table}.{$this->getForeignKey()} as ".self::THE_PARENT_ID)
                ->innerJoin(
                    $this->join_table, 
                    $this->join_table . "." . $this->relation_foreign_key,
                    $relation_class::$table .".". $this->getPrimaryKey()
                )
                ->where(
                    array(
                        "{$this->join_table}.{$this->getForeignKey()}" => $this->getPrimaryKeyValue()
                    )
                );

            $this->options->setQuery($this->query);
        }
        
        return $this->query;
    }

    public function add(Record $record, $offset=null)
    {
        $relation_class = $this->relation_class; 
        $ids = $this->all()->toArray($relation_class::$primary_key);

        if (!in_array($record->{$relation_class::$primary_key}, $ids)) {

            if ($record->isNewRecord()) {
                $record->save();
            }

            $query = Record::getAdapter()->createQuery();
            $insert = $query->insert(
                $this->join_table,
                array(
                    $this->relation_foreign_key => $record->{$relation_class::$primary_key},
                    $this->foreign_key => $this->getPrimaryKeyValue()
                )
            );
            
            if ($insert) {
                $this->all()->offsetSet($offset, $record);
            }
        }
    }

    public function delete(Record $record, $offset=null)
    {
        $relation_class = $this->relation_class; 
        $key = $offset ?: $this->getDeleteKey($record);
        
        $query = Record::getAdapter()->createQuery();

        $delete = $query->delete(
            $this->join_table,
            array(
                $this->relation_foreign_key => $record->{$relation_class::$primary_key},
                $this->foreign_key => $this->getPrimaryKeyValue() 
            )
        );

        if ($delete) {
            unset($this->result_set[$key]);
        }
    }
}
