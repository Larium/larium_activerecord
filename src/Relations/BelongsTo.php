<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord\Relations;

use Larium\ActiveRecord\CollectionInterface;
use Larium\ActiveRecord\Record;

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
                $parent->getRelation($this->attribute)->assign(new \Larium\ActiveRecord\NullObject());
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
            $foreignKeyValue = $this->getForeignKeyValue();
            if (is_array($foreignKeyValue) && empty($foreignKeyValue)) {
                $foreignKeyValue = null;
            }

            // For polymorphic associations, resolve the target class from the type column
            if (true === $this->options->polymorphic) {
                $typeColumn = $this->attribute . '_type';
                $typeValue = $this->getPolymorphicTypeValue($typeColumn);
                
                // Resolve the target class dynamically from the type value
                if ($typeValue) {
                    // If type value is a class name, use it directly
                    if (class_exists($typeValue)) {
                        $relation_class = $typeValue;
                        // Update relation_class so getPrimaryKey() uses the resolved class
                        $this->relation_class = $relation_class;
                    } else {
                        // Fallback to record_name if type doesn't resolve to a class
                        $relation_class = $this->relation_class;
                    }
                }
            }

            // Get primary key from the resolved class
            $primaryKey = $relation_class::$primary_key;

            $where = array(
                $primaryKey => $foreignKeyValue,
            );

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
        if (null == $this->primary_key) {
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
