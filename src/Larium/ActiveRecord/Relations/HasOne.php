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
class HasOne extends Relation
{

    public function eagerLoad()
    {
        $collection = $this->find()->fetchAll();

        $attribute = $this->getRelationAttribute();

        foreach ($this->parent as $parent) {

            $detect = $collection->detect(
                $parent->{$this->getPrimaryKey()},
                $this->getForeignKey()
            );

            if ($detect) {
                $parent->getRelation($this->attribute)->assign($detect);
                $detect->getRelation($attribute)->assign($parent);
            } else {
                $parent->getRelation($this->attribute)->assign(new \Larium\ActiveRecord\Null());
            }
        }

        return $collection;
    }

    protected function assign($collection_or_record)
    {
        $this->result_set = $collection_or_record;
    }

    public function setRelated(Record $record)
    {
        $record->{$this->getForeignKey()} = $this->parent->{$this->getPrimaryKey()};
        $this->assign($record);
    }

    public function fetch($reload = false)
    {
        if (true === $reload || null === $this->result_set) {
            $this->result_set = $this->find()->fetch();
        }

        return $this->result_set;
    }

    public function find()
    {
        if (null == $this->query) {

            $relation_class = $this->relation_class;

            $where = array(
                $this->getForeignKey() => $this->getPrimaryKeyValue()
            );

            if ($this->options->polymorphic) {
                $type = $this->options->polymorphic['as'] . '_type';
                $where = array(
                    $this->getForeignKey() => $this->getPrimaryKeyValue(),
                    $type => $this->options->polymorphic['class']
                );
            }

            $this->query = $relation_class::find()->where($where);

            $this->options->setQuery($this->query);
        }

        return $this->query;
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

    public function saveDirty()
    {
        // code...
    }
}
