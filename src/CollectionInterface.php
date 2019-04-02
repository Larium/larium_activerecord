<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord;

/**
 * Collection
 *
 * @package Larium\ActiveRecord
 * @author  Andreas Kollaros <php@andreaskollaros.com>
 * @license MIT {@link http://opensource.org/licenses/mit-license.php}
 */
interface CollectionInterface extends \ArrayAccess
{
    public function first();

    public function last();

    public function isEmpty();

    public function map(\Closure $block);

    public function each(\Closure $block);

    public function toArray($keyColumn = null, $valueColumn = null);

    public function select($search_value, $field_value);

    public function detect($search_value, $field_value);

    public function delete($search_value, $field_value);

    public function getRecord();
}
