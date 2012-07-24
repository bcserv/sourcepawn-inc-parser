<?php

abstract class PawnElement
{
	protected $pawnParser = false;

	protected $name = 'undefined';
	protected $type = -1;
	protected $line = 0;
	
	public function __construct($pawnParser)
	{
		$this->pawnParser = $pawnParser;
	}
	
	static function IsPawnElement($pawnParser)
	{
		return false;
	}
	
	public function Parse()
	{
		$this->line = $this->pawnParser->GetLine();
	}
	
	public function GetName()
	{
		return $this->name;
	}
}
