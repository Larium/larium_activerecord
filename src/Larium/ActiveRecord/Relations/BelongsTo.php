<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord\Relations;

use Larium\ActiveRecord\Record;
use Larium\ActiveRecord\CollectionInterface;

/**
 * ManyToOne relation class
 *
 * Required options:
 * - `record_name`
 * - `foreign_key`
 * - `inversed_by`
 *
 */
class BelongsTo extends Relation
{

    public function eagerLoad()
    {
        $collection = $this->find()->fetchAll();

        $attribute = $this->getRelationAttribute();

        foreach ($this->parent as $parent) {

            $detect = $collection->detect(
                $parent->{$this->getForeignKey()},
                $this->getPrimaryKey()
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
                $this->getPrimaryKey() => $this->getForeignKeyValue()
            );

            if (true === $this->options->polymorphic) {
                $type = $this->getRelationAttribute() . '_type';
                $where = array(
                    $this->getPrimaryKey() => $this->getForeignKeyValue(),
                    $type => $this->getPolymorphicTypeValue($type)
                );
            }

            $this->query = $relation_class::find()->where($where);

            $this->options->setQuery($this->query);
        }

        return $this->query;

    }

    protected function assign($collection_or_record)
    {
        $this->result_set = $collection_or_record;
    }

    public function setRelated(Record $record)
    {
        $this->parent->{$this->getForeignKey()} = $record->{$this->getPrimaryKey()};
        $this->assign($record);
    }

    public function getPrimaryKey()
    {
        if (null == $this->primary_key){
            $record = $this->getRelationClass();
            $this->primary_key = $record::$primary_key;
        }

        return $this->primary_key;
    }

    public function getForeignKeyValue()
    {

        if ($this->parent instanceof Record) {

            return $this->parent->{$this->getForeignKey()};
        } elseif ($this->parent instanceof CollectionInterface) {

            return array_unique(array_filter($this->parent->toArray($this->getForeignKey())));
        }
    }

    public function getPolymorphicTypeValue($type)
    {
        if ($this->parent instanceof Record) {

            return $this->parent->$type;
        } elseif ($this->parent instanceof CollectionInterface) {

            return array_unique(array_filter($this->parent->toArray($type)));
        }
    }

    public function saveDirty()
    {
    }

    public function destroy()
    {
        if ($this->options->dependent == 'destroy') {
           return $this->fetch()->destroy();
        }
    }
}
