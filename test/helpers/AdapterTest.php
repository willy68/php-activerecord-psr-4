<?php

namespace Test\helpers;

use ActiveRecord\exceptions\DatabaseException;

class AdapterTest extends DatabaseTest
{
    public const InvalidDb = '__1337__invalid_db__';

    public function set_up($connection_name = null)
    {
        if (
            ($connection_name && !in_array($connection_name, \PDO::getAvailableDrivers())) ||
            \ActiveRecord\Config::instance()->get_connection($connection_name) == 'skip'
        ) {
            $this->markTestSkipped($connection_name . ' drivers are not present');
        }

        parent::set_up($connection_name);
    }

    public function test_i_has_a_default_port_unless_im_sqlite()
    {
        if ($this->conn instanceof \ActiveRecord\SqliteAdapter) {
            return true;
        }

        $c = $this->conn;
        $this->assertTrue($c::$DEFAULT_PORT > 0);
    }

    public function test_should_set_adapter_variables()
    {
        $this->assertNotNull($this->conn->protocol);
    }

    public function test_null_connection_string_uses_default_connection()
    {
        $this->assertNotNull(\ActiveRecord\Connection::instance(null));
        $this->assertNotNull(\ActiveRecord\Connection::instance(''));
        $this->assertNotNull(\ActiveRecord\Connection::instance());
    }

    /**
     */
    public function test_invalid_connection_protocol()
    {
        $this->expectException(DatabaseException::class);
        \ActiveRecord\Connection::instance('terribledb://user:pass@host/db');
    }

    /**
     */
    public function test_no_host_connection()
    {
        $this->expectException(DatabaseException::class);
        if (!$GLOBALS['slow_tests']) {
            throw new DatabaseException("");
        }

        \ActiveRecord\Connection::instance("{$this->conn->protocol}://user:pass");
    }

    /**
     */
    public function test_connection_failed_invalid_host()
    {
        $this->expectException(DatabaseException::class);
        if (!$GLOBALS['slow_tests']) {
            throw new DatabaseException("");
        }

        \ActiveRecord\Connection::instance("{$this->conn->protocol}://user:pass/1.1.1.1/db");
    }

    /**
     */
    public function test_connection_failed()
    {
        $this->expectException(DatabaseException::class);
        \ActiveRecord\Connection::instance("{$this->conn->protocol}://baduser:badpass@127.0.0.1/db");
    }

    /**
     */
    public function test_connect_failed()
    {
        $this->expectException(DatabaseException::class);
        \ActiveRecord\Connection::instance("{$this->conn->protocol}://zzz:zzz@127.0.0.1/test");
    }

    public function test_connect_with_port()
    {
        $config = \ActiveRecord\Config::instance();
        $name = $config->get_default_connection();
        $url = parse_url($config->get_connection($name));
        $conn = $this->conn;
        $port = $conn::$DEFAULT_PORT;

        $connection_string = "{$url['scheme']}://{$url['user']}";
        if (isset($url['pass'])) {
            $connection_string =  "{$connection_string}:{$url['pass']}";
        }
        $connection_string = "{$connection_string}@{$url['host']}:$port{$url['path']}";

        if ($this->conn->protocol != 'sqlite') {
            \ActiveRecord\Connection::instance($connection_string);
        }
    }

    /**
     */
    public function test_connect_to_invalid_database()
    {
        $this->expectException(DatabaseException::class);
        \ActiveRecord\Connection::instance("{$this->conn->protocol}://test:test@127.0.0.1/" . self::InvalidDb);
    }

    public function test_date_time_type()
    {
        $columns = $this->conn->columns('authors');
        $this->assertEquals('datetime', $columns['created_at']->raw_type);
        $this->assertEquals(\ActiveRecord\Column::DATETIME, $columns['created_at']->type);
        $this->assertTrue($columns['created_at']->length > 0);
    }

    public function test_date()
    {
        $columns = $this->conn->columns('authors');
        $this->assertEquals('date', $columns['some_Date']->raw_type);
        $this->assertEquals(\ActiveRecord\Column::DATE, $columns['some_Date']->type);
        $this->assertTrue($columns['some_Date']->length >= 7);
    }

    public function test_columns_no_inflection_on_hash_key()
    {
        $author_columns = $this->conn->columns('authors');
        $this->assertTrue(array_key_exists('author_id', $author_columns));
    }

    public function test_columns_nullable()
    {
        $author_columns = $this->conn->columns('authors');
        $this->assertFalse($author_columns['author_id']->nullable);
        $this->assertTrue($author_columns['parent_author_id']->nullable);
    }

