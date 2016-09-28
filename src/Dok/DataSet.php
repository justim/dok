<?php

namespace Dok;

class DataSet implements \ArrayAccess, \Iterator, \Countable
{
	private $_records;
	private $_context;

	private $_iteratorIndex = 0;

	public function __construct(array $records, Context $context)
	{
		$this->_records = $records;
		$this->_context = $context;
	}

	public function offsetExists($offset)
	{
		foreach ($this->_records as $record)
		{
			if ($record['id'] == $offset)
			{
				return true;
			}
		}

		return false;
	}

	public function offsetGet($offset)
	{
		foreach ($this->_records as $record)
		{
			if ($record['id'] == $offset)
			{
				return $record;
			}
		}

		return null;
	}

	public function offsetSet($offset, $values)
	{
		if ($offset !== null && !array_key_exists('id', $values))
		{
			$values['id'] = $offset;
		}

		return $this->insert($values);
	}

	public function offsetUnset($offset)
	{
		return $this->delete($offset);
	}

	public function insert($values)
	{
		$table = $this->_context->getTable();

		if ($this->_context->hasRecord())
		{
			$record = $this->_context->getRecord();
			$recordTable = $record->getTable();
			$foreignKey = substr($recordTable->getName(), 0, -1) . '_id';

			$values[$foreignKey] = $record['id'];
		}

		return $table->insert($values);
	}

	public function delete($id)
	{
		$table = $this->_context->getTable();

		return $table->delete($id);
	}

	public function current()
	{
		return $this->_records[$this->_iteratorIndex];
	}

	public function key()
	{
		$record = $this->_records[$this->_iteratorIndex];

		return $record['id'];
	}

	public function next()
	{
		$this->_iteratorIndex++;
	}

	public function rewind()
	{
		$this->_iteratorIndex = 0;
	}

	public function valid()
	{
		return isset($this->_records[$this->_iteratorIndex]);
	}

	public function count()
	{
		return count($this->_records);
	}
}
