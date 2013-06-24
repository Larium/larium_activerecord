<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord;

class Attributes implements \Iterator, \ArrayAccess, \Serializable, \Countable
{

    private $_primary_key;
    
    private $_columns;

    private $_composers;

    private $storage;

    private $_class_name;

    private $dirty_values = array();

    public function __construct($columns, $class_name, $options=array())
    {
        $attributes = array();
        if (isset($options['new_record']) && $options['new_record']) {
            $attributes = array_combine($columns, array_pad(array(), count($columns), null));
        }

        $this->_primary_key = $class_name::$primary_key;
        $this->_composers = $class_name::$composed_of;
        $this->_class_name = $class_name;

        $this->storage = $attributes;
        
        if (isset($options['new_record']) && $options['new_record']) {
            $this->dirty_values = $attributes;
        }

        $this->_columns = $columns;
    }

    public function assign($new_attributes, $options=array())
    {
        foreach ($new_attributes as $k=>$v) {
            isset($options['new_record']) && $options['new_record']
            ? $this->set($k, $v)
            : $this->storage[$k] = $v;
        }
    }

    public function reload() {
        $this->dirty_values = array();
    }

    /**
     * TODO: Reserve function for getting attribute values as objects ex.
     * \DateTime object for date values
     * or type cast special values like boolean from integer to boolean
     * etc
     */
    public function get($key)
    {
        if (array_key_exists($key, $this->storage))
            return $this[$key];
        return false;
    }


    public function set($key, $value)
    {
        if ( null == $key) return;
        
        if (   isset( $this->dirty_values[$key]) 
            && $this->dirty_values[$key] == $value // Maybe === operator 
        ) {
            unset($this->dirty_values[$key]); 

        } elseif (array_key_exists($key, $this->storage) 
            && $this->storage[$key] !== $value 
        ) {
            $this->dirty_values[$key] = $this->storage[$key];
            $this->storage[$key] = $value;
        }
    }


    public function isDirty()
    {
        return !empty($this->dirty_values);
    }

    public function attributesValues(
        $include_primary_key=true, 
        $include_readonly_attributes=true, 
        $attribute_names=null
    ) {

        if (empty($this->dirty_values)) {
            return array();
        }

        if ( null === $attribute_names ) {
            $attribute_names = array_keys($this->storage);
        }

        $attrs = array();
        
        foreach ($attribute_names as $name) {
            
            $column = $this->columnForAttribute($name);

            if (   array_key_exists($column, $this->dirty_values) && $column 
                && ($include_primary_key || !($column == $this->_primary_key))
            ) {
                $value = $this[$name];
                $attrs[$name] = $value;
            }
        }

        return $attrs;
    }

    public function columnForAttribute($name)
    {
        return in_array($name, $this->_columns) ? $name : false;
    }

    public function keys()
    {
        return array_keys($this->storage);
    }

    public function toArray()
    {
        return $this->storage;
    }

    // SPL - Countable functions
    // ----------------------------------------------

    /**
     * Get a count of all the records in the result set
     */
    public function count()
    {
        return count($this->storage);
    }

    // ----------------------------------------------
    // SPL - Iterator functions
    // ----------------------------------------------
    public function current()
    {
        return current($this->storage);
    }

    public function key()
    {
        return key($this->storage);
    }

    public function next()
    {
        next($this->storage);
    }

    public function rewind()
    {
        reset($this->storage);
    }

    public function valid()
    {
        return (current($this->storage) !== FALSE);
    }

    // ----------------------------------------------
    // SPL - ArrayAccess functions
    // ----------------------------------------------
    public function offsetExists($key)
    {
        return isset($this->storage[$key]);
    }

    public function offsetGet($key)
    {
        return $this->storage[$key];
    }

    public function offsetSet($key, $value)
    {
        if ($key === null) {
            $this->storage[] = $value;
        } else {
            $this->storage[$key] = $value;
        }
    }

    public function offsetUnset($key)
    {
        if (is_int($key)) {
            array_splice($this->storage, $key, 1);
        } else {
            unset($this->storage[$key]);
        }
    }

    // ----------------------------------------------
    // SPL - Serializable functions
    // ----------------------------------------------
    public function serialize()
    {
        return serialize($this->storage);
    }

    public function unserialize($serialized)
    {
        $this->storage = unserialize($serialized);
    }
    // ----------------------------------------------
}
