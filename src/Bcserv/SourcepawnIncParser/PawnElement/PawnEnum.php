<?php
namespace Bcserv\SourcepawnIncParser\PawnElement;

use Bcserv\SourcepawnIncParser\PawnElement;
use Bcserv\SourcepawnIncParser\PawnElement\PawnFunction;

class PawnEnum extends PawnElement
{
    const PAWNENUM_TYPE_NORMAL = 0;
    const PAWNENUM_TYPE_FUNC   = 1;

    static $types = array(
        'enum',
        'funcenum'
    );

    protected $elements = array();
    
    public function serialize()
    {
      return serialize(array(
        'elements' => $this->elements,
        'parent'   => parent::serialize(),
      ));
    }

    public function unserialize($data)
    {
      $data           = unserialize($data);
      $this->elements = $data['elements'];
      
      parent::unserialize($data['parent']);
    }

    static function IsPawnElement($pawnParser)
    {
        if (in_array($pawnParser->GetCurrentWord(), self::$types)) {
            return true;
        }

        return false;
    }

    public function Parse()
    {
        parent::Parse();

        $this->ParseType();
        $this->ParseName();
        $this->ParseBody();

        $this->pawnParser->Jump(1);
    }

    protected function ParseType()
    {
        $word = $this->pawnParser->GetWord();
        $pos = array_search($word, self::$types);
        
        if ($pos !== false) {
            $this->type = $pos;
        }
    }

    protected function ParseName()
    {
        $pp = $this->pawnParser;

        $pp->SkipWhiteSpace();

        if ($pp->ReadChar(true, true) != '{') {
            $this->name = $pp->GetWord();
        }
    }

    protected function ParseBody()
    {
        $pp = $this->pawnParser;

        $body = "";
        $inBody = false;

        while (($char = $pp->ReadChar(true, false)) !== false) {

            if ($char == '{') {
                $inBody = true;
                continue;
            }
            else if ($char == '}') {
                break;
            }
			else if ($char == ')' && !$inBody) {
				$pawnFunction = new PawnFunction($this);
				$pawnFunction->SetIsFuncTag(true);
				$pawnFunction->Parse();
			}

            if ($inBody) {
                $body .= $char;
            }
        }

        $body = trim($body);

        if ($this->type == self::PAWNENUM_TYPE_NORMAL) {
            $lines = explode(',', $body);
        }
        else {
            $lines = explode('),', $body);
        }

        $n = 0;
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line)) {
                continue;
            }
            
            if ($this->type == self::PAWNENUM_TYPE_NORMAL) {
                $n = $this->ParseNormalEnumLine($line, $n);
                $n++;
            }
            else {
                $this->ParseFuncEnumLine($line);
            }
        }
        
        $this->body = $body;
        $this->lineEnd = $pp->GetLine();
    }

    protected function ParseNormalEnumLine($line, $n)
    {
        $element = array();
            
        $pos = strpos($line, '=');
        
        if ($pos !== false) {
            $first = substr($line, 0, $pos);
            $element['value'] = trim(substr($line, $pos+1));

            if (is_numeric($element['value'])) {
                $n = $element['value'];
            }
        }
        else {
            $first = $line;
            $element['value'] = $n;
        }
        
        $pos = strpos($first, ':');
        
        if ($pos !== false) {
            $element['type'] = substr($first, 0, $pos);
            $element['name'] = substr($first, $pos);
        }
        else {
            $element['type'] = '';
            $element['name'] = $first;
        }
        
        $this->elements[] = $element;
        
        return $n;
    }

    protected function ParseFuncEnumLine($line)
    {
        $pawnFunction = new PawnFunction($this->pawnParser);
		$pawnFunction->SetIsFuncTag(true);

        $toks = explode('(', $line);
        
        $pawnFunction->ParseReturnType($toks[0]);
        $pawnFunction->ParseArguments($toks[1]);
        
        $this->elements[] = $pawnFunction;
    }

    public function __toString()
    {
        return 'Enum (' . $this->GetName() . ')';
    }

	public function getType()
	{
		return $this->type;
	}

	public function GetElements()
	{
		return $this->elements;
	}
}
