<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord\Relations;

use Larium\ActiveRecord\Record;
use Larium\ActiveRecord\CollectionInterface;

abstract class Relation
{
    /**
     * The foreign key field name.
     *
     * @var string $foreign_key
     */
    protected $foreign_key;

    protected $primary_key;

    protected $options = array();

    protected $attribute;

    /**
     * The class name of record that the $parent is related.
     *
     * @var Larium\ActiveRecord\Record
     */
    protected $relation_class;

    /**
     * @var Larium\ActiveRecord\CollectionInterface
     */
    protected $result_set;

    /**
     * A reference to record that called this relation.
     * It can be an Record or a Collection instance
     *
     * @var Record|CollectionInterface
     */
    protected $parent;

    /**
     * @var Query
     */
    protected $query;

    /**
     *
     * @param string $attribute
     * @param Record|Collection $parent
     * @param array $options
     * @access public
     *
     * @return Larium\ActiveRecord\Relation
     */
    public function __construct(
        $attribute,
        $parent,
        array $options = array()
    ) {
        $this->options = new Options($options, get_called_class());

        $this->attribute = $attribute;
        $this->parent = $parent;

        $this->relation_class = $this->options->record_name;
        $this->foreign_key = $this->options->foreign_key;
        $this->primary_key = $this->options->primary_key;
    }

    abstract protected function assign($collection_or_record);

    abstract public function eagerLoad();

    /**
     * Return the class name of the reference that called this relation
     *
     * @access public
     * @return string The class name of parent class
     */
    public function getParentClass()
    {
        if ($this->parent instanceof Record) {
            $class = get_class($this->parent);
        } elseif ($this->parent instanceof CollectionInterface) {
            $class = $this->parent->getRecord();
        }

        return $class;
    }

    public function getRelationClass()
    {
        return $this->relation_class;
    }

    /**
     * Gets the name of attribute that has been assigned from the inversed
     * relation.
     */
    protected function getRelationAttribute()
    {
        $class = str_replace(__NAMESPACE__."\\", '', get_called_class());
        return $this->options->getRelationAttribute(
            $class,
            $this->options->record_name,
            $this->getParentClass()
        );
    }

    public function getForeignKey()
    {
        return $this->foreign_key;
    }

    public function getOptions()
    {
        return $this->options;
    }
}
