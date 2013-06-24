<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord\Mysql;

use Larium\Database\AdapterInterface;
use Larium\Database\Mysql\Adapter as MysqlAdapter;
use Larium\ActiveRecord\Collection;

class Adapter extends MysqlAdapter
{
    /**
     * {@inheritdoc}
     */
    public function __construct(array $config) 
    {
        $config['fetch'] = MysqlAdapter::FETCH_ASSOC;
        parent::__construct($config);
    }
    
    /**
     * {@inheritdoc}
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
