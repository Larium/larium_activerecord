<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord\Callback;

use Larium\ActiveRecord\AdapterFactory;
use Larium\ActiveRecord\Record;

class EventExecutorTest extends \PHPUnit_Framework_TestCase
{
    protected $adapter;

    public function setUp()
    {
        $config = (new \Config())->getDatabase();

        $this->adapter = AdapterFactory::create($config);

        Record::setAdapter($this->adapter);
    }

    public function testEventCallackTrigger()
    {
        $author = new \Author();

        $author->lastname = 'Kollaros';

        $author->save();

        $this->assertEquals('kollaros', $author->lastname);
    }
}
