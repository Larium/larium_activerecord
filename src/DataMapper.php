<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord;

use Larium\ActiveRecord\Callback\Base;
use Larium\Database\AdapterInterface;

trait DataMapper
{

    public static $columns=array();
    public static $table;
    public static $primary_key = "id";

    protected $new_record = true;
    
    protected $to_save = array();
    
    protected $dirty = array();
    
    private $attributes = array();

    /**
     * Attributes placed here will not assigned when we passed an array 
     * of attributes in Record class.
     *
     * <code>
     * class Test extends Larium\ActiveRecord\Record
     * {
     *      protected $protected_attributes = array('field');
     * }
     * 
     * $test = new Test(array('field'=>'value', 'field_a'=>'value_a');
     * // field attribute will not be assigned.
     * 
     * $test->field = 'value'; 
     * // field value now will be assigned.
     * </code>
     *
     * @var array An array with Record attributes names.
     */
    protected $protected_attributes = array();

    private $_default_protected_attributres = array(
        'id', 'created_at', 'updated_at'
    );

    protected static $adapter;
    
    public function __construct($attrs=array(), $new_record=true)
    {
        $this->new_record = $new_record;

        $this->set_attributes($attrs);
    }

    public static function initWith(array $attributes)
    {
        $record = new static(array(), false);
        $record->setAttributes($attributes, false);
        return $record;
    }

    public function isNewRecord()
    {
        return $this->new_record;
    }

    public function isDirty()
    {
        return !empty($this->to_save);
    }

    public function __get($name)
    {
        return array_key_exists($name, $this->attributes) 
            ? $this->attributes[$name]
            : null; 
    }

    public function getAttributes()
    {
        return array_merge($this->_get_public_attributes(), $this->attributes);
    }

    public function setAttributes($attrs, $protected=true)
    {
        if (true == $protected) {
            
            $protected_attributes = array_merge(
                $this->protected_attributes, 
                $this->_default_protected_attributres
            );

            $attrs = array_diff_key($attrs, array_flip($protected_attributes));
            
        }
            
        $this->set_attributes($attrs);
    }

    public function __isset($name)
    {
        return array_key_exists($name, $this->attributes);
    } 

    public function __set($name, $value)
    {
        if (array_key_exists($name, $this->attributes)) {
            $this->set_attribute($name, $value);
        } else {
            $this->$name = $value;
        }
    }

    private function set_attribute($attr, $value)
    {
        if ($this->attributes[$attr] != $value) {
            $this->to_save[$attr] = $value;
            $this->dirty[$attr] = $this->attributes[$attr];
            $this->attributes[$attr] = $value;
        }
    }

    private function set_attributes($attrs)
    {
        if ($this->new_record) { 
            $this->attributes = array_combine(
                static::$columns, 
                array_pad(array(), count(static::$columns), null)
            );
        }

        foreach ($attrs as $k=>$v) {

            if ((  $this->new_record 
                && $this->attributes[$k] !== null)
                || (array_key_exists($k, $this->attributes)
                && $this->attributes[$k] != $v)
            ) {
                $this->to_save[$k] = $v;
                $this->dirty[$k] = isset($this->attributes[$k]) 
                    ? $this->attributes[$k] 
                    : null;
            }
            if (in_array($k, static::$columns) ) {
                $this->attributes[$k] = $v;
            } else {
                $this->$k = $v; 
            }

        }
    }

    protected function assign_attributes()
    {
        $attr = array();
        foreach (static::$columns as $column) {

            if ($column == static::$primary_key) continue;

            if (array_key_exists($column, $this->to_save)) {
                $attr[$column] = $this->to_save[$column];
            }
        }

        if (in_array('created_at', static::$columns) && $this->new_record) {
            $attr['created_at'] = date('Y-m-d H:i:s',time());
        }

        if (in_array('updated_at', static::$columns)) {
            $attr['updated_at'] = date('Y-m-d H:i:s',time());
        }

        return $attr;
    }

    protected function get_id()
    {
        return $this->attributes[static::$primary_key];
    }

    protected function set_id($value)
    {
        $this->attributes[static::$primary_key] = $value;
    }

    public function reload()
    {
        $this->to_save = array();
        $this->dirty = array();
    }

    public function destroy()
    {
        return Base::runCallbacks('destroy', $this, function() {
            $pk = static::$primary_key;
            
            return static::getAdapter()->createQuery()
                ->delete(static::$table, array($pk=>$this->$pk));
        });
    }

    public function save()
    {
        return $this->create_or_update();
    }

    protected function create_or_update()
    {
        $result = $this->new_record 
            ? $this->create() 
            : $this->update();
            
        if ($result) {
            $this->reload();
        }
        
        return $result != false;
    }
    
    private function create()
    {
        return Base::runCallbacks('create', $this, function(){
            
            $attrs = $this->assign_attributes();

            static::find()->insert(static::$table, $attrs);
            $id = static::getAdapter()->getInsertId();
            if ($id) {
                $this->set_id($id);
                $this->new_record = false;
                return true;
            } else {
                return false;
            } 
        });
    }

    private function update()
    {
        return Base::runCallbacks('update', $this, function(){

            if (!$this->isDirty()) {
                return true;
            }
            $attrs = $this->assign_attributes();

            $pk = static::$primary_key;

            $update = static::getAdapter()->createQuery()
                ->update(static::$table, $attrs, array($pk=>$this->$pk));

            return $update;
        });
    }

    public function updateAttributes($attr)
    {
        $this->set_attributes($attr);
        return $this->save();
    }

    private function _get_public_attributes()
    {
        $attr = array();
        $ref = new \ReflectionObject($this);
        $props = $ref->getProperties(\ReflectionProperty::IS_PUBLIC);
        
        foreach ($props as $pro) {
            // skip static properties
            if ($pro->isStatic()) continue;

            false && $pro = new \ReflectionProperty();
            $attr[$pro->getName()] = $pro->getValue($this);
        }
        
        return $attr;
    }

    public static function setAdapter(AdapterInterface $adapter)
    {
        AdapterPool::add(get_called_class(), $adapter);
    }

    public static function getAdapter()
    {
        return AdapterPool::get(get_called_class());
    }
}
