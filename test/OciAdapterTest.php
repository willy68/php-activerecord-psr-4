<?php

namespace Test;

use ActiveRecord\Config;
use ActiveRecord\Connection;
use Test\helpers\AdapterTest;

class OciAdapterTest extends AdapterTest
{
    public function set_up($connection_name = null)
    {
        parent::set_up('oci');
    }

    public function test_get_sequence_name()
    {
        $this->assertEquals('authors_seq', $this->conn->get_sequence_name('authors', 'author_id'));
    }

    public function test_columns_text()
    {
        $author_columns = $this->conn->columns('authors');
        $this->assertEquals('varchar2', $author_columns['some_text']->raw_type);
        $this->assertEquals(100, $author_columns['some_text']->length);
    }

    public function test_datetime_to_string()
    {
        $this->assertEquals('01-Jan-2009 01:01:01 AM', $this->conn->datetime_to_string(date_create('2009-01-01 01:01:01 EST')));
    }

    public function test_date_to_string()
    {
        $this->assertEquals('01-Jan-2009', $this->conn->date_to_string(date_create('2009-01-01 01:01:01 EST')));
    }

    public function test_insert_id()
    {
    }
    public function test_insert_id_with_params()
    {
    }
    public function test_insert_id_should_return_explicitly_inserted_id()
    {
    }
    public function test_columns_time()
    {
    }
    public function test_columns_sequence()
    {
    }

    public function test_set_charset()
    {
        $connection_string = Config::instance()->get_connection($this->connection_name);
        $conn = Connection::instance($connection_string . '?charset=utf8');
        $this->assertEquals(';charset=utf8', $conn->dsn_params);
    }
}
