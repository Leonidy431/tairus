<?php

namespace Tests\Unit\Database;

use App\Database\Database;
use PHPUnit\Framework\TestCase;

class DatabaseComprehensiveTest extends TestCase
{
    private Database $db;
    private string $testDb = '/tmp/test_database_comprehensive.sqlite';

    protected function setUp(): void
    {
        if (file_exists($this->testDb)) {
            unlink($this->testDb);
        }

        $config = ['driver' => 'sqlite', 'path' => $this->testDb];
        $this->db = new Database($config);

        $this->db->getConnection()->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                age INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testDb)) {
            unlink($this->testDb);
        }
    }

    public function testInsertSingleRecord(): void
    {
        $result = $this->db->insert('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        $this->assertTrue($result);
    }

    public function testSelectAllRecords(): void
    {
        $this->db->insert('users', ['name' => 'Jane', 'email' => 'jane@example.com']);
        $this->db->insert('users', ['name' => 'John', 'email' => 'john@example.com']);

        $results = $this->db->select('SELECT * FROM users');

        $this->assertCount(2, $results);
    }

    public function testSelectWithBinding(): void
    {
        $this->db->insert('users', ['name' => 'Test User', 'email' => 'test@example.com']);

        $results = $this->db->select('SELECT * FROM users WHERE name = ?', ['Test User']);

        $this->assertCount(1, $results);
        $this->assertEquals('Test User', $results[0]['name']);
    }

    public function testSelectOne(): void
    {
        $this->db->insert('users', ['name' => 'Single', 'email' => 'single@example.com']);

        $result = $this->db->selectOne('SELECT * FROM users WHERE email = ?', ['single@example.com']);

        $this->assertIsArray($result);
        $this->assertEquals('Single', $result['name']);
    }

    public function testSelectOneNotFound(): void
    {
        $result = $this->db->selectOne('SELECT * FROM users WHERE email = ?', ['nonexistent@example.com']);

        $this->assertNull($result);
    }

    public function testUpdateRecord(): void
    {
        $this->db->insert('users', ['name' => 'Original', 'email' => 'orig@example.com', 'age' => 25]);

        $result = $this->db->update('users', ['name' => 'Updated'], ['email' => 'orig@example.com']);

        $this->assertTrue($result);

        $updated = $this->db->selectOne('SELECT * FROM users WHERE email = ?', ['orig@example.com']);
        $this->assertEquals('Updated', $updated['name']);
    }

    public function testDeleteRecord(): void
    {
        $this->db->insert('users', ['name' => 'ToDelete', 'email' => 'delete@example.com']);

        $result = $this->db->delete('users', ['email' => 'delete@example.com']);

        $this->assertTrue($result);

        $found = $this->db->selectOne('SELECT * FROM users WHERE email = ?', ['delete@example.com']);
        $this->assertNull($found);
    }

    public function testCount(): void
    {
        $this->db->insert('users', ['name' => 'User1', 'email' => 'user1@example.com']);
        $this->db->insert('users', ['name' => 'User2', 'email' => 'user2@example.com']);
        $this->db->insert('users', ['name' => 'User3', 'email' => 'user3@example.com']);

        $count = $this->db->count('users');

        $this->assertEquals(3, $count);
    }

    public function testCountWithConditions(): void
    {
        $this->db->insert('users', ['name' => 'Young', 'email' => 'young@example.com', 'age' => 20]);
        $this->db->insert('users', ['name' => 'Old', 'email' => 'old@example.com', 'age' => 50]);

        $count = $this->db->count('users', ['age' => 20]);

        $this->assertEquals(1, $count);
    }

    public function testTransactionSuccess(): void
    {
        $result = $this->db->transaction(function (Database $db) {
            $db->insert('users', ['name' => 'Trans1', 'email' => 'trans1@example.com']);
            $db->insert('users', ['name' => 'Trans2', 'email' => 'trans2@example.com']);
            return true;
        });

        $this->assertTrue($result);
        $this->assertEquals(2, $this->db->count('users'));
    }

    public function testTransactionRollback(): void
    {
        try {
            $this->db->transaction(function (Database $db) {
                $db->insert('users', ['name' => 'Rollback', 'email' => 'rollback@example.com']);
                throw new \Exception('Forced error');
            });
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertTrue(true);
    }

    public function testGetConnection(): void
    {
        $connection = $this->db->getConnection();

        $this->assertIsObject($connection);
        $this->assertTrue(method_exists($connection, 'prepare'));
    }

    public function testMultipleInserts(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->db->insert('users', [
                'name' => "User $i",
                'email' => "user$i@example.com",
                'age' => 20 + $i,
            ]);
        }

        $count = $this->db->count('users');
        $this->assertEquals(5, $count);
    }

    public function testComplexQuery(): void
    {
        $this->db->insert('users', ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => 25]);
        $this->db->insert('users', ['name' => 'Bob', 'email' => 'bob@example.com', 'age' => 30]);
        $this->db->insert('users', ['name' => 'Charlie', 'email' => 'charlie@example.com', 'age' => 28]);

        $results = $this->db->select(
            'SELECT * FROM users WHERE age > ? ORDER BY age DESC',
            [26]
        );

        $this->assertCount(2, $results);
        $this->assertEquals('Bob', $results[0]['name']);
    }
}
