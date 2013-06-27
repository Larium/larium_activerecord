<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// Dependency
require_once 'larium_database/autoload.php';

require_once 'ClassMap.php';

$classes = array(
    'Larium\\ActiveRecord\\AdapterFactory' => 'Larium/ActiveRecord/AdapterFactory.php',
    'Larium\\ActiveRecord\\Attributes' => 'Larium/ActiveRecord/Attributes.php',
    'Larium\\ActiveRecord\\Relations\\RelationCollectionInterface' => 'Larium/ActiveRecord/Relations/RelationCollectionInterface.php',
    'Larium\\ActiveRecord\\Relations\\HasMany' => 'Larium/ActiveRecord/Relations/HasMany.php',
    'Larium\\ActiveRecord\\Relations\\Collection' => 'Larium/ActiveRecord/Relations/Collection.php',
    'Larium\\ActiveRecord\\Relations\\Relation' => 'Larium/ActiveRecord/Relations/Relation.php',
    'Larium\\ActiveRecord\\Relations\\ManyToMany' => 'Larium/ActiveRecord/Relations/ManyToMany.php',
    'Larium\\ActiveRecord\\Relations\\BelongsTo' => 'Larium/ActiveRecord/Relations/BelongsTo.php',
    'Larium\\ActiveRecord\\Relations\\Options' => 'Larium/ActiveRecord/Relations/Options.php',
    'Larium\\ActiveRecord\\Relations\\HasOne' => 'Larium/ActiveRecord/Relations/HasOne.php',
    'Larium\\ActiveRecord\\Datetime' => 'Larium/ActiveRecord/Datetime.php',
    'Larium\\ActiveRecord\\Collection' => 'Larium/ActiveRecord/Collection.php',
    'Larium\\ActiveRecord\\DataMapper' => 'Larium/ActiveRecord/DataMapper.php',
    'Larium\\ActiveRecord\\Mysql\\Query' => 'Larium/ActiveRecord/Mysql/Query.php',
    'Larium\\ActiveRecord\\Mysql\\Adapter' => 'Larium/ActiveRecord/Mysql/Adapter.php',
    'Larium\\ActiveRecord\\Null' => 'Larium/ActiveRecord/Null.php',
    'Larium\\ActiveRecord\\AdapterPool' => 'Larium/ActiveRecord/AdapterPool.php',
    'Larium\\ActiveRecord\\Callback\\Base' => 'Larium/ActiveRecord/Callback/Base.php',
    'Larium\\ActiveRecord\\DataRepository' => 'Larium/ActiveRecord/DataRepository.php',
    'Larium\\ActiveRecord\\Logger' => 'Larium/ActiveRecord/Logger.php',
    'Larium\\ActiveRecord\\Inflect' => 'Larium/ActiveRecord/Inflect.php',
    'Larium\\ActiveRecord\\LogIterator' => 'Larium/ActiveRecord/LogIterator.php',
    'Larium\\ActiveRecord\\CollectionInterface' => 'Larium/ActiveRecord/CollectionInterface.php',
    'Larium\\ActiveRecord\\Record' => 'Larium/ActiveRecord/Record.php',
);

ClassMap::load(__DIR__ . "/src/", $classes)->register();
