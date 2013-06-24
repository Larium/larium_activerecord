<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord;

use Larium\Database\AdapterInterface;

class AdapterPool
{
    protected static $pool = array();

    public static function add($record = 'Larium\\ActiveRecord\\Record', AdapterInterface $adapter) {
        self::$pool[$record] = $adapter;
    }

    public static function get($record='Larium\\ActiveRecord\\Record')
    {
        return isset(self::$pool[$record]) 
            ? self::$pool[$record]
            : self::$pool['Larium\\ActiveRecord\\Record'];
    }
}
