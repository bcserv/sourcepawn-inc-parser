<?php

class PawnFunction extends PawnElement
{
	protected $isStatic = false;
	protected $returnType;
	protected $arguments = array();
	protected $body = "";

	// 'static' is not a type
	static $keywords = array(
		'normal',
		'native',
		'public',
		'stock',
		'forward'
	);
	
	static function IsPawnElement($pawnParser)
	{
		$word = $pawnParser->GetCurrentWord();
		
		$n=0;
		while (($char = $pawnParser->ReadChar(false)) !== false) {
			
			$n++;
			
			if (!$pawnParser->IsValidNameChar($char, 0) && !$pawnParser->IsWhiteSpace($char) && $char != ':') {
				break;
			}
		}
		
		$pawnParser->Jump(-$n);
		
		if ($char == '(') {
			return true;
		}
		
		return false;
	}
	
	public function Parse()
	{
		parent::Parse();

		$pp = $this->pawnParser;
		
		$head = "";
		
		while (($char = $pp->ReadChar()) !== false) {
			
			if ($char == ')') {
				break;
			}

			$head .= $char;
		}
		
		$toks = explode('(', $head);
		
		$this->ParseTypes($toks[0]);
		$this->ParseReturnType($toks[0]);
		$this->ParseName($toks[0]);
		$this->ParseArguments($toks[1]);
		$this->ParseBody();
	}
	
	public function ParseTypes($head)
	{
		$this->isStatic = false;

		$head = str_replace(array("\t", "\r", "\n"), ' ', $head);
		$types = trim(substr($head, 0, strrpos($head, ' ')));
		
		$toks2 = explode(' ', $types);
		
		foreach($toks2 as $type) {
			
			if ($type == 'static') {
				$this->isStatic = true;
			}
			else {
				$pos = array_search($type, PawnFunction::$keywords);
				if ($pos !== false) {
					$this->type = $pos;
				}
			}
		}
	}

	public function ParseReturnType($head)
	{
		$pos_colon = strpos($head, ':');
		
		if ($pos_colon !== false) {
			$this->returnType = '';
			return '';
		}
		
		$pos_space = strrpos($head, ' ');
		
		if ($pos_space === false) {
			$pos_space = 0;
		}

		$head = str_replace(array("\t", "\r", "\n"), ' ', $head);
		 $this->returnType = trim(substr($head, strrpos($head, ' '), $pos_colon));
	}

	public function ParseName($head)
	{
		$head = str_replace(array("\t", "\r", "\n"), ' ', $head);

		$pos = strpos($head, ':');
		
		if ($pos === false) {
			$pos = strpos($head, ' ');
		}

		$this->name = trim(substr($head, $pos+1));
	}

	public function ParseArguments($str)
	{
		$arguments = array();

		$args = explode(',', $str);
		
		foreach ($args as $arg) {
			
			$arg = trim($arg);
			
			if (empty($arg)) {
				continue;
			}
			
			$arg_info = array();
			$arg_info['string'] = $arg;
			
			$parts = explode(':', $arg);
			
			if ($parts[0][0] == '&') {
				$arg_info['byreference'] = true;
				$parts[0] = substr($parts[0], 1);
			}
			
			if (sizeof($parts) == 2) {
				$arg_info['type'] = $parts[0];
			}
			else {
				$arg_info['type'] = '';
			}
			
			$name = $parts[sizeof($parts)-1];
			
			$pos = strpos($name, '=');
			
			if ($pos !== false) {
				$arg_info['name'] = substr($name, 0, $pos);
				$arg_info['defaultvalue'] = substr($name, $pos);
			}
			else {
				$arg_info['name'] = $name;
				$arg_info['defaultvalue'] = '';
			}
			
			$pos = strpos($arg_info['name'], '[');
			
			if ($pos !== false) {
				$arg_info['dimensions'] = substr($arg_info['name'], $pos);
				$arg_info['name'] = substr($arg_info['name'], 0, $pos);
			}
			
			$arguments[] = $arg_info;
		}
		
		$this->arguments = $arguments;
	}

	protected function ParseBody()
	{
		$pp = $this->pawnParser;

		$body = "";
		$braceLevel = 0;
		$inString = false;
		$stringType = 0; // " = 1, ' = 2

		while (($char = $pp->ReadChar()) !== false) {
			
			if ($braceLevel == 0) {
				
				if ($char == ';') {
					break;
				}
			}
			
			if ($char == '"') {
				if ($inString) {
					if ($stringType == 1) {
						$inString = false;
					}
				}
				else {
					$inString = true;
					$stringType = 1;
				}
			}
			else if ($char == '\'') {
				if ($inString) {
					if ($stringType == 2) {
						$inString = false;
					}
				}
				else {
					$inString = true;
					$stringType = 2;
				}
			}
			if ($char == '{' && !$inString) {
				$braceLevel++;
			}
			else if ($char == '}' && !$inString) {
				$braceLevel--;
			}
			
			$body .= $char;
		}
		
		$this->body = $body;
	}
	
	public function __toString()
	{
        return 'Function (' . $this->GetName() . ')';
    }
}
