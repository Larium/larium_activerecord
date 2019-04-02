<?php

namespace Larium\ActiveRecord;

use Larium\ActiveRecord\Mysql\Adapter;
use Larium\ActiveRecord\Mysql\Query;
use Larium\ActiveRecord\Relations\BelongsTo;
use Larium\ActiveRecord\Relations\HasOne;
use Larium\ActiveRecord\Callback\Base;
use Larium\Database\AdapterInterface;

/**
 * Record
 *
 * Accessing Relations:
 *  - $record->relation_name # Relations\Relation instance
 *  - $record->getRelationName() # Record or Collection instance
 *
 * @abstract
 * @author  Andreas Kollaros <andreaskollaros@ymail.com>
 * @license MIT {@link http://opensource.org/licenses/mit-license.php}
 *
 * @property mixed $id
 */
abstract class Record implements \ArrayAccess
{

    /**
     * Name of columns for table.
     *
     * @var array
     */
    public static $columns = array();

    /**
     * The name of the table in database/
     *
     * @var string
     */
    public static $table;

    /**
     * The primary key for this table.
     *
     * @var string
     */
    public static $primary_key = "id";

    protected $relation_attributes = array();

    /**
     * Indicated if this record is new or not.
     *
     * @var boolean
     * @access protected
     */
    protected $new_record = true;

    protected $to_save = array();

    /**
     * Attributes that has changed. The key is the name of the column and value
     * is the old attribute value.
     *
     * Old value for new records is is always null.
     * Old value for fetched records is always the value of persisted data.
     *
     * @var array
     * @access protected
     */
    protected $dirty = array();

    protected $dirty_relations = array();

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

    private $mark_for_destroy;

    private $frozen = false;

    private $event_executor;

    /**
     * @var AdapterInterface
     */
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

    public function __construct(array $attrs = array(), $new_record = true)
    {
        $this->new_record = $new_record;

        $this->setAttributes($attrs);
    }

