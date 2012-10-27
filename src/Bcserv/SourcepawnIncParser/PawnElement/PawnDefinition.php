<?php
namespace Bcserv\SourcepawnIncParser\PawnElement;

use Bcserv\SourcepawnIncParser\PawnElement;

class PawnDefinition extends PawnElement
{
    protected $value = '';
    
    static function IsPawnElement($pawnParser)
    {
        return ($pawnParser->GetCurrentWord() == "#define");
    }

    public function Parse()
    {
        parent::Parse();

        $pp = $this->pawnParser;
        $this->name = '';

        $pp->Jump(7);
        $pp->SkipWhiteSpace();

        while ($char = $pp->ReadChar()) {
            
            if ($pp->IsSpace($char)) {
                $pp->Jump(-1);
                
                break;
            }

            $this->name .= $char;
        }
        
        $pp->SkipWhiteSpace();
        
        $char = $pp->ReadChar(true, true);

        if ($char != '/' && !$pp->IsSpace($char)) {
            $this->value = $pp->ReadToEndOfLine();
        }
    }

    public function __toString()
    {
        return 'Defintion (' . $this->name . ')';
    }
}
