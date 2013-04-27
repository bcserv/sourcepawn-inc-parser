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

        $n = 0;
        $line = "";
        $inBody = false;

        while (($char = $pp->ReadChar(true, false)) !== false) {

            if ($char == '{') {
                $inBody = true;
                continue;
            }
			else if ($char == ')' && !$inBody) {
				$pawnFunction = new PawnFunction($pp);
				$pawnFunction->SetIsFuncTag(true);
				$pawnFunction->Parse();
			}
            else if ($inBody) {
                if ($char == '(' && $this->type == self::PAWNENUM_TYPE_FUNC) {
                    $this->ParseFuncEnumLine($line);
                    $line = '';
                    continue;
                }
                if ($char == ',' || $char == '}') {
                    if ($this->type == self::PAWNENUM_TYPE_NORMAL) {
                      $this->ParseNormalEnumLine($line, $n);
                      $n++;
                      $line = '';
                    }
                    if ($char == '}') {
                        break;
                    }
                  
                  continue;
                }
                
                $line .= $char;
            }
        }

        $this->lineEnd = $pp->GetLine();
    }

    protected function ParseNormalEnumLine($line, &$n)
    {
        $line = trim($line);
        if(empty($line))
            return;
        
        $element = array();
            
        $pos = strpos($line, '=');
        
        if ($pos !== false) {
            $first = trim(substr($line, 0, $pos));
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
            $element['type'] = trim(substr($first, 0, $pos));
            $element['name'] = trim(substr($first, $pos+1));
        }
        else {
            $element['type'] = '';
            $element['name'] = $first;
        }
        
        $this->elements[] = $element;
        
        return $n;
    }

    protected function ParseFuncEnumLine($head)
    {
        $head = trim($head);
        if(empty($head))
            return;
        
        $pawnFunction = new PawnFunction($this->pawnParser);
		$pawnFunction->SetIsFuncTag(true);
        
        $pawnFunction->ParseReturnType($head);
        $pawnFunction->ParseArguments();
        
        $this->elements[] = $pawnFunction;
    }

    public function __toString()
    {
        $value = -1;
        $ret = $this->type == self::PAWNENUM_TYPE_FUNC ? 'funcenum' : 'enum';
        
        if (!empty($this->name)) {
            $ret .= ' ' . $this->name;
        }
        
        $ret .= "\n{";
        
        foreach ($this->elements as $element) {
            $ret .= "\n\t";
            
            if ($this->type == self::PAWNENUM_TYPE_FUNC) {
                $ret .= $element . ',';
            }
            else {
                if (!empty($element['type'])) {
                    $ret .= $element['type'] . ':';
                }
                
                $ret .= $element['name'];
                
                if ($element['value'] !== $value + 1) {
                    $ret .= ' = ' . $element['value'];
                }
                
                $ret .= ',';
                $value = $element['value'];
            }
        }
        
        $ret .= "\n}";
        
        return $ret;
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
