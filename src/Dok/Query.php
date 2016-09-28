<?php

namespace Dok;

class Query
{
	const SELECT = 'SELECT';
	const INSERT = 'INSERT';
	const UPDATE = 'UPDATE';
	const DELETE = 'DELETE';

	private $_type;
	private $_db;

	private $_tableName = null;
	private $_where = null;
	private $_whereValues = [];
	private $_limit = null;
	private $_leftJoin = null;
	private $_values = [];

	private function __construct($type, Database $db)
	{
		$this->_type = $type;
		$this->_db = $db;
	}

	public static function select(Database $db)
	{
		return new self(self::SELECT, $db);
	}

	public static function insert(Database $db)
	{
		return new self(self::INSERT, $db);
	}

	public static function update(Database $db)
	{
		return new self(self::UPDATE, $db);
	}

	public static function delete(Database $db)
	{
		return new self(self::DELETE, $db);
	}

	public function table($tableName)
	{
		$this->_tableName = $tableName;

		return $this;
	}

	public function leftJoin($tableName, $column, $foreignColumn, $fields)
	{
		$this->_leftJoin = [
			'tableName' => $tableName,
			'column' => $column,
			'foreignColumn' => $foreignColumn,
			'fields' => $fields,
		];

		return $this;
	}

	public function where($condition)
	{
		$this->_where = $condition;

		if (is_array($condition))
		{
			$this->_whereValues = array_merge($this->_whereValues, $condition);
		}

		return $this;
	}

	public function limit($limit)
	{
		$this->_limit = $limit;

		return $this;
	}

	public function values($values)
	{
		$this->_values = $values;

		return $this;
	}

	public function getStatement()
	{
		$query = $this->_getQuery();
		return $this->_db->prepare($query);
	}

	public function exec()
	{
		$statement = $this->getStatement();
		$statement->execute(
			array_merge(
				array_values($this->_values),
				array_values($this->_whereValues)));

		return $statement;
	}

	private function _getQuery()
	{
		if (!isset($this->_tableName))
		{
			throw new Exception('No table given for query');
		}

		$result = '';

		switch ($this->_type)
		{
			case self::SELECT:
				$result = $this->_getSelectQuery();
				break;

			case self::INSERT:
				$result = $this->_getInsertQuery();
				break;

			case self::UPDATE:
				$result = $this->_getUpdateQuery();
				break;

			case self::DELETE:
				$result = $this->_getDeleteQuery();
				break;
		}

		return $result;
	}

	private function _getSelectQuery()
	{
		$escapedTableName = $this->_escapeIdentifier($this->_tableName);

		if (isset($this->_leftJoin))
		{
			$escapedJoinTableName = $this->_escapeIdentifier($this->_leftJoin['tableName']);
			$escapedColumn = $this->_escapeIdentifier($this->_leftJoin['column']);
			$escapedForeignColumn = $this->_escapeIdentifier($this->_leftJoin['foreignColumn']);

			$sqlColumns = "{$escapedTableName}.*";

			foreach ($this->_leftJoin['fields'] as $as => $field)
			{
				$escapedFieldName = $this->_escapeIdentifier($field);
				$escapedAsName = $this->_escapeIdentifier($as);

				$sqlColumns .= ", {$escapedJoinTableName}.{$escapedFieldName} as {$escapedAsName}";
			}

			$sqlLeftJoin = "LEFT JOIN {$escapedJoinTableName} ON {$escapedTableName}.{$escapedColumn} = {$escapedJoinTableName}.{$escapedForeignColumn}";
		}
		else
		{
			$sqlColumns = "{$escapedTableName}.*";
			$sqlLeftJoin = '';
		}
		$sqlWhere = $this->_getWhere();
		$sqlLimit = $this->_limit !== null ? "LIMIT {$this->_limit}" : '';

		return "SELECT {$sqlColumns}
			FROM {$escapedTableName}
			{$sqlLeftJoin}
			{$sqlWhere}
			{$sqlLimit}
			";
	}

	private function _getInsertQuery()
	{
		return "INSERT INTO
			{$this->_escapeIdentifier($this->_tableName)}
			(" . implode(', ', array_keys($this->_values)) . ")
			VALUES
			(" . implode(', ', array_fill(0, count($this->_values), '?')) . ")";
	}

	private function _getUpdateQuery()
	{
		$sqlWhere = $this->_getWhere();
		$fields = implode(
			', ',
			array_map(
				function($q)
				{
					return $this->_escapeIdentifier($q) . ' = ?';
				},
				array_keys($this->_values)));

		return "UPDATE
			{$this->_escapeIdentifier($this->_tableName)}
			SET {$fields}
			{$sqlWhere}
			";
	}

	private function _getDeleteQuery()
	{
		$sqlWhere = $this->_getWhere();

		return "DELETE
			FROM {$this->_escapeIdentifier($this->_tableName)}
			{$sqlWhere}
			";
	}

	private function _getWhere()
	{
		if ($this->_where !== null)
		{
			if (is_array($this->_where))
			{
				$where = implode(' AND ', array_keys($this->_where));
				return "WHERE {$where}";
			}
			else
			{
				return "WHERE {$this->_where}";
			}
		}
		else
		{
			return '';
		}
	}

	private function _escapeIdentifier($identifier)
	{
		return '`' . str_replace('`', '``', $identifier) . '`';
	}
}