    public function test_columns_pk()
    {
        $author_columns = $this->conn->columns('authors');
        $this->assertTrue($author_columns['author_id']->pk);
        $this->assertFalse($author_columns['parent_author_id']->pk);
    }

    public function test_columns_sequence()
    {
        if ($this->conn->supports_sequences()) {
            $author_columns = $this->conn->columns('authors');
            $this->assertEquals('authors_author_id_seq', $author_columns['author_id']->sequence);
        }
    }

    public function test_columns_default()
    {
        $author_columns = $this->conn->columns('authors');
        $this->assertEquals('default_name', $author_columns['name']->default);
    }

    public function test_columns_type()
    {
        $author_columns = $this->conn->columns('authors');
        $this->assertEquals('varchar', substr($author_columns['name']->raw_type, 0, 7));
        $this->assertEquals(\ActiveRecord\Column::STRING, $author_columns['name']->type);
        $this->assertEquals(25, $author_columns['name']->length);
    }

    public function test_columns_text()
    {
        $author_columns = $this->conn->columns('authors');
        $this->assertEquals('text', $author_columns['some_text']->raw_type);
        $this->assertEquals(null, $author_columns['some_text']->length);
    }

    public function test_columns_time()
    {
        $author_columns = $this->conn->columns('authors');
        $this->assertEquals('time', $author_columns['some_time']->raw_type);
        $this->assertEquals(\ActiveRecord\Column::TIME, $author_columns['some_time']->type);
    }

    public function test_query()
    {
        $sth = $this->conn->query('SELECT * FROM authors');

        while (($row = $sth->fetch())) {
            $this->assertNotNull($row);
        }

        $sth = $this->conn->query('SELECT * FROM authors WHERE author_id=1');
        $row = $sth->fetch();
        $this->assertEquals('Tito', $row['name']);
    }

    /**
     */
    public function test_invalid_query()
    {
        $this->expectException(DatabaseException::class);
        $this->conn->query('alsdkjfsdf');
    }

    public function test_fetch()
    {
        $sth = $this->conn->query('SELECT * FROM authors WHERE author_id IN(1,2,3)');
        $i = 0;
        $ids = array();

        while (($row = $sth->fetch())) {
            ++$i;
            $ids[] = $row['author_id'];
        }

        $this->assertEquals(3, $i);
        $this->assertEquals(array(1, 2, 3), $ids);
    }

    public function test_query_with_params()
    {
        $x = array('Bill Clinton', 'Tito');
        $sth = $this->conn->query('SELECT * FROM authors WHERE name IN(?,?) ORDER BY name DESC', $x);
        $row = $sth->fetch();
        $this->assertEquals('Tito', $row['name']);

        $row = $sth->fetch();
        $this->assertEquals('Bill Clinton', $row['name']);

        $row = $sth->fetch();
        $this->assertEquals(null, $row);
    }

    public function test_insert_id_should_return_explicitly_inserted_id()
    {
        $this->conn->query('INSERT INTO authors(author_id,name) VALUES(99,\'name\')');
        $this->assertTrue($this->conn->insert_id() > 0);
    }

    public function test_insert_id()
    {
        $this->conn->query("INSERT INTO authors(name) VALUES('name')");
        $this->assertTrue($this->conn->insert_id() > 0);
    }

    public function test_insert_id_with_params()
    {
        $x = array('name');
        $this->conn->query('INSERT INTO authors(name) VALUES(?)', $x);
        $this->assertTrue($this->conn->insert_id() > 0);
    }

    public function test_inflection()
    {
        $columns = $this->conn->columns('authors');
        $this->assertEquals('parent_author_id', $columns['parent_author_id']->inflected_name);
    }

    public function test_escape()
    {
        $s = "Bob's";
        $this->assertNotEquals($s, $this->conn->escape($s));
    }

