<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord;

use Larium\ActiveRecord\Relations\BelongsTo;
use Larium\ActiveRecord\Relations\HasOne;
use Larium\ActiveRecord\Callback\Base;
use Larium\Database\AdapterInterface;

abstract class Record 
{ 
    public static $columns=array();
    public static $table;
    public static $primary_key = "id";

    protected $new_record = true;
    
    protected $to_save = array();
    
    protected $dirty = array();
    
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
    
    private $attributes = array();

    private $default_protected_attributes = array(
        'id', 'created_at', 'updated_at'
    );

    protected static $adapter;

    /* -( Relationships ) -------------------------------------------------- */
    
    protected static $relations = array(
        'HasMany',
        'ManyToMany',
        'BelongsTo',
        'HasOne'
    );

    public static $HasMany    = array();
    public static $BelongsTo  = array();
    public static $HasOne     = array();
    public static $ManyToMany = array();
    
    /* -( Model ) ---------------------------------------------------------- */

    public function __construct($attrs=array(), $new_record=true)
    {
        $this->new_record = $new_record;

        $this->setAttributes($attrs);
    }

    public static function initWith(array $attributes)
    {
        $record = new static(array(), false);

        foreach ($attributes as $name=>$value) { 
            $record->set_attribute($name, $value, true);
        }

        return $record;
    }

    /* -( Attributes ) ----------------------------------------------------- */

    public function __isset($name)
    {
        return array_key_exists($name, $this->attributes) 
            || in_array($name, static::$columns);
    } 

    public function __get($name)
    {
        return array_key_exists($name, $this->attributes) 
            ? $this->attributes[$name]
            : null; 
    }

    public function getAttributes()
    {
        return array_merge($this->public_attributes(), $this->attributes);
    }

    private function public_attributes()
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

    public function setAttributes($attrs, $protected=true)
    {
        if (true == $protected) {
            
            $protected_attributes = array_merge(
                $this->protected_attributes, 
                $this->default_protected_attributes
            );

            $attrs = array_diff_key($attrs, array_flip($protected_attributes));
            
        }
            
        $this->set_attributes($attrs);
    }

    private function set_attributes($attrs)
    {
        if ($this->isNewRecord()) {
            $this->dummy_fill();
        }

        foreach ($attrs as $k=>$v) {
            $this->set_attribute($k, $v);
        }
    }

    public function __set($name, $value)
    {
        $this->set_attribute($name, $value);
    }

    private function set_attribute($attr, $value, $load=false)
    {

        if (in_array($attr, static::$columns)) {
            
            if (   array_key_exists($attr, $this->attributes) 
                && $this->attributes[$attr] != $value
                && false === $load
            ) { 
                $this->to_save[$attr] = $value;
                $this->dirty[$attr] = $this->attributes[$attr];
            }

            $this->attributes[$attr] = $value;

        } else {
            $this->$attr = $value;
        }
    }

    /**
     * Fill dummy values to attributes array according to column names.
     * 
     * @access protected
     * @return void
     */
    protected function dummy_fill()
    {
        $this->attributes = array_combine(
            static::$columns, 
            array_pad(array(), count(static::$columns), null)
        );       
    }


    public function attrWas($attr)
    {
        if ($this->attrChanged($attr)) {

            return $this->dirty[$attr];
        }

        return false;
    }

    public function attrChanged($attr)
    {
        return array_key_exists($attr, $this->dirty);
    }

    /* -( Persistence Methods ) -------------------------------------------- */
    
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

            if (empty($attrs)) {
                return false;
            }

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
            $attr['created_at'] = date('Y-m-d H:i:s', time());
        }

        if (in_array('updated_at', static::$columns)) {
            $attr['updated_at'] = date('Y-m-d H:i:s', time());
        }

        return $attr;
    }

    public function isNewRecord()
    {
        return $this->new_record;
    }

    public function isDirty()
    {
        return !empty($this->to_save);
    }

    /* -( Adapter Setup ) -------------------------------------------------- */
    
    public static function setAdapter(AdapterInterface $adapter)
    {
        AdapterPool::add(get_called_class(), $adapter);
    }

    public static function getAdapter()
    {
        return AdapterPool::get(get_called_class());
    }

    /* -( Finder ) --------------------------------------------------------- */
    
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

    /* -( Relationships ) -------------------------------------------------- */
    
    public static function getRelationship($record_or_collection, $attribute)
    {
        foreach (self::$relations as $relation) {
            if (isset(static::$$relation) && !empty(static::$$relation)) {
                if (   in_array($attribute, static::$$relation)
                    || array_key_exists($attribute, static::$$relation)
                ) {
                    $o = static::$$relation;
                    $options = array_key_exists($attribute, $o)
                        ? $o[$attribute] 
                        : array();

                    $r = "Larium\ActiveRecord\\Relations\\$relation";
                    return new $r($attribute, $record_or_collection, $options);
                }
            }
        }
    }

    public function getRelation($attribute)
    {
        if (!isset($this->$attribute)) {
            $this->$attribute = self::getRelationship($this, $attribute);
        }

        return $this->$attribute;
    }

    public function setRelationValues($attribute, $values)
    {
        $this->$attribute = $values;
    }

    public static function hasMany($name, $options=array())
    {
        static::$HasMany[$name] = $options;
    }

    public static function hasOne($name, $options=array())
    {
        static::$HasOne[$name] = $options;
    }

    public static function belongsTo($name, $options=array())
    {
        static::$BelongsTo[$name] = $options;
    }

    public static function manyToMany($name, $options=array())
    {
        static::$ManyToMany[$name] = $options;
    }

    public function __call($name, $arguments)
    {
        if (0 === strpos($name, 'get')) {
            return $this->call_accessor($name, $arguments);
        } else if (0 === strpos($name, 'set')) {
            return $this->call_mutator($name, $arguments);
        } else {
            $class = get_class($this);
            throw new \BadMethodCallException("Undefined method {$class}::{$name}()");
        }
    }

    protected function call_accessor($name, $arguments)
    {
        $attribute = $this->underscore(str_replace('get','',$name));
        $relation = $this->getRelation($attribute);
        if (   $relation instanceof BelongsTo
            || $relation instanceof HasOne
        ) {
            return $relation->fetch();
        } else {
            return $relation;
        }
    }

    protected function call_mutator($name, $arguments)
    {
        $attribute = $this->underscore(str_replace('set','',$name));
        $arg = current($arguments);
        $this->getRelation($attribute)->setRelated($arg);
    }

    private static function underscore($camelCasedWord) {
        return strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $camelCasedWord));
    }

}
