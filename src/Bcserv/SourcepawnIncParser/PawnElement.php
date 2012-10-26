<?php
namespace Bcserv\SourcepawnIncParser;

abstract class PawnElement
{
    protected $pawnParser = false;

    protected $name = 'undefined';
    protected $type = -1;
    protected $line = 0;
    protected $lineEnd = 0;
    
    public function __construct($pawnParser)
    {
        $this->pawnParser = $pawnParser;
    }
    
    static function IsPawnElement($pawnParser)
    {
        return false;
    }
    
    public function Parse()
    {
        $this->line = $this->pawnParser->GetLine();
        $this->lineEnd = $this->line;
    }
    
    public function GetName()
    {
        return $this->name;
    }
    
    public function GetType()
    {
        return $this->type;
    }
    
    public function GetTypeString()
    {
        return '';
    }
    
    public function GetLineStart()
    {
        return $this->line;
    }
    
    public function GetLineEnd()
    {
        return $this->lineEnd;
    }
    
    public function GetLines()
    {
        return array($this->line, $this->lineEnd);
    }
}
