<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord\Relations;

use Larium\ActiveRecord\Record;
use Larium\ActiveRecord\CollectionInterface;

interface RelationCollectionInterface extends \Countable
{
    #public function build(array $attributes = array());

    #public function create(array $attributes = array());

    public function setRelated(CollectionInterface $collection);

    #public function getIds();

    #public function setIds(array $ids);

    public function all($force_reload = false);

    public function find();

    public function delete(Record $record, $offset=null);

    public function add(Record $record, $offset = null);

    #public function clear();

    public function isEmpty();

    #public function contains();
}
