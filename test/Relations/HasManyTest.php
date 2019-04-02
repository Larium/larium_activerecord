<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord\Relations;

use Larium\ActiveRecord\AdapterFactory;
use Larium\ActiveRecord\Model\Author;
use Larium\ActiveRecord\Record;
use Larium\ActiveRecord\Collection;
use Larium\ActiveRecord\Config;
use PHPUnit\Framework\TestCase;

class HasManyTest extends TestCase
{
    public function setUp()
    {
        $config = (new Config())->getDatabase();

        $adapter = AdapterFactory::create($config);

        Record::setAdapter($adapter);
    }

    public function testHasManyRelationCalls()
    {
        $a = Author::find(1)->fetch();

        $books = $a->books;

        $this->assertInstanceOf(
            'Larium\ActiveRecord\Relations\HasMany',
            $books
        );

        $books = $a->getBooks();

        $this->assertInstanceOf(
            'Larium\ActiveRecord\Collection',
            $books
        );

        //$c = new Collection(new \ArrayIterator(array(new \Book())));
        //$a->setBooks($c);
    }
}
