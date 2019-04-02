<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord\Mysql;

use Larium\Database\Mysql\Adapter as DatabaseMysqlAdapter;
use Larium\ActiveRecord\Collection;

class Adapter extends DatabaseMysqlAdapter
{
    /**
     * {@inheritdoc}
     */
    public function __construct(array $config) 
    {
        $config['fetch'] = DatabaseMysqlAdapter::FETCH_ASSOC;
        parent::__construct($config);
    }
    
    /**
     * {@inheritdoc
     *
     * @return Query
     */
    public function createQuery($object = null)
    {
        return new Query($object, $this);
    }

    public function createCollection($data=array(), $record=null)
    {
        return new Collection(new \ArrayIterator($data), $record);
    }
}
