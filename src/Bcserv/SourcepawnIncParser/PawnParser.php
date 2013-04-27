<?php
namespace Bcserv\SourcepawnIncParser;

use Bcserv\SourcepawnIncParser\PawnElement;

class PawnParser
{
    protected $elements		= array();
    protected $callback		= false;
    protected $handle		= false;
    protected $lineNumber	= 1;
	protected $lastComment  = null;
	protected $linebreaksSinceLastComment = 0;

    public function __construct($callback)
    {
        $this->callback = $callback;

        $this->RegisterPawnElement('\Bcserv\SourcepawnIncParser\PawnElement\PawnComment');
        $this->RegisterPawnElement('\Bcserv\SourcepawnIncParser\PawnElement\PawnDefinition');
        $this->RegisterPawnElement('\Bcserv\SourcepawnIncParser\PawnElement\PawnEnum');
        $this->RegisterPawnElement('\Bcserv\SourcepawnIncParser\PawnElement\PawnFunction');
        $this->RegisterPawnElement('\Bcserv\SourcepawnIncParser\PawnElement\PawnStruct');
        $this->RegisterPawnElement('\Bcserv\SourcepawnIncParser\PawnElement\PawnVariable');
    }

    public function RegisterPawnElement($element)
    {
        $this->elements[] = $element;
    }

	public function ParseFile($file)
    {
        $handle = fopen($file, 'r');

        if ($handle === false) {
            return;
		}

        $this->handle = $handle;
        
        while (!feof($handle)) {
            
            $offset = ftell($handle);
            $this->word = '';

            while ($this->ReadChar(true) !== false) {
                ob_flush(); flush();
                
                if ($this->IsValidElementNameChar($this->char, strlen($this->word)+1)) {
                    $this->word .= $this->char;
                }
                else if (strlen($this->word) >= 1) {
                    
                    if ($this->word[0] == '#' && substr($this->word, 1, 6) != 'define') {
                        // Not handled macro, skip this line
                        fgets($handle);
                        $this->lineNumber++;
                        
                        break;
                    }

                    foreach ($this->elements as $element) {

                        if ($element::IsPawnElement($this)) {
                            fseek($handle, $offset);
                            $pawnElement = new $element($this);

							if ($this->linebreaksSinceLastComment == 1) {
								// The last comment seems to belong to this element
								// because there is only one linebreak in between them.
								$pawnElement->SetComment($this->lastComment);
							}

                            $pawnElement->Parse();

                            $callback = $this->callback;
                            if (is_callable($callback)) {
                                call_user_func($callback, $pawnElement);
                            }
                        }
                    }

                    break;
                }
                
                if ($this->IsSpace($this->char)) {
                    break;
                }
            }
        }
 
        fclose($handle);
        $this->handle = false;
    }
   
    public function GetHandle()
    {
        return $this->handle;
    }
    
    public function GetCurrentChar()
    {
        return $this->char;
    }
    
    public function GetCurrentWord()
    {
        return $this->word;
    }
    
    public function GetLine()
    {
        return $this->lineNumber;
    }

    // Debugging function to get the entire line
    public function _GetLineString($line=null)
    {
        static $file_lines = null;
        if ($file_lines === null)
        {
            $pos = ftell($this->handle);
            fseek($this->handle, 0, SEEK_SET);
            
            $file_lines = array();
            while (($file_lines[] = fgets($this->handle)) !== FALSE);
            
            fseek($this->handle, $pos, SEEK_SET);
        }
        
        if ($line === null)
            $line = $this->GetLine();
        
        return $file_lines[$line-1];
    }

