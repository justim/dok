<?php

namespace Dok;

class Table implements \ArrayAccess, \IteratorAggregate, \Countable
{
    private $db;
    private $name;
    private $joinInfo;

    public function __construct(Database $db, $name, $joinInfo = null)
    {
        $this->db = $db;
        $this->name = $name;
        $this->joinInfo = $joinInfo;
    }

    public function getName()
    {
        return $this->name;
    }

    public function insert($values)
    {
        if (array_key_exists('id', $values) && $values['id'] === null) {
            $id = &$values['id'];
            unset($values['id']);
            $refId = true;
        } else {
            $refId = false;
        }

        Query::insert($this->db)
            ->table($this->name)
            ->values($values)
            ->exec();

        $newId = $this->db->lastInsertId();

        if ($refId) {
            $id = $newId;
        }

        return $this->offsetGet($newId);
    }

    public function delete($id)
    {
        Query::delete($this->db)
            ->table($this->name)
            ->where(['id = ?' => $id])
            ->exec();
    }

    public function all(Context $context = null)
    {
        $statement = $this->getStatement();

        return $this->list($statement, $context);
    }

    public function where($condition, Context $context = null)
    {
        $statement = $this->getStatement($condition);

        return $this->list($statement, $context);
    }

    private function getStatement($condition = null, $limit = null)
    {
        $query = Query::select($this->db)
            ->table($this->name)
            ->where($condition)
            ->limit($limit);

        if (!empty($this->joinInfo)) {
            @list($tableId, $columnId) = explode('.', $this->joinInfo);
            $tableName = $tableId . 's';

            $fields = [
                "{$tableId}.{$columnId}" => $columnId,
            ];

            $query->leftJoin(
                $tableName,
                "{$tableId}_id",
                'id',
                $fields
            );
        }

        return $query->exec();
    }

    private function list($executedStatement, Context $context = null)
    {
        if ($context === null) {
            $context = Context::createWithTable($this);
        } elseif ($context->hasRecord()) {
            $context = Context::createWithTableAndRecord($this, $context->getRecord());
        }

        return new DataSet(array_map(function ($record) {
            return new Record($this->db, $this, $record);
        }, $executedStatement->fetchAll(\PDO::FETCH_ASSOC)), $context);
    }

    public function offsetExists($id)
    {
        try {
            $this->offsetGet($id);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function offsetGet($id)
    {
        $statement = $this->getStatement(
            ["{$this->name}.id = ?" => $id],
            1
        );
        $record = $statement->fetch(\PDO::FETCH_ASSOC);

        if (!empty($record)) {
            return new Record($this->db, $this, $record);
        } else {
            throw new Exception('Could not find record: ' . $id);
        }
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
