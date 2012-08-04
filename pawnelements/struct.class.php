<?php

class PawnStruct extends PawnElement
{
    static function IsPawnElement($pawnParser)
    {
        if ($pawnParser->GetCurrentWord() == 'struct') {
            return true;
        }

        return false;
    }
    
    public function Parse()
    {
        parent::Parse();

        // We don't want to handle this element.
        while (($char = $this->pawnParser->ReadChar(false)) !== false) {
            
            if ($char == ';') {
                break;
            }
        }
    }
    
    public function __toString()
    {
        return 'Struct (' . $this->GetName() . ')';
    }
}
