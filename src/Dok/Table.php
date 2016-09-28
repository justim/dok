<?php

namespace Dok;

class Table implements \ArrayAccess, \IteratorAggregate, \Countable
{
	private $_db;
	private $_name;
	private $_joinInfo;

	private $_iterator;

	public function __construct(Database $db, $name, $joinInfo = null)
	{
		$this->_db = $db;
		$this->_name = $name;
		$this->_joinInfo = $joinInfo;
	}

	public function getName()
	{
		return $this->_name;
	}

	public function insert($values)
	{
		if (array_key_exists('id', $values) && $values['id'] === null)
		{
			$id = &$values['id'];
			unset($values['id']);
			$refId = true;
		}
		else
		{
			$refId = false;
		}

		Query::insert($this->_db)
			->table($this->_name)
			->values($values)
			->exec();

		$newId = $this->_db->lastInsertId();

		if ($refId)
		{
			$id = $newId;
		}

		return $this->offsetGet($newId);
	}

	public function delete($id)
	{
		Query::delete($this->_db)
			->table($this->_name)
			->where(['id = ?' => $id])
			->exec();
	}

	public function all(Context $context = null)
	{
		$statement = $this->_getStatement();

		return $this->_list($statement, $context);
	}

	public function where($condition, Context $context = null)
	{
		$statement = $this->_getStatement($condition);

		return $this->_list($statement, $context);
	}

	private function _getStatement($condition = null, $limit = null)
	{
		$query = Query::select($this->_db)
			->table($this->_name)
			->where($condition)
			->limit($limit);

		if (!empty($this->_joinInfo))
		{
			@list($tableId, $columnId) = explode('.', $this->_joinInfo);
			$tableName = $tableId . 's';

			$fields = [
				"{$tableId}.{$columnId}" => $columnId,
			];

			$query->leftJoin(
				$tableName,
				"{$tableId}_id",
				'id',
				$fields);
		}

		return $query->exec();
	}

	private function _list($executedStatement, Context $context = null)
	{
		if ($context === null)
		{
			$context = Context::createWithTable($this);
		}
		else if ($context->hasRecord())
		{
			$context = Context::createWithTableAndRecord($this, $context->getRecord());
		}

		return new DataSet(array_map(function($record)
		{
			return new Record($this->_db, $this, $record);
		}, $executedStatement->fetchAll(\PDO::FETCH_ASSOC)), $context);
	}

	public function offsetExists($id)
	{
		try
		{
			$this->offsetGet($id);

			return true;
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	public function offsetGet($id)
	{
		$statement = $this->_getStatement(
			["{$this->_name}.id = ?" => $id],
			1);
		$record = $statement->fetch(\PDO::FETCH_ASSOC);

		if (!empty($record))
		{
			return new Record($this->_db, $this, $record);
		}
		else
		{
			throw new Exception('Could not find record: ' . $id);
		}
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

	public function getIterator()
	{
		return $this->all();
	}

	public function count()
	{
		// might be a bit overkill to redo the query...
		return count($this->all());
	}
}
