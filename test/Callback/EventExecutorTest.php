<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord\Callback;

use Larium\ActiveRecord\AdapterFactory;
use Larium\ActiveRecord\Model\Author;
use Larium\ActiveRecord\Record;
use Larium\ActiveRecord\Config;
use PHPUnit\Framework\TestCase;

class EventExecutorTest extends TestCase
{
    protected $adapter;

    public function setUp()
    {
        $config = (new Config())->getDatabase();

        $this->adapter = AdapterFactory::create($config);

        Record::setAdapter($this->adapter);
    }

    public function testEventCallackTrigger()
    {
        $author = new Author();

        $author->lastname = 'Kollaros';

        $author->save();

        $this->assertEquals('pre_kollaros', $author->lastname);

        $author->save();

        $this->assertEquals('PRE_pre_kollaros', $author->lastname);
    }
}