    public static function initWith(array $attributes, $force_fetch=false)
    {
        if (array_key_exists(static::$primary_key, $attributes)
            && $attributes[static::$primary_key] != null
            && true === $force_fetch
        ) {
            $record = static::find($attributes[static::$primary_key])->fetch();
            foreach ($attributes as $name=>$value) {
                $record->set_attribute($name, $value);
            }
        } else {
            $record = new static(array(), false);
            foreach ($attributes as $name=>$value) {
                $record->set_attribute($name, $value, true);
            }
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
        if (array_key_exists($name, $this->attributes)) {

            return method_exists($this, $name)
                ? $this->$name()
                : $this->attributes[$name];
        } elseif ($relation = $this->getRelation($name)) {

            return $relation;
        }

        throw new \InvalidArgumentException(sprintf('%s::%s property does not exists', get_class($this), $name));
    }

    protected function getAttribute($attr)
    {
        return array_key_exists($attr, $this->attributes)
            ? $this->attributes[$attr]
            : null;
    }

    private $__de_hydrated = false;

    /**
     * @return array
     */
    public function getAttributes()
    {
        if ($this->__de_hydrated == true) {
            return [];
        }

        $attrs = method_exists($this, 'toArray')
            ? $this->toArray()
            : array_merge($this->public_attributes(), $this->attributes);

        $return = array();
        $this->__de_hydrated = true;
        foreach ($attrs as $name =>$value) {
            if ($value instanceof Relations\Collection) {
                $c = $value->all()->toArray();
                if ($c) {
                    $return[$name] = $c;
                }
            } elseif( $value instanceof Relations\Relation) {
                $v = $value->fetch()->getAttributes();
                if ($v) {
                    $return[$name] = $v;
                }
            } else {
                $return[$name] = $value;
            }
        }

        $this->__de_hydrated = false;

        return $return;
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

    /**
     * @param array $attrs
     * @param bool $protected
     * @param bool $load
     * @return void
     */
    public function setAttributes(array $attrs, $protected = true, $load = false)
    {
        if ($this->frozen) {
            return;
        }

        if (true == $protected) {

            $protected_attributes = array_merge(
                $this->protected_attributes,
                $this->default_protected_attributes
            );

            $attrs = array_diff_key(
                $attrs,
                array_flip($protected_attributes)
            );
        }

        if ($this->isNewRecord()) {
            $this->dummy_fill();
        }

        foreach ($attrs as $k=>$v) {
            $this->set_attribute($k, $v, $load);
        }
    }

    public function __set($name, $value)
    {
        $this->set_attribute($name, $value);
    }

    private function set_dirty($attr, $value, $old_value)
    {
        $this->check_attribute_exists($attr);

        if ($value !== $old_value) {
            if (!$this->attrChanged($attr)) {
                $this->to_save[$attr] = $value;
                $this->dirty[$attr] = $old_value;
            } elseif ($value == $this->dirty[$attr]) {
                unset($this->dirty[$attr]);
                unset($this->to_save[$attr]);
            } else {
                $this->to_save[$attr] = $value;
            }

            $this->attributes[$attr] = $value;
        }
    }

    private function check_attribute_exists($attr)
    {
        if (!array_key_exists($attr, $this->attributes)) {
            throw new \InvalidArgumentException(sprintf("Property '%s' does not exists in class %s", $attr, get_class($this)));
        }
    }

    private function set_attribute($attr, $value, $load=false)
    {
        if ($this->frozen) return false;

        // Is $attr table field or relation?
        if (in_array($attr, static::$columns)) {

            if (false === $load) {

                $old = $this->attributes[$attr];

                // Checks for custom setter method.
                if (method_exists($this, $attr)) {
                    $this->$attr($value);
                    return $value = $this->attributes[$attr];
                }

                return $this->set_dirty($attr, $value, $old);
            }

            $this->attributes[$attr] = $value;

        } elseif (in_array($attr, $this->getRelationAttributes())) {

            if ($value instanceof Record || $value instanceof Relations\Relation) {

                $this->$attr = $value;
            } else {
                $this->hydrate_relation($attr, $value);
            }

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

    private function reset()
    {
        $this->to_save = array();
        $this->dirty = array();
    }

    public function reload()
    {
        //$this->attributes = array_merge($this->attributes, $this->dirty);
        $fresh_object = static::find($this->id)->fetch();

        $this->setAttributes($fresh_object->getAttributes(), false, true);

        $this->reset();
    }

    public function destroy()
    {
        if ($this->mark_for_destroy == true) {
            return true;
        }

        return Base::runCallbacks('destroy', $this, function() {
            $pk = static::$primary_key;

            $return = static::getAdapter()->createQuery()
                ->delete(static::$table, array($pk=>$this->$pk));

            $this->mark_for_destroy = true;

            $this->destroy_related_records();

            $this->frozen = true;

            return $return;
        });
    }

    public function isDestroyed()
    {
        return $this->frozen;
    }

    /**
     * @return bool
     */
    public function save()
    {
        return $this->create_or_update();
    }

    /**
     * @return bool
     */
    protected function create_or_update()
    {
        $result = $this->new_record
            ? $this->create()
            : $this->update();

        if ($result) {
            $this->reset();
            $this->update_dirty_relations();
        }

        return $result != false;
    }

    private function create()
    {
        return $this->getEventExecutor()->execute('create', function(){

            $attrs = $this->assign_attributes();

            if (empty($attrs)) {
                return false;
            }

            static::find()->insert(static::$table, $attrs);
            $id = static::getAdapter()->getInsertId();
            if ($id) {
                $this->set_id($id);
                $this->reset();
                $this->save_related_records();
                $this->new_record = false;
                return true;
            } else {
                return false;
            }
        });
    }

    private function update()
    {
        return $this->getEventExecutor()->execute('save', function(){

            if (!$this->isDirty() || empty($this->to_save)) {
                return true;
            }
            $attrs = $this->assign_attributes();

            $pk = static::$primary_key;

            $update = static::getAdapter()->createQuery()
                ->update(static::$table, $attrs, array($pk=>$this->$pk));

            $this->reset();

            return $update;
        });
    }

    public function updateAttributes($attr)
    {
        $this->setAttributes($attr);

        return $this->save();
    }

    private function update_dirty_relations()
    {
        $relation_attrs = $this->getRelationAttributes();
        foreach ($relation_attrs as $attr) {
            if ($rel = $this->$attr) {
                if ($rel instanceof Relations\RelationCollectionInterface) {
                    foreach($rel as $item)  {
                        if ($item->isDirty()) {
                            $item->save();
                        }
                    }
                } elseif ($rel instanceof Record) {
                    if ($rel->fetch() && $rel->fetch()->isDirty()){
                        $rel->fetch()->save();
                    }
                }
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

        if (   in_array('created_at', static::$columns)
            && $this->new_record
            && !isset($this->attributes['created_at'])
        ) {
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
        return !empty($this->to_save) || !empty($this->dirty);
    }

    /* -( EventExecutor ) -------------------------------------------------- */

    public function getEventExecutor()
    {
        if (null === $this->event_executor) {
            $this->event_executor = new Callback\EventExecutor($this);
        }

        return $this->event_executor;
    }

    /* -( Adapter Setup ) -------------------------------------------------- */

    public static function setAdapter(AdapterInterface $adapter)
    {
        AdapterPool::add(get_called_class(), $adapter);
    }

    /**
     * @return Adapter
     */
    public static function getAdapter()
    {
        return AdapterPool::get(get_called_class());
    }

    public static function addAdapter(AdapterInterface $adapter)
    {
        AdapterPool::add(get_called_class(), $adapter);
    }

    public static function useAdapter($name)
    {
        $adapter = null;
        foreach (AdapterPool::getPool() as $record=>$a) {
            if ($name == $record) {
                $adapter = $a;
                break;
            }
        }

        if (null !== $adapter) {
            static::setAdapter($adapter);
        }
    }

    public function getConnection()
    {
        return static::$adapter->getConnection();
    }

    /* -( Finder ) --------------------------------------------------------- */

    /**
     * @param string null $id
     * @return Query
     */
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

                $field = Inflect::underscore($field);
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

    public function getRelationAttributes()
    {
        if (empty($this->relation_attributes)) {
            $relation_attributes = array();

            foreach (static::$relations as $relation) {
                 if (isset(static::$$relation) && !empty(static::$$relation)) {

                     $attrs = array_keys(static::$$relation);

                     $relation_attributes =  array_merge($relation_attributes, $attrs);
                }
            }

            $this->relation_attributes = $relation_attributes;
        }

        return $this->relation_attributes;
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

    protected function hydrate_relation($attr, $value)
    {
        $rel = self::getRelationship($this, $attr);

        $hydrate_class = $rel->getOptions()->record_name;

        if ($rel instanceof Relations\RelationCollectionInterface) {
            if (is_array($value)) {
                $iterator = new \ArrayIterator();
                foreach ($value as $item) {
                    if (   !isset($item[static::$primary_key])
                        || empty($item[static::$primary_key])
                    ) {

                        $iterator->append(new $hydrate_class($item));
                    } else {
                        $iterator->append($hydrate_class::initWith($item, true));
                    }
                }

                //$rel->setRelated(new Collection($iterator));
                $method = 'set' . Inflect::camelize($attr);
                $this->$method(new Collection($iterator));
            }
        } else {
            $this->$attr = $hydrate_class::initWith($value, true);
        }

    }

    private function save_related_records()
    {
        foreach($this->dirty_relations as $attr => $relation) {

            if ($this->$attr instanceof Relations\Relation){
                $this->$attr->saveDirty();
            }
        }
    }

    private function destroy_related_records()
    {
        $relations = $this->getRelationAttributes();
        foreach ($relations as $attr) {

            $rel = $this->getRelation($attr);

            if ($rel instanceof Relations\RelationCollectionInterface) {

                $collection = $rel->all();

                $collection->rewind();

                while ($collection->valid()) {
                    $rel->delete($collection->current(),$collection->key());
                }
            } else {
                $rel->destroy();
            }
        }

    }

    /* -( Magic Methods ) -------------------------------------------------- */

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
        $attribute = Inflect::underscore(str_replace('get','',$name));

        if (array_key_exists($attribute, $this->attributes)) {

            return $this->$attribute;
        } elseif ($relation = $this->getRelation($attribute)) {
            if (   $relation instanceof BelongsTo
                || $relation instanceof HasOne
            ) {
                return $relation->fetch();
            } else {
                return $relation->all();
            }
        }

        throw new \InvalidArgumentException(sprintf('Undefined method %s::%s()', get_class($this), $name));
    }

    protected function call_mutator($name, $arguments)
    {
        $attribute = Inflect::underscore(str_replace('set', null, $name));

        $value = current($arguments);

        if (in_array($attribute, $this->getRelationAttributes())) {
            $relation = $this->getRelation($attribute);
            $return = $relation->setRelated($value);

            $this->dirty[$attribute] = get_class($relation);
            $this->dirty_relations[$attribute] = get_class($relation);

            return $return;
        } elseif (in_array($attribute, static::$columns)) {
            $this->set_attribute($attribute, $value);
        } else {
            throw new \InvalidArgumentException(sprintf("Invalid method %s::%s", get_class($this), $name));
        }
    }


    /* -( Array Access ) --------------------------------------------------- */

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->attributes);
    }

    public function offsetGet($offset)
    {
        return $this->getAttribute($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->set_dirty($offset, $value, $this->$offset);
    }

    public function offsetUnset($offset)
    {
        $this->set_dirty($offset, null, $this->$offset);
    }

}
