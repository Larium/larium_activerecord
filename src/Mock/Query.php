<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord\Mock;

use Larium\Database\Mysql\Query as DatabaseMysqlQuery;
use Larium\ActiveRecord\Collection;
use Larium\ActiveRecord\CollectionInterface;

/**
 * Mock Query class for testing
 */
class Query extends DatabaseMysqlQuery
{
    protected $eager = array();

    public function __construct($object = null, Adapter $adapter = null)
    {
        parent::__construct($object, $adapter);

        if (null !== $object) {
            /** @var \Larium\ActiveRecord\Record $object */
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

    /**
     * @param null $hydration
     * @return \Iterator|Collection
     */
    public function fetchAll($hydration = null)
    {
        return $this->fetch_data("all", $hydration);
    }

    /**
     * @param string $mode
     * @param null $hydration
     * @return \Iterator|Collection|Record|null
     */
    protected function fetch_data($mode, $hydration = null)
    {
        $this->build_sql();

        $iterator = $this->adapter->execute($this, 'Load', $hydration);

        // Aggregates should not be hydrated into Record models.
        if ($this->object) {
            $collection = new Collection($iterator, $this->object);
        } else {
            $collection = $iterator;
        }

        if ('all' == $mode) {
            return $collection;
        }

        return $collection instanceof Collection
            ? $collection->first()
            : $collection->current();
    }

    public function fetch($hydration = null)
    {
        return $this->fetch_data("one", $hydration);
    }
}

