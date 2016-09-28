<?php

namespace Dok;

class Context
{
	private $_table;
	private $_record;

	public static function createWithRecord(Record $record)
	{
		$instance = new self;
		$instance->_record = $record;

		return $instance;
	}

	public static function createWithTable(Table $table)
	{
		$instance = new self;
		$instance->_table = $table;

		return $instance;
	}

	public static function createWithTableAndRecord(Table $table, Record $record)
	{
		$instance = new self;
		$instance->_table = $table;
		$instance->_record = $record;

		return $instance;
	}

	public function hasRecord()
	{
		return isset($this->_record);
	}

	public function getRecord()
	{
		return $this->_record;
	}

	public function getTable()
	{
		return $this->_table;
	}
}
