<?php

namespace Dok;

class Database implements \ArrayAccess
{
	private $_connection;
	private $_tableNames = [];

	private function __construct(\PDO $connection)
	{
		$connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		$this->_connection = $connection;
	}

	public static function connectWithPDOConnectionString($connectionString)
	{
		try
		{
			$pdoConnection = new \PDO($connectionString);
			return new self($pdoConnection);
		}
		catch (\Exception $e)
		{
			throw new Exception("Invalid database: {$connectionString}", 0, $e);
		}
	}

	public static function connect($connectionString)
	{
		return self::connectWithPDOConnectionString($connectionString);
	}

	public function prepare($query)
	{
		return $this->_connection->prepare($query);
	}

	public function query($query)
	{
		return $this->_connection->query($query);
	}

	public function offsetExists($tableName)
	{
		try
		{
			$this->offsetGet($tableName);

			return true;
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	public function offsetGet($rawTableName)
	{
		@list($tableName, $joinInfo) = explode(':',	$rawTableName, 2);

		if ($this->_tableExists($tableName))
		{
			return new Table($this, $tableName, $joinInfo);
		}
		else
		{
			throw new Exception('Could not find table: ' . $tableName);
		}
	}

	public function offsetSet($offset, $value)
	{
		throw new Exception('Adding tables is not supported');
	}

	public function offsetUnset($offset)
	{
		throw new Exception('Deleting tables is not supported');
	}

	public function __get($tableName)
	{
		return $this->offsetGet($tableName);
	}

	public function lastInsertId()
	{
		return $this->_connection->lastInsertId();
	}

	private function _tableExists($tableName)
	{
		if (isset($this->_tableNames[$tableName]))
		{
			return $this->_tableNames[$tableName];
		}

		try
		{
			$query = Query::select($this)
				->table($tableName)
				->where('1=2');
			$statement = $query->getStatement();

			$this->_tableNames[$tableName] = true;
			return true;
		}
		catch (\Exception $e)
		{
			$this->_tableNames[$tableName] = false;
			return false;
		}
	}
}
