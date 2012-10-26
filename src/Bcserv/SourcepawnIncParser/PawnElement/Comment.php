<?php
namespace Bcserv\SourcepawnIncParser\PawnElement;

class PawnComment extends PawnElement
{
    const PAWNCOMMENT_TYPE_SINGLELINE = 0;
    const PAWNCOMMENT_TYPE_MULTILINE  = 1;

    protected $text;
    protected $raw;

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

    public function _ReadChar($pp, $jump=false)
    {
        $char = $pp->ReadChar(false);
        $this->raw .= $char;
        return $char;
    }

    public function Parse()
    {
        parent::Parse();

        $pp = $this->pawnParser;
        
        // Serves the purpose of $pp->Jump(1), but saves to $raw
        $this->_ReadChar($pp, true);
        
        if ($this->_ReadChar($pp) == '/') {
            $this->type = PAWNCOMMENT_TYPE_SINGLELINE;
        }
        else {
            $this->type = PAWNCOMMENT_TYPE_MULTILINE;
        }
        
        $lastChar = "";

        while (($char = $this->_ReadChar($pp)) !== false) {
            
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
        
        $this->lineEnd = $pp->GetLine();
    }

    public function __toString()
    {
        return 'Comment';
    }

    public function GetText()
    {
        return $this->text;
    }

    public function GetRaw()
    {
        return $this->raw;
    }
}
