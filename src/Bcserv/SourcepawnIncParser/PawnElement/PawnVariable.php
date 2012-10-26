<?php
namespace Bcserv\SourcepawnIncParser\PawnElement;

use Bcserv\SourcepawnIncParser\PawnElement;

class PawnVariable extends PawnElement
{
    static $keywords = array(
        'new',
        'decl',
        'public'
    );

    static function IsPawnElement($pawnParser)
    {
        if (in_array($pawnParser->GetCurrentWord(), self::$keywords)) {
            return true;
        }
        
        return false;
    }

    public function Parse()
    {
        parent::Parse();

        $pp = $this->pawnParser;

        // We don't handle variables for now, so let's just skip this line.
        fgets($pp->GetHandle()); $pp->Jump(-1);
    }
}
