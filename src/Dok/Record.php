<?php

namespace Dok;

class Record implements \ArrayAccess
{
	private $_db;
	private $_table;
	private $_values;
	private $_fieldNames;

	public function __construct(Database $db, Table $table, $values)
	{
		$this->_db = $db;
		$this->_table = $table;
		$this->_values = $values;

		$this->_fieldNames = array_keys($values);
	}

	public function getTable()
	{
		return $this->_table;
	}

	public function offsetExists($fieldName)
	{
		return $this->offsetGet($fieldName) !== null;
	}

	public function offsetGet($rawFieldName)
	{
		@list($fieldName, $rawJoinInfo) = explode(':', $rawFieldName);

		if (array_key_exists($fieldName, $this->_values))
		{
			return $this->_values[$fieldName];
		}
		else
		{
			$foreignKey = $fieldName . '_id';

			if (array_key_exists($foreignKey, $this->_values))
			{
				$tableName = $fieldName . 's';

				return $this->_db[$tableName][$this->_values[$foreignKey]];
			}

			if (substr($fieldName, -1) === 's')
			{
				$foreignKey = substr($this->_table->getName(), 0, -1) . '_id';

				$context = Context::createWithRecord($this);
				$joinInfo = !empty($rawJoinInfo) ? ":{$rawJoinInfo}" : '';
				return $this->_db[$fieldName . $joinInfo]
					->where(["{$foreignKey} = ?" => $this->_values['id']], $context);
			}
		}

		return null;
	}

	public function offsetSet($fieldName, $value)
	{
		if (array_search($fieldName, $this->_fieldNames))
		{
			$this->_values[$fieldName] = $value;

			$this->_save();
		}
		else
		{
			throw new Exception('No new fields can be introduced: ' . $fieldName);
		}
	}

	public function offsetUnset($fieldName)
	{
		throw new Exception('Unsetting fields is not supported');
	}

	private function _save()
	{
		return Query::update($this->_db)
			->table($this->_table->getName())
			->values($this->_values)
			->where(['id = ?' => $this->_values['id']])
			->exec();
	}
}
