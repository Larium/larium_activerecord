<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord;

use Larium\ActiveRecord\Mysql\Adapter;
use Larium\Database\AdapterInterface;

class AdapterPool
{
    protected static $pool = array();

    /**
     * Adds a new Adapter to pool.
     *
     * @param string $record
     * @param AdapterInterface $adapter
     * @static
     * @access public
     * @return void
     */
    public static function add(
        AdapterInterface $adapter,
        $record = 'Larium\\ActiveRecord\\Record'
    ) {
        self::$pool[$record] = $adapter;
    }

    /**
     * Gets addapter for given class name
     *
     * @param string $record
     * @static
     * @access public
     * @return Adapter
     */
    public static function get($record='Larium\\ActiveRecord\\Record')
    {
        return isset(self::$pool[$record])
            ? self::$pool[$record]
            : self::$pool['Larium\\ActiveRecord\\Record'];
    }

    public static function getPool()
    {
        return self::$pool;
    }
}
