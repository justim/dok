<?php

namespace Dok;

class Record implements \ArrayAccess
{
	private $db;
	private $table;
	private $values;
	private $fieldNames;

	public function __construct(Database $db, Table $table, $values)
	{
		$this->db = $db;
		$this->table = $table;
		$this->values = $values;

		$this->fieldNames = array_keys($values);
	}

	public function getTable()
	{
		return $this->table;
	}

	public function offsetExists($fieldName)
	{
		return $this->offsetGet($fieldName) !== null;
	}

	public function offsetGet($rawFieldName)
	{
		@list($fieldName, $rawJoinInfo) = explode(':', $rawFieldName);

		if (array_key_exists($fieldName, $this->values)) {
			return $this->values[$fieldName];
		} else {
			$foreignKey = $fieldName . '_id';

			if (array_key_exists($foreignKey, $this->values)) {
				$tableName = $fieldName . 's';

				return $this->db[$tableName][$this->values[$foreignKey]];
			}

			if (substr($fieldName, -1) === 's') {
				$foreignKey = substr($this->table->getName(), 0, -1) . '_id';

				$context = Context::createWithRecord($this);
				$joinInfo = !empty($rawJoinInfo) ? ":{$rawJoinInfo}" : '';
				return $this->db[$fieldName . $joinInfo]
					->where(["{$foreignKey} = ?" => $this->values['id']], $context);
			}
		}

		return null;
	}

	public function offsetSet($fieldName, $value)
	{
		if (array_search($fieldName, $this->fieldNames)) {
			$this->values[$fieldName] = $value;

			$this->save();
		} else {
			throw new Exception('No new fields can be introduced: ' . $fieldName);
		}
	}

	public function offsetUnset($fieldName)
	{
		throw new Exception('Unsetting fields is not supported');
	}

	private function save()
	{
		return Query::update($this->db)
			->table($this->table->getName())
			->values($this->values)
			->where(['id = ?' => $this->values['id']])
			->exec();
	}
}
