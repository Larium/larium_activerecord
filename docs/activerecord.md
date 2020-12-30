### Define the class
```php
<?php

use Larium\ActiveRecord\Record;

class Post extends Record
{

}
```

### Define attributes
```php
public static $columns = array('id', 'title', 'text', 'user_id', 'created_at')
```

### Define table
```php
public static $table = 'posts';
```

It is the plural name of the model in lower case.

if model name is `MyPost` then table name must be `my_posts` with underscore.

### Define primary key
```php
public static $primary_key = 'id';
```

### Define associations
```php
public static $BelongsTo = array(
	'user' => array(
    	'record_name' => 'User',
        'foreign_key' => 'user_id',
        'inversed_by' => 'posts'
    )
);
```

### Code examples
```php
<?php
// Post.php

use Larium\ActiveRecord\Record;

class Post extends Record
{
    public static $columns = array('id', 'title', 'text', 'user_id', 'created_at');
    public static $table = 'posts';
    public static $primary_key = 'id';

    public static $BelongsTo = array(
        'user' => array(
            'record_name' => 'User',
            'foreign_key' => 'user_id',
            'inversed_by' => 'posts'
        )
    );

    public static $ManyToMany = array(
    	'categories' => array(
        	'record_name' => 'Category',
 			'foreign_key' => 'post_id',
 			'relation_foreign_key' => 'category_id',
 			'join_table' => 'categories_posts'
        )
    );

    public static $HasMany = array(
    	'comments' => array(
        	'record_name' => 'Comment',
            'foreign_key' => 'post_id',
        )
    );
}
```
```php
<?php
//Comment.php

use Larium\ActiveRecord\Record;

class Comment extends Record
{
	public static $columns = array('id', 'content', 'post_id', 'created_at');
    public static $table = 'comments';
    // If primary_key is ommited then default primary_key name is 'id';

    public static $BelongsTo = array(
        'post' => array(
            'record_name' => 'Post',
            'foreign_key' => 'post_id',
            'inversed_by' => 'comments'
        )
    );
}
```
#### Find posts

```php
<?php

Post::find(); // returns Larium\Database\Mysql\Query object. No query to database yet.

Post::find()->fetch(); // returns the first Post object from a Collection and executes query to database.
// SELECT * FROM `posts`;

Post::find()->fetchAll(); // returns Collection object and executes query to database.
// SELECT * FROM `posts`;

Post::find(1)->fetch();
// SELECT * FROM `posts` WHERE id = '1';

Post::find()
    ->where(array('id'=>1))
    ->fetch();
// SELECT * FROM `posts` WHERE id = '1';

Post::findById(array(1, 2, 3))->fetchAll();
// SELECT * FROM `posts` WHERE id IN ('1', '2', '3');

Post::find()
    ->where(array('user_id = ? AND created_at = ?', 1, date('Y-m-d H:i:s'))
    ->fetchAll();
// SELECT * FROM `posts` WHERE user_id = '1' AND created_at = '2012-05-25 01:12:25'
```
#### Eagger Loading

```php
<?php

// A BelongsTo (many to one) relation
$post = Post::find(1)
    ->eager('user')
    ->fetch();
// SELECT * FROM `posts` WHERE id = '1';
// If post exists then
// SELECT * FROM `users` WHERE id = <user_id from post>

$post->user; // returns Larium\ActiveRecord\Relations\BelongsTo instance.
$post->getUser(); // returns User object
```

#### Fetching relationship object

```php
<?php
$post = Post::find(1)->fetch();
// SELECT * FROM `posts` WHERE id = '1';

$user = $post->user; // returns BelongsTo object
// No query to database yet

echo $user->fetch()->name;
// executes query
// SELECT * FROM `users` WHERE id = <user_id from post>

// A hasMany (one to many) relation
$comments = $post->comments; // return HasMany object

$comment = $comments->find()->where(array('id'=>'12')); // returns Query object. no query to database yet

$comment->fetch();
// SELECT * FROM `comments` WHERE post_id = <id from post> AND id = '12';
```

#### Adding / appending objects to HasMany relation

```php
<?php
 $post = Post::find(1)->fetch();

 $comment = new Comment();

 $post->comments[] = $comment;
 // INSERT INTO `comments` (post_id) VALUES (<id from post>);
```