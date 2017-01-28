<?php

namespace Dok;

class DokTest extends \PHPUnit_Framework_TestCase
{
    private $db;

    public function setUp()
    {
        $this->db = Database::connectWithPDOConnectionString('sqlite::memory:');

        $this->db->query('CREATE TABLE `users` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `name` VARCHAR(50) DEFAULT NULL
        )');

        $this->db->query('CREATE TABLE `projects` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `user_id` INTEGER,
            `name` VARCHAR(50) DEFAULT NULL
        )');

        $user = $this->db['users']->insert([
            'name' => 'Tim',
        ]);

        $this->db['projects']->insert([
            'user_id' => $user['id'],
            'name' => 'Dok',
        ]);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Invalid database: foobar
     */
    public function testInvalidConnectionString()
    {
        $db = Database::connect('foobar');
    }

    public function testFetchField()
    {
        $this->assertEquals('Tim', $this->db['users'][1]['name']);
        $this->assertEquals('Tim', $this->db->users[1]['name']);
    }

    public function testExistenceOfRecord()
    {
        $this->assertTrue(isset($this->db['projects'][1]));
        $this->assertFalse(isset($this->db['projects'][2]));
    }

    public function testExistenceOfRecordColumns()
    {
        $this->assertTrue(isset($this->db['users'][1]['name']));
        $this->assertFalse(isset($this->db['users'][1]['email']));
        $this->assertNull($this->db['users'][1]['email']);

        // array_key_exists doesn't use the ArrayAccess methods... :(
        // http://stackoverflow.com/q/1538124/391892
        $this->assertFalse(array_key_exists('name', $this->db['users'][1]));
    }

    public function testFetchAllRecords()
    {
        $projects = $this->db['projects'];

        $this->assertEquals(1, count($projects));

        foreach ($projects as $id => $project) {
            $this->assertEquals(1, $id);
            $this->assertEquals('Dok', $project['name']);
        }
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Could not find table: links
     */
    public function testInvalidTable()
    {
        $this->db['links'];
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Adding tables is not supported
     */
    public function testAddingTablesShouldFail()
    {
        $this->db[] = [];
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Deleting tables is not supported
     */
    public function testDeletingTablesShouldFail()
    {
        unset($this->db['projects']);
    }

    public function testEditField()
    {
        $this->assertEquals('Tim', $this->db['users'][1]['name']);
        $this->db['users'][1]['name'] = 'justim';
        $this->assertEquals('justim', $this->db['users'][1]['name']);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage No new fields can be introduced: email
     */
    public function testEditNewColumnFails()
    {
        $user = $this->db['users'][1];
        $user['email'] = 'dok@example.com';
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Unsetting fields is not supported
     */
    public function testUnsettingColumnShouldFail()
    {
        $user = $this->db['users'][1];
        unset($user['name']);
    }

    public function testListRecordsAfterEdit()
    {
        $projects = $this->db['projects'];

        foreach ($projects as $project) {
            $this->assertEquals('Dok', $project['name']);
        }

        $this->db['projects'][1]['name'] = 'DokDok';

        // projects query is executed again
        foreach ($projects as $project) {
            $this->assertEquals('DokDok', $project['name']);
        }
    }

    public function testInsertIntoTable1()
    {
        $user = $this->db['users']->insert([
            'name' => 'John',
        ]);

        $this->assertEquals(2, $user['id']);
        $this->assertEquals(2, count($this->db['users']));
    }

    public function testInsertIntoTable2()
    {
        $this->db['users'][] = [
            'id' => &$id,
            'name' => 'John',
        ];

        $user = $this->db['users'][$id];

        $this->assertEquals($id, $user['id']);
        $this->assertEquals(2, count($this->db['users']));
    }

    public function testInsertIntoTable3()
    {
        $newId = 3;

        $this->db['users'][$newId] = [
            'name' => 'John',
        ];

        $user = $this->db['users'][$newId];

        $this->assertEquals($newId, $user['id']);
        $this->assertEquals(2, count($this->db['users']));
    }

    public function testDeleteRecord1()
    {
        $this->assertEquals(1, count($this->db['projects']));

        $this->db['projects']->delete(1);

        $this->assertEquals(0, count($this->db['projects']));
    }

    public function testDeleteRecord2()
    {
        $this->assertEquals(1, count($this->db['projects']));

        unset($this->db['projects'][1]);

        $this->assertEquals(0, count($this->db['projects']));
    }

    public function testDeleteRecord3()
    {
        $this->db['users']->insert([
            'name' => 'John',
        ]);

        $this->assertEquals(2, count($this->db['users']));

        $this->db['users']->delete(1);

        $this->assertEquals(1, count($this->db['users']));
    }

    public function testLinkedTable()
    {
        $this->assertTrue(isset($this->db['users'][1]['projects']));
        $this->assertTrue(isset($this->db['projects'][1]['user']));

        $this->assertEquals(
            'Tim',
            $this->db['projects'][1]['user']['name']
        );

        $this->assertEquals(
            'Dok',
            $this->db['users'][1]['projects'][1]['name']
        );

        $this->assertEquals(
            'Dok',
            $this->db['projects'][1]['user']['projects'][1]['name']
        );

        $this->assertEquals(
            'Tim',
            $this->db['projects'][1]['user']['projects'][1]['user']['name']
        );

        // und so weiter...
    }

    public function testLinkedTableFails()
    {
        $this->assertNull($this->db['users'][1]['projects'][2]);
    }

    public function testExistenceOfRecordLinkedTable()
    {
        $this->assertTrue(isset($this->db['users'][1]['projects'][1]));
        $this->assertFalse(isset($this->db['users'][1]['projects'][2]));
    }

    public function testInsertLinkedTable1()
    {
        $this->db['users'][1]['projects'][] = [
            'id' => &$id,
            'name' => 'DokTest',
        ];

        $this->assertEquals(2, $id);
        $this->assertEquals(2, count($this->db['projects']));

        $project = $this->db['projects'][2];

        $this->assertEquals(2, $project['id']);
        $this->assertEquals('Tim', $project['user']['name']);
    }

    public function testInsertLinkedTable2()
    {
        $newId = 3;

        $this->db['users'][1]['projects'][] = [
            'id' => $newId,
            'name' => 'John',
        ];

        $project = $this->db['projects'][$newId];

        $this->assertEquals($newId, $project['id']);
        $this->assertEquals('John', $project['name']);
    }

    public function testInsertLinkedTable3()
    {
        $project = $this->db['users'][1]['projects']->insert([
            'name' => 'John',
        ]);

        $this->assertEquals(2, $project['id']);
        $this->assertEquals('John', $project['name']);
    }

    public function testInsertLinkedTable4()
    {
        $newId = 3;

        $this->db['users'][1]['projects'][3] = [
            'name' => 'John',
        ];

        $project = $this->db['projects'][$newId];

        $this->assertEquals($newId, $project['id']);
        $this->assertEquals('John', $project['name']);
    }

    public function testDeleteRecordLinkedTable1()
    {
        $this->assertEquals(1, count($this->db['users'][1]['projects']));

        $this->db['users'][1]['projects']->delete(1);

        $this->assertEquals(0, count($this->db['users'][1]['projects']));
    }

    public function testDeleteRecordLinkedTable2()
    {
        $this->assertEquals(1, count($this->db['users'][1]['projects']));

        unset($this->db['users'][1]['projects'][1]);

        $this->assertEquals(0, count($this->db['users'][1]['projects']));
    }

    public function testBasicJoinSingleRecord()
    {
        $project = $this->db['projects:user.name'][1];

        $this->assertEquals('Tim', $project['user.name']);
    }

    public function testBasicJoinMultipleRecords()
    {
        $projects = $this->db['projects:user.name'];

        $this->assertEquals(1, count($projects));

        foreach ($projects as $project) {
            $this->assertEquals('Tim', $project['user.name']);
        }
    }

    public function testBasicJoinLinkedTable()
    {
        $projects = $this->db['users'][1]['projects:user.name'];

        $this->assertEquals(1, count($projects));

        foreach ($projects as $project) {
            $this->assertEquals('Tim', $project['user.name']);
        }
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage No table given for query
     */
    public function testMissingTableInQuery()
    {
        $query = Query::select($this->db);
        $query->getStatement();
    }
}
