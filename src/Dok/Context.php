<?php

namespace Dok;

class Context
{
	private $table;
	private $record;

	public static function createWithRecord(Record $record)
	{
		$instance = new self;
		$instance->record = $record;

		return $instance;
	}

	public static function createWithTable(Table $table)
	{
		$instance = new self;
		$instance->table = $table;

		return $instance;
	}

	public static function createWithTableAndRecord(Table $table, Record $record)
	{
		$instance = new self;
		$instance->table = $table;
		$instance->record = $record;

		return $instance;
	}

	public function hasRecord()
	{
		return isset($this->record);
	}

	public function getRecord()
	{
		return $this->record;
	}

	public function getTable()
	{
		return $this->table;
	}
}
