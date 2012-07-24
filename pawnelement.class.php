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
	
	abstract static function IsPawnElement($pawnParser);
	
	public function Parse()
	{
		$this->line = $this->pawnParser->GetLine();
	}
	
	public function GetName()
	{
		return $this->name;
	}
}
