<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord;

class NullObject extends Record
{
    public static $columns=array(null);

    public function __get($name)
    {
        return new self();
    }

    public function __call($name, $value)
    {
        return null;
    }

    public function __toString()
    {
        return '';
    }

    public function isNull()
    {
        return true;
    }
}
