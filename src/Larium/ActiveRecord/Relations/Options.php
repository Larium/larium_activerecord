<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord\Relations;

/**
 * Options for relations
 * Available options are:
 * - `record_name` : The name of the class of related record.
 * - `foreign_key` : The name of the foreign key that connects the tables. 
 * - `inversed_by` : Required on BelongsTo relation. It is the relation name of 
 *                   HasMany.
 * - `where`       : An array with extra where clause that will append to query.
 * - `order_by`    : Order that will append to query.
 */
class Options
{
    private $options;

    private $required = array(
        'record_name', 'foreign_key'
    );

    private $relation;

    public function __construct($options, $relation = null)
    {
        $this->options = $options;

        $this->relation = $relation;
    }

    public function setRelationClass($relation)
    {
        $this->relation = $relation;
    }

    public function __get($name)
    {
        if (isset($this->options[$name])) {

            return $this->options[$name];
        } else {
            if (in_array($name, $this->required_options())) {

                throw new \InvalidArgumentException("Undefined value for `{$name}` option in relation options");
            } else {
                return null;
            }
        }
    }

    public function __isset($name)
    {
        return isset($this->options[$name]);
    }

    public function setQuery($query)
    {
        if ($this->where) {
            $query->andWhere($this->where);
        }

        if ($this->order_by) {
            list($field, $order) = $this->order_by;
            $query->orderBy($field, $order);
        }
    }

    public function toArray()
    {
        return $this->options;
    }

    public function getRelationAttribute($relation, $record, $reference)
    {
        if ($relation == 'HasMany' || $relation == 'HasOne') {
            $options = $record::$BelongsTo;
        } else if ($relation == 'BelongsTo') {
            $options = array_merge($record::$HasMany, $record::$HasOne);
        } else if ($relation == 'ManyToMany') {
            $options = $record::$ManyToMany;
        }
        
        $attribute= null;
        
        foreach ($options as $rel=>$option) {
            if ($option['record_name'] == $reference) {
                $attribute = $rel;
                break;
            }
        }

        if (null === $attribute) {
            throw new \Exception("{$reference} has not a `$relation` relation for {$record}");
        }

        return $attribute;
    }

    private function required_options()
    {

        switch ($this->relation) {
            case 'Larium\\ActiveRecord\\Relations\\BelongsTo':
                $options = array(
                    'inversed_by'
                );
                break;
            case 'Larium\\ActiveRecord\\Relations\\ManyToMany':
                $options = array(
                    'join_table',
                    'relation_foreign_key'
                );
                break;
            default:
                $options = array();
                break;
        }

        return array_merge($this->required, $options);
    }
}
