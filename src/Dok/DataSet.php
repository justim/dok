<?php

namespace Dok;

class DataSet implements \ArrayAccess, \Iterator, \Countable
{
    private $records;
    private $context;

    private $iteratorIndex = 0;

    public function __construct(array $records, Context $context)
    {
        $this->records = $records;
        $this->context = $context;
    }

    public function offsetExists($offset)
    {
        foreach ($this->records as $record) {
            if ($record['id'] == $offset) {
                return true;
            }
        }

        return false;
    }

    public function offsetGet($offset)
    {
        foreach ($this->records as $record) {
            if ($record['id'] == $offset) {
                return $record;
            }
        }

        return null;
    }

    public function offsetSet($offset, $values)
    {
        if ($offset !== null && !array_key_exists('id', $values)) {
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
        $table = $this->context->getTable();

        if ($this->context->hasRecord()) {
            $record = $this->context->getRecord();
            $recordTable = $record->getTable();
            $foreignKey = substr($recordTable->getName(), 0, -1) . '_id';

            $values[$foreignKey] = $record['id'];
        }

        return $table->insert($values);
    }

    public function delete($id)
    {
        $table = $this->context->getTable();

        return $table->delete($id);
    }

    public function current()
    {
        return $this->records[$this->iteratorIndex];
    }

    public function key()
    {
        $record = $this->records[$this->iteratorIndex];

        return $record['id'];
    }

    public function next()
    {
        $this->iteratorIndex++;
    }

    public function rewind()
    {
        $this->iteratorIndex = 0;
    }

    public function valid()
    {
        return isset($this->records[$this->iteratorIndex]);
    }

    public function count()
    {
        return count($this->records);
    }
}