    public function ReadChar($parseComments=true, $rewind=false)
    {
        $this->char = fgetc($this->handle);

        if ($parseComments && PawnElement\PawnComment::IsPawnElement($this)) {
            $this->Jump(-1);
            $pawnComment = new PawnElement\PawnComment($this);
            $pawnComment->Parse();

			// Push the comment to our callback function
            if (is_callable($this->callback)) {
                call_user_func($this->callback, $pawnComment);
            }

			// Save this comment, so we can possibly connect
			// it to the next element on the next line.
			$this->lastComment = $pawnComment;
			$this->linebreaksSinceLastComment = 0;

            //$this->Jump(-1);
            $this->char = fgetc($this->handle);

			//return $this->char;
            //return false;
        }
        
        if ($rewind) {
            $this->Jump(-1);
        }
        else if ($this->char == "\n") {
            $this->lineNumber++;
			$this->linebreaksSinceLastComment++;
        }
        
        return $this->char;
    }
    
    public function Jump($pos = -1)
    {
        fseek($this->handle, $pos, SEEK_CUR);
    }
    
    public function IsSpace($char)
    {
        return ($char == ' ' || $char == "\t" || $char == "\r" || $char == "\n");
    }
    
    public function IsWhiteSpace($char)
    {
        return ($char == ' ' || $char == "\t");
    }

    public function SkipWhiteSpace()
    {
        while (($char = $this->ReadChar()) !== false) {
            
            if (!$this->IsWhiteSpace($char)) {
                $this->Jump(-1);
                return;
            }
        }
    }
    
    public function ReadValue()
    {
        $value = '';
        $lastChar = '';
        $inString = false;
        $colonLevel = 0;

        while (($char = $this->ReadChar()) !== false) {
            
            if (!$inString) {
                if ($char == '(') {
                    $colonLevel++;
                }
                else if ($char == ')') {
                    $colonLevel--;
                }
                else if ($this->IsSpace($char) && $colonLevel == 0) {
                    break;
                }
                else if ($lastChar . $char == '//' || $lastChar . $char == '/*') {
                    $this->Jump(-2);
                    break;
                }
            }
            
            if ($char == '"') {
                
                if ($inString && $lastChar != '\\') {
                    $inString = false;
                }
                else {
                    $inString = true;
                }
            }

            $value .= $char;

            $lastChar = $char;
        }
        
        return $value;
    }
    
    /* Reads the string to the end of the line (allowing for backslashes to
     * escape newlines). Useful for #defines.
     */
    public function ReadToEndOfLine()
    {
        $value = '';
        $lastChar = '';
        
        while (($char = $this->ReadChar()) !== false)
        {
            if ($lastChar != "\\" && $char == "\n")
                break;
            
            $value .= $char;
            $lastChar = $char;
        }
        
        return $value;
    }

    public function IsValidElementNameChar($str, $pos)
    {
        $len = strlen($str);
        for ($i=0; $i < $len; $i++) {
            
            $char = $str[$i];
            
            if ($char >= 'a' && $char <= 'z') {
                continue;
            }
            
            if ($char >= 'A' && $char <= 'Z') {
                continue;
            }
            
            if ($pos == 1) {
                if ($char == '#') {
                    continue;
                }
            }
            else if (is_numeric($char)) {
                continue;
            }
            
            return false;
        }
        
        return true;
    }
    
    public function IsValidNameChar($str, $pos)
    {
        $len = strlen($str);
        for ($i=0; $i < $len; $i++) {
            
            $char = $str[$i];
            
            if ($char >= 'a' && $char <= 'z') {
                continue;
            }
            
            if ($char >= 'A' && $char <= 'Z') {
                continue;
            }
            
            if ($char == '_')
                continue;

            if ($pos > 1 && is_numeric($char)) {
                continue;
            }
            
            return false;
        }
        
        return true;
    }
    
    public function GetWord($break=" \t\r\n")
    {
        $word =    "";
        $pos =    1;

        while (($char = $this->ReadChar()) !== false) {
            
            if (strpos($break, $char) !== false) {
                $this->Jump(-1);
                break;
            }
            
            $word .= $char;
            $pos++;
        }
        
        return $word;
    }
}
