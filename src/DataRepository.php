<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord;

trait DataRepository
{
    public static function find($id = null)
    {
        if (null !== $id) {
            return static::getAdapter()
                ->createQuery(get_called_class())
                ->where(array('id' => $id));
        }
        return static::getAdapter()->createQuery(get_called_class());
    }

    public static function first()
    {
        return static::find()->limit(1)->offset(0)->fetch();
    }

    public static function __callStatic($name, $args)
    {
        $find_by = 'findBy';
        if (strpos($name, $find_by) === 0) {

            $field = substr($name, strlen($find_by));

            if (!empty($field)) {

                $field = self::underscore($field);
                $arg = array_shift($args);

                return static::find()->where(array($field=>$arg));
            }
        }
    }

    private static function underscore($camelCasedWord) {
        return strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $camelCasedWord));
    }
}
