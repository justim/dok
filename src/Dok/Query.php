<?php

namespace Dok;

class Query
{
	const SELECT = 'SELECT';
	const INSERT = 'INSERT';
	const UPDATE = 'UPDATE';
	const DELETE = 'DELETE';

	private $type;
	private $db;

	private $tableName = null;
	private $where = null;
	private $whereValues = [];
	private $limit = null;
	private $leftJoin = null;
	private $values = [];

	private function __construct($type, Database $db)
	{
		$this->type = $type;
		$this->db = $db;
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
		$this->tableName = $tableName;

		return $this;
	}

	public function leftJoin($tableName, $column, $foreignColumn, $fields)
	{
		$this->leftJoin = [
			'tableName' => $tableName,
			'column' => $column,
			'foreignColumn' => $foreignColumn,
			'fields' => $fields,
		];

		return $this;
	}

	public function where($condition)
	{
		$this->where = $condition;

		if (is_array($condition)) {
			$this->whereValues = array_merge($this->whereValues, $condition);
		}

		return $this;
	}

	public function limit($limit)
	{
		$this->limit = $limit;

		return $this;
	}

	public function values($values)
	{
		$this->values = $values;

		return $this;
	}

	public function getStatement()
	{
		$query = $this->getQuery();
		return $this->db->prepare($query);
	}

	public function exec()
	{
		$statement = $this->getStatement();
		$statement->execute(
			array_merge(
				array_values($this->values),
				array_values($this->whereValues)
			)
		);

		return $statement;
	}

	private function getQuery()
	{
		if (!isset($this->tableName)) {
			throw new Exception('No table given for query');
		}

		$result = '';

		switch ($this->type) {
			case self::SELECT:
				$result = $this->getSelectQuery();
				break;

			case self::INSERT:
				$result = $this->getInsertQuery();
				break;

			case self::UPDATE:
				$result = $this->getUpdateQuery();
				break;

			case self::DELETE:
				$result = $this->getDeleteQuery();
				break;
		}

		return $result;
	}

	private function getSelectQuery()
	{
		$escapedTableName = $this->escapeIdentifier($this->tableName);

		if (isset($this->leftJoin)) {
			$escapedJoinTableName = $this->escapeIdentifier($this->leftJoin['tableName']);
			$escapedColumn = $this->escapeIdentifier($this->leftJoin['column']);
			$escapedForeignColumn = $this->escapeIdentifier($this->leftJoin['foreignColumn']);

			$sqlColumns = "{$escapedTableName}.*";

			foreach ($this->leftJoin['fields'] as $as => $field) {
				$escapedFieldName = $this->escapeIdentifier($field);
				$escapedAsName = $this->escapeIdentifier($as);

				$sqlColumns .= ", {$escapedJoinTableName}.{$escapedFieldName} as {$escapedAsName}";
			}

			$sqlLeftJoin = "LEFT JOIN {$escapedJoinTableName}
				ON {$escapedTableName}.{$escapedColumn} = {$escapedJoinTableName}.{$escapedForeignColumn}";
		} else {
			$sqlColumns = "{$escapedTableName}.*";
			$sqlLeftJoin = '';
		}
		$sqlWhere = $this->getWhere();
		$sqlLimit = $this->limit !== null ? "LIMIT {$this->limit}" : '';

		return "SELECT {$sqlColumns}
			FROM {$escapedTableName}
			{$sqlLeftJoin}
			{$sqlWhere}
			{$sqlLimit}
			";
	}

	private function getInsertQuery()
	{
		return "INSERT INTO
			{$this->escapeIdentifier($this->tableName)}
			(" . implode(', ', array_keys($this->values)) . ")
			VALUES
			(" . implode(', ', array_fill(0, count($this->values), '?')) . ")";
	}

	private function getUpdateQuery()
	{
		$sqlWhere = $this->getWhere();
		$fields = implode(
			', ',
			array_map(
				function ($q) {
					return $this->escapeIdentifier($q) . ' = ?';
				},
				array_keys($this->values)
			)
		);

		return "UPDATE
			{$this->escapeIdentifier($this->tableName)}
			SET {$fields}
			{$sqlWhere}
			";
	}

	private function getDeleteQuery()
	{
		$sqlWhere = $this->getWhere();

		return "DELETE
			FROM {$this->escapeIdentifier($this->tableName)}
			{$sqlWhere}
			";
	}

	private function getWhere()
	{
		if ($this->where !== null) {
			if (is_array($this->where)) {
				$where = implode(' AND ', array_keys($this->where));
				return "WHERE {$where}";
			} else {
				return "WHERE {$this->where}";
			}
		} else {
			return '';
		}
	}

	private function escapeIdentifier($identifier)
	{
		return '`' . str_replace('`', '``', $identifier) . '`';
	}
}
