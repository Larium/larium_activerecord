<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

use Larium\ActiveRecord\Record;

class Book extends Record
{
    public static $columns = array('id', 'title', 'author_id');

    public static $table = 'books';

    public static $BelongsTo = array(
        'author' => array(
            'record_name' => 'Author',
            'foreign_key' => 'author_id',
            'inversed_by' => 'books'
        )
    );
}
