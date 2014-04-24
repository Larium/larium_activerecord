<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord\Mysql;

use Larium\Database\Mysql\Query as MysqlQuery;
use Larium\ActiveRecord\Collection;
use Larium\ActiveRecord\CollectionInterface;

class Query extends MysqlQuery
{
    protected $eager = array();

    public function __construct($object=null, $adapter=null)
    {
        parent::__construct($object, $adapter);

        if (null !== $object) {
            $this->from($object::$table);
        }
    }

    public function eager($relations)
    {
        if (is_array($relations)) {
            $this->eager = $relations;
        } else {
            $this->eager = array_map('trim', explode(',', $relations));
        }

        return $this;
    }

    protected function fetch_data($mode, $hydration = null)
    {
        $this->build_sql();

        $iterator = $this->adapter->execute($this, 'Load', $hydration);

        if ($this->object) {
            $collection = new Collection($iterator, $this->object);
            $collection = $this->eager_load($collection);
        } else {
            $collection = $iterator;
        }


        if ('all' == $mode) {

            return $collection;
        } elseif ('one' == $mode) {

            return $collection instanceof Collection ? $collection->first() : $collection->current();
        }
    }

    protected function eager_load($collection)
    {
        // eager loading
        if ( !$collection->isEmpty() && !empty($this->eager) ) {

            // include extra queries for eager loading relations
            foreach( $this->eager as $k=>$include ) {
                if ( !is_numeric($k) ) {
                    // chain association detected so call _includes method
                    // including $k
                    $this->_includes(array($k => $include), $collection, $this->object);
                } else {
                    $this->_includes($include, $collection, $this->object);
                }
            }
        }

        return $collection;
    }

    private function _includes($include, CollectionInterface $collection, $object)
    {
        if ( is_array($include) && !is_numeric(key($include)) ) {
            // we have chain associations to include
            foreach($include as $parent=>$v){
                // include the parent association first.
                // return the included collection as $c. $collection already
                // merged association records.

                $c = $this->_includes($parent, $collection, $collection->getRecord());
                if (!$c->isEmpty()) {
                    // now if we have multiple includes, include them with parent
                    if (is_array($v)) {
                        $this->_includes($v, $c, $c->getRecord());
                    // if we have not multiply includes just include it with parent
                    // classify($v) to $c collection
                    } else {
                        $this->_includes($v, $c, $c->getRecord());
                    }
                }
            }
        } elseif ( is_array($include) && is_numeric(key($include)) ) {
            // just regular includes. not chains.
            foreach ($include as $i) {
                $this->_includes($i, $collection, $object);
            }
            // here we make the merge of the associations to main $collection
            // dependent association type
        } else {
            if ( $relation = $object::getRelationship($collection, $include)
            ) {
                return $relation->eagerLoad();
            }
        }
    }

}
