<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord\Callback;

class Base
{
    public static $CALLBACKS = array(
        'before_validation', 'after_validation',
        'before_save', 'after_save',
        'before_create', 'after_create',
        'before_update', 'after_update',
        'before_destroy', 'after_destroy'
    );

    protected static function reflection_properties($object)
    {
        $properties = array();
        //$ref = new \ReflectionClass(get_class($object));
        $def_prop = get_object_vars($object);//$ref->getProperties();

        foreach (self::$CALLBACKS as $callback) {

            if (array_key_exists($callback, $def_prop)) {

                if (!isset($properties[$callback])) {
                    $properties[$callback] = array();
                }

                $properties[$callback] = array_merge(
                    $properties[$callback],
                    $def_prop[$callback]
                );


                if (   isset($object->$callback)
                    && $object->$callback != $def_prop[$callback]
                ) {
                    $properties[$callback] = array_merge(
                        $properties[$callback],
                        $object->$callback
                    );
                }

                $properties[$callback] = array_unique($properties[$callback]);

            } else {

                if ( isset($object->$callback) && !empty($object->$callback)) {

                    if (!isset($properties[$callback])) {
                        $properties[$callback] = array();
                    }

                    $properties[$callback] = array_merge(
                        $properties[$callback],
                        $object->$callback
                    );
                }
            }
        }

        return $properties;
    }

    protected static function perform_callback_for($kind, $chain, $object)
    {
        $obs = $norm = true;
        $callback_methods = array("{$chain}_{$kind}");
        if ($kind == 'update' || $kind == 'create') {
            $callback_methods[] = "{$chain}_save";
        }

        foreach ($callback_methods as $callback) {
            if (!in_array($callback, self::$CALLBACKS)) continue;

            //Call record callback method
            if ( property_exists($object, $callback)) {
                if (!is_array($object->$callback)) {
                    throw new \InvalidArgumentException(sprintf("%s::%s must be an array",get_class($object), $callback));
                }
                foreach ($object->$callback as $method) {
                    $norm = $object->$method();
                    if ( false === $norm ) break;
                }
            }

            //Call observer callbacks if exist
            if ($object instanceof \SplSubject) {
                $obs = $object->notifySubject($callback);
            }

            if ($obs !==false && $norm !== false) {

                continue;
            } else {

                return false;
            }
        }

        return true;
    }

    public static function runCallbacks($kind, $object, $block=null)
    {
        $res = self::perform_callback_for($kind, 'before', $object);

        if ($res !== false ) {
            if (is_callable($block)) {
                $res = $block();
            }
        } else {
            $res = false;
        }

        return $res !== false ?
            self::perform_callback_for($kind, 'after', $object)
            : false;
    }
}
