# Dok
> Databases as arrays

_Note: not really ready for anything production-like_

```php
$db = \Dok\Database::create('sqlite::memory:');
$db['projects'][] = [
    'name' => 'Dok',
];
echo $db['projects'][1]['name']; // Dok
```

## Features

### Basic operations

All basic database operations can be executed with normal array operations.

- Select:

  ```php
  $db['projects'][1];
  // SELECT * FROM projects WHERE id = 1
  ```

- Select multiple:

  ```php
  foreach ($db['projects'] as $project) { /* ... */ }
  // SELECT * FROM projects
  ```

- Insert:

  ```php
  $db['projects'][] = [ 'name' => 'Tim' ];
  // INSERT INTO projects (name) VALUES ('Tim')
  ```

- Delete:

  ```php
  unset($db['projects'][1]);
  // DELETE FROM projects WHERE id = 1
  ```

- Update:

  ```php
  $db['projects'][1]['name'] = 'Dok';
  // UDPATE projects SET name = 'Dok' WHERE id = 1
  ```

### Linked tables

```php
// this assumes that the projects table has a `user_id` column,
// and that a `users` table exists with a `id` column
$nameOfProjectOwner = $db['projects'][1]['user']['name'];

// using the same assumption, we can also go the other way
$projectsOfUser = $db['users'][1]['projects'];

// and again using this assumption, inserting is also possible
$db['users'][1]['projects'][] = [ 'name' => 'Vice' ];
// INSERT INTO projects (name, user_id) VALUES ('Vice', 1)
```

Linked tables behave the same a regular table would, so you can also update/delete those records.

### Joins

Some simple joins can be made. Currently uses a bit of a weird syntax...

```php
$projectsWithUserName = $db['projects:user.name'];
// [
//     'name' => 'Dok',
//     'user.name' => 'Tim',
// ]
// SELECT projects.*, users.name AS `user.name`
// FROM projects LEFT JOIN users ON projects.user_id = users.id
```

See the tests for more example on how to use Dok.

## How

So how does this all work? Basically we execute a query every time a array index is
accessed and return a new `ArrayAccess` instance representing the thing you asked for.

A Dok database instance represents a database, all the indexes refer to tables, which can only
be tables that actually exist (`$db['users']`). You get back a table instance, on which
you can fetch records with their `id` (`$db['users'][1]`). After that you can lookup
field for this records (`$db['users'][1]['name']`).
