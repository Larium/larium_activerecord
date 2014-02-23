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

        // The name of the property of the parent class.
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
     * @return Larium\ActiveRecord\CollectionInterface
     */
    public function all($reload = false)
    {
        if (true === $reload || null === $this->result_set) {

            if ($this->parent->isNewRecord()) {
                $collection = new \Larium\ActiveRecord\Collection(new \ArrayIterator(), $this->relation_class);
            } else {
                $collection = $this->find()->fetchAll();

                $attribute = $this->getRelationAttribute();

                foreach($collection as $item) {
                    $item->getRelation($attribute)->assign($this->parent);
                }
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

        $inversed_mutator = "set" . $this->getParentClass();
        $record->$inversed_mutator($this->parent);

        if (!$this->parent->isNewRecord()) {

            $ids = array_filter($this->all()->toArray($relation_class::$primary_key));

            if (!in_array($record->{$relation_class::$primary_key}, $ids)
                && !$this->parent->isNewRecord()
            ) {
                $record->{$this->getForeignKey()} = $this->getPrimaryKeyValue();
                if ($record->save()) {
                    $this->all()->offsetSet($offset, $record);
                }
            }
        } else {
            $this->all()->offsetSet($offset, $record);
        }
    }

    public function delete(Record $record, $offset = null)
    {

        $key = $offset ?: $this->getDeleteKey($record);

        switch ($this->options->dependent) {
            case 'delete':
            case 'destroy':
                $record->destroy();
                break;
            case 'nullify':
            default:
                $record->{$this->getForeignKey()} = null;
                $record->save();
                break;
        }

        $this->getIterator()->offsetUnset($key);
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

            // Find ids from relation class.
            $ids = $this->get_related_ids($this->result_set);

            $relation_class = $this->relation_class;

            // Checks if record exists in collection otherwise add it.
            if (!in_array($collection_or_record->{$relation_class::$primary_key}, $ids)) {
                $this->result_set[] = $collection_or_record;
            }
        }
    }

    /**
     * Sets a new collection to current relation.
     *
     * Diffs existing collection with new one and removes the different
     * records.
     *
     * @param  CollectionInterface $collection
     * @access public
     * @return void
     */
    public function setRelated(CollectionInterface $collection)
    {
        $relation_class = $this->relation_class;

        if (!$this->parent->isNewRecord()) {
            $old_ids = $this->get_related_ids($this->all());
            $new_ids = $this->get_related_ids($collection);

            $to_delete = array_diff($old_ids, $new_ids);
            $to_delete_objects = $this->result_set->filter(function($v) use ($to_delete, $relation_class){
                return in_array($v->{$relation_class::$primary_key}, $to_delete);
            });
            foreach ($to_delete_objects as $key=>$obj) {
                $this->delete($obj, $key);
            }


            // resolve new and to add items
            $to_add = array_diff($new_ids, $old_ids);
            $null_keys = array_filter($to_add, function($var){
                return $var === null || empty($var);
            });

            if (!empty($null_keys)) {
                foreach($null_keys as $key=>$null) {
                    $this->add($collection[$key]);
                }
            }
            $to_add_objects = $collection->filter(function($v) use ($to_add, $relation_class){
                return in_array($v->{$relation_class::$primary_key}, $to_add);
            });
            foreach ($to_add_objects as $key=>$obj) {
                $this->add($obj, $key);
            }

            // Update existing records
            $unchanged = array_intersect($old_ids, $new_ids);
            foreach ($unchanged as $id) {
                $old = $this->all()->detect($id, 'id');
                $new = $collection->detect($id, 'id');
                if ($old && $new) {
                    $old->setAttributes($new->getAttributes());
                    if ($old->isDirty()) $old->save();
                }
            }
        } else {
            foreach ($collection as $item) {
                $this->add($item);
            }
        }
    }

    public function saveDirty()
    {
        foreach ($this->all() as $item) {
            $item->{$this->getForeignKey()} = $this->getPrimaryKeyValue();
            $item->save();
        }
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

    private function get_related_ids($collection)
    {
        $relation_class = $this->relation_class;
        $pk = $relation_class::$primary_key;

        return $collection->toArray($pk);
    }
}
