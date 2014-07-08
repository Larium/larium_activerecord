<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

use Larium\ActiveRecord\Record;

class Author extends Record
{
    public static $columns = array('id', 'firstname', 'lastname');

    public static $table = 'authors';

    public static $HasMany = array(
        'books' => array(
            'record_name' => 'Book',
            'foreign_key' => 'author_id'
        )
    );

    public $before_create = array('uppercase');

    public $after_create = array('lowercase');

    public $before_save = array('prepend_name');

    public function uppercase()
    {
        $this->lastname = strtoupper($this->lastname);
    }

    public function lowercase()
    {
        $this->lastname = strtolower($this->lastname);

    }

    public function prepend_name()
    {
        $this->lastname = "PRE_" . $this->lastname;
    }

}
