<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord;

class LogIterator extends \ArrayIterator
{
    public function logQuery(
        $query, 
        $class_name=null, 
        $parse_time = 0, 
        $action='Load'
    ) {
        $class_name = $class_name ?: 'Sql';
        
        $buffer = "$class_name $action ("
            . number_format($parse_time * 1000, '4')
            . "ms)  " . $query;

        $this->append($buffer);
    }

    public function yield()
    {
        return implode(PHP_EOL, $this->getArrayCopy());
    }
}
