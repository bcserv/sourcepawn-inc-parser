<?php

define('PAWNCOMMENT_TYPE_SINGLELINE',	0);
define('PAWNCOMMENT_TYPE_MULTILINE',	1);

class PawnComment extends PawnElement
{
	protected $text;
	
	static function IsPawnElement($pawnParser)
	{
		if ($pawnParser->GetCurrentChar() == '/') {
			
			$c = $pawnParser->ReadChar(false, true);

			if ($c == '/' || $c == '*') {
				return true;
			}
		}
		
		return false;
	}
	
	public function Parse()
	{
		parent::Parse();

		$pp = $this->pawnParser;
		
		$pp->Jump(1);
		
		if ($pp->ReadChar(false) == '/') {
			$this->type = PAWNCOMMENT_TYPE_SINGLELINE;
		}
		else {
			$this->type = PAWNCOMMENT_TYPE_MULTILINE;
		}
		
		$lastChar = "";

		while (($char = $pp->ReadChar(false)) !== false) {
			
			if ($this->type == PAWNCOMMENT_TYPE_SINGLELINE && $char == "\n") {
				break;
			}
			else if ($lastChar . $char == '*/') {
				$this->text = substr($this->text, 0, -1);
				break;
			}

			$this->text .= $char;

			$lastChar = $char;
		}
	}

	public function __toString()
	{
        return 'Comment';
    }
}
