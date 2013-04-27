<?php
namespace Bcserv\SourcepawnIncParser\PawnElement;

use Bcserv\SourcepawnIncParser\PawnElement;

class PawnFunction extends PawnElement
{
    protected $isStatic = false;
	protected $isFuncTag = false;
    protected $returnType;
    protected $arguments = array();
    protected $body = '';
    protected $bodyLineStart = 0;
    protected $types = array();

    // 'static' and 'functag' are not types
    static $keywords = array(
        'normal',
        'native',
        'public',
        'stock',
        'forward',
    );

    public function serialize()
    {
      return serialize(array(
        'arguments'     => $this->arguments,
        'body'          => $this->body,
        'bodyLineStart' => $this->bodyLineStart,
        'isFuncTag'     => $this->isFuncTag,
        'isStatic'      => $this->isStatic,
        'returnType'    => $this->returnType,
        'types'         => $this->types,
        'parent'        => parent::serialize(),
      ));
    }

    public function unserialize($data)
    {
      $data                = unserialize($data);
      $this->arguments     = $data['arguments'];
      $this->body          = $data['body'];
      $this->bodyLineStart = $data['bodyLineStart'];
      $this->isFuncTag     = $data['isFuncTag'];
      $this->isStatic      = $data['isStatic'];
      $this->returnType    = $data['returnType'];
      $this->types         = $data['types'];
      
      parent::unserialize($data['parent']);
    }

    static function IsPawnElement($pawnParser)
    {
        $word = $pawnParser->GetCurrentWord();
        
        $n=0;
        while (($char = $pawnParser->ReadChar(false)) !== false) {
            
            $n++;
            
            if (!$pawnParser->IsValidNameChar($char, $n - 1) && !$pawnParser->IsWhiteSpace($char) && $char != ':') {
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
        $this->line = $pp->GetLine();
        
        $head = "";
        
        while (($char = $pp->ReadChar()) !== false) {

            if ($char === '(') {
                break;
            }

            $head .= $char;
        }

        $this->ParseTypes($head);
        $this->ParseReturnType($head);
        $this->ParseName($head);
        $this->ParseArguments();
        $this->ParseBody();
    }

    public function ParseTypes($head)
    {
        $this->isStatic = false;

        $head = str_replace(array("\t", "\r", "\n"), ' ', $head);
        $len = strrpos($head, ' ');

        if ($len === FALSE) {
            $len = count($head);
		}

        $types = trim(substr($head, 0, $len));
        
        $toks2 = explode(' ', $types);
        
        foreach($toks2 as $type)
        {
            if ($type == 'static') {
                $this->isStatic = true;
            }
            else if ($type == 'functag') {
                $this->isFuncTag = true;
            }
            else {
                $pos = array_search($type, self::$keywords);
                if ($pos !== false) {
                    $this->types[] = self::$keywords[$pos];
                }
            }
        }
    }

    public function ParseReturnType($head)
    {
        $pos_colon = strpos($head, ':');
        
        if ($pos_colon === false) {
            $this->returnType = '';
            return '';
        }
        
        $pos_space = strrpos($head, ' ');
        if ($pos_space === false) {
            $pos_space = 0;
		}

        $head = str_replace(array("\t", "\r", "\n"), ' ', $head);
        $this->returnType = trim(substr($head, $pos_space, $pos_colon - $pos_space));
    }

    public function ParseName($head)
    {
        $head = str_replace(array("\t", "\r", "\n"), ' ', $head);

        $pos = strpos($head, ':');
        if ($pos === false) {
            $pos = strrpos($head, ' ');
		}
        else {
            $pos++;
		}

        $this->name = trim(substr($head, $pos));
    }

    public function ParseArguments()
    {
        $pp = $this->pawnParser;
        
        $arguments = array();
        $arg = '';
        $braceLevel = 0;
        $inString = false;
        $stringType = 0; // " = 1, ' = 2
        
        while (($char = $pp->ReadChar()) !== false) {

            if ($braceLevel == 0 && !$inString && ($char === ',' || $char === ')')) {
                $arg = trim($arg);
                
                if (empty($arg)) {
                    continue;
                }
                
                $arg_info = array(
				'byreference'	=> false,
				'isConstant'	=> false,
				'dimensions'	=> ''
			);

                $arg_info['string'] = $arg;
                
                if (substr($arg, 0, 6) == 'const ') {
                    $arg_info['isConstant'] = true;
                    $arg = substr($arg, 6);
                }
                
                if ($arg[0] == '&') {
                    $arg_info['byreference'] = true;
                    $arg = substr($arg, 1);
                }
                
                $pos = strpos($arg, ':');
                
                if ($pos !== false) {
                    $arg_info['type'] = trim(substr($arg, 0, $pos));
                    $name = trim(substr($arg, $pos+1));
                }
                else {
                    $arg_info['type'] = '';
                    $name = $arg;
                }
    
                $pos = strpos($name, '=');
    
                if ($pos !== false) {
                    $arg_info['name'] = trim(substr($name, 0, $pos));
                    $arg_info['defaultvalue'] = trim(substr($name, $pos+1));
                }
                else {
                    $arg_info['name'] = $name;
                    $arg_info['defaultvalue'] = null;
                }
    
                $pos = strpos($arg_info['name'], '[');
                
                if ($pos !== false) {
                    $arg_info['dimensions'] = substr($arg_info['name'], $pos);
                    $arg_info['name'] = substr($arg_info['name'], 0, $pos);
                }
                
                $arguments[] = $arg_info;
                $arg = '';
                
                if ($char === ')') {
                    $this->arguments = $arguments;
                    break;
                }
                
                continue;
            }

            $arg .= $char;

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
            else if(!$inString) {
                if ($char == '{') {
                    $braceLevel++;
                }
                else if($char == '}')
                {
                    $braceLevel--;
                }
            }
        }
    }

    protected function ParseBody()
    {
        $pp = $this->pawnParser;
        
        $body = '';
        $braceLevel = 0;
        $inString = false;
        $stringType = 0; // " = 1, ' = 2
        
        while (($char = $pp->ReadChar(false)) !== false)
        {
            if (PawnComment::IsPawnElement($pp)) {
                $pp->Jump(-1);
                $comment = new PawnComment($pp);
                $comment->Parse();
                
                $body .= $comment->GetRaw();
                continue;
            }

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
                if ($braceLevel == 0) {
					$braceLevel++;
                    $this->bodyLineStart = $pp->GetLine();
					continue;
				}

				$braceLevel++;
            }
            else if ($char == '}' && !$inString) {
                $braceLevel--;
                
                if ($braceLevel === 0) {
                    break;
                }
            }

            $body .= $char;
        }
        
        $this->body = $body;
        $this->lineEnd = $pp->GetLine();
    }

    public function __toString()
    {
        return 'Function (' . $this->GetName() . ')';
    }

    public function GetArguments()
    {
        return $this->arguments;
    }

	public function GetIsFuncTag() {
		return $this->isFuncTag;
	}

	public function SetIsFuncTag($boolean) {
		$this->isFuncTag = $boolean;
	}

    public function GetBody()
    {
        return $this->body;
    }

    public function GetBodyLineStart()
    {
        return $this->bodyLineStart;
    }

    public function GetReturnType()
    {
        return $this->returnType;
    }

    public function IsStatic()
    {
        return $this->isStatic;
    }

    public function GetTypes()
    {
        return $this->types;
    }
}
