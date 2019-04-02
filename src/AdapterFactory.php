<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord;

class AdapterFactory
{
    /**
     * Creates a new AdapterInterface based on given config values.
     *
     * @param array $config 
     * @static
     * @access public
     * @return void
     */
    public static function create(array $config)
    {
        if (!isset($config['adapter'])) {
            throw new \InvalidArgumentException('Indefined key `adapter` in configuration file.');
        }

        $class = '\\Larium\\ActiveRecord\\' . $config['adapter'] . '\\Adapter';
        
        return new $class($config);
    }
}