    public function test_columnsx()
    {
        $columns = $this->conn->columns('authors');
        $names = array('author_id', 'parent_author_id', 'name', 'updated_at', 'created_at', 'some_Date', 'some_time', 'some_text', 'encrypted_password', 'mixedCaseField');

        if ($this->conn instanceof \ActiveRecord\OciAdapter) {
            $names = array_filter(array_map('strtolower', $names), function ($s) {
                return $s !== 'some_time';
            });
        }

        foreach ($names as $field) {
            $this->assertTrue(array_key_exists($field, $columns));
        }

        $this->assertEquals(true, $columns['author_id']->pk);
        $this->assertEquals('int', $columns['author_id']->raw_type);
        $this->assertEquals(\ActiveRecord\Column::INTEGER, $columns['author_id']->type);
        $this->assertTrue($columns['author_id']->length > 1);
        $this->assertFalse($columns['author_id']->nullable);

        $this->assertEquals(false, $columns['parent_author_id']->pk);
        $this->assertTrue($columns['parent_author_id']->nullable);

        $this->assertEquals('varchar', substr($columns['name']->raw_type, 0, 7));
        $this->assertEquals(\ActiveRecord\Column::STRING, $columns['name']->type);
        $this->assertEquals(25, $columns['name']->length);
    }

    public function test_columns_decimal()
    {
        $columns = $this->conn->columns('books');
        $this->assertEquals(\ActiveRecord\Column::DECIMAL, $columns['special']->type);
        $this->assertTrue($columns['special']->length >= 10);
    }

    private function limit($offset, $limit)
    {
        $ret = array();
        $sql = 'SELECT * FROM authors ORDER BY name ASC';
        $this->conn->query_and_fetch($this->conn->limit($sql, $offset, $limit), function ($row) use (&$ret) {
            $ret[] = $row;
        });
        return \ActiveRecord\collect($ret, 'author_id');
    }

    public function test_limit()
    {
        $this->assertEquals(array(2, 1), $this->limit(1, 2));
    }

    public function test_limit_to_first_record()
    {
        $this->assertEquals(array(3), $this->limit(0, 1));
    }

    public function test_limit_to_last_record()
    {
        $this->assertEquals(array(1), $this->limit(2, 1));
    }

    public function test_limit_with_null_offset()
    {
        $this->assertEquals(array(3), $this->limit(null, 1));
    }

    public function test_limit_with_nulls()
    {
        $this->assertEquals(array(), $this->limit(null, null));
    }

    public function test_fetch_no_results()
    {
        $sth = $this->conn->query('SELECT * FROM authors WHERE author_id=65534');
        $this->assertEquals(null, $sth->fetch());
    }

    public function test_tables()
    {
        $this->assertTrue(count($this->conn->tables()) > 0);
    }

    public function test_query_column_info()
    {
        $this->assertGreaterThan(0, count((array) $this->conn->query_column_info("authors")));
    }

    public function test_query_table_info()
    {
        $this->assertGreaterThan(0, count((array) $this->conn->query_for_tables()));
    }

    public function test_query_table_info_must_return_one_field()
    {
        $sth = $this->conn->query_for_tables();
        $this->assertEquals(1, count($sth->fetch()));
    }

    public function test_transaction_commit()
    {
        $original = $this->conn->query_and_fetch_one("select count(*) from authors");

        $this->conn->transaction();
        $this->conn->query("insert into authors(author_id,name) values(9999,'blahhhhhhhh')");
        $this->conn->commit();

        $this->assertEquals($original + 1, $this->conn->query_and_fetch_one("select count(*) from authors"));
    }

    public function test_transaction_rollback()
    {
        $original = $this->conn->query_and_fetch_one("select count(*) from authors");

        $this->conn->transaction();
        $this->conn->query("insert into authors(author_id,name) values(9999,'blahhhhhhhh')");
        $this->conn->rollback();

        $this->assertEquals($original, $this->conn->query_and_fetch_one("select count(*) from authors"));
    }

    public function test_show_me_a_useful_pdo_exception_message()
    {
        try {
            $this->conn->query('select * from an_invalid_column');
            $this->fail();
        } catch (\Exception $e) {
            $this->assertEquals(1, preg_match('/(an_invalid_column)|(exist)/', $e->getMessage()));
        }
    }

    public function test_quote_name_does_not_over_quote()
    {
        $c = $this->conn;
        $q = $c::$QUOTE_CHARACTER;
        $qn = function ($s) use ($c) {
            return $c->quote_name($s);
        };

        $this->assertEquals("{$q}string", $qn("{$q}string"));
        $this->assertEquals("string{$q}", $qn("string{$q}"));
        $this->assertEquals("{$q}string{$q}", $qn("{$q}string{$q}"));
    }

    public function test_datetime_to_string()
    {
        $datetime = '2009-01-01 01:01:01 EST';
        $this->assertEquals($datetime, $this->conn->datetime_to_string(date_create($datetime)));
    }

    public function test_date_to_string()
    {
        $datetime = '2009-01-01';
        $this->assertEquals($datetime, $this->conn->date_to_string(date_create($datetime)));
    }
}
