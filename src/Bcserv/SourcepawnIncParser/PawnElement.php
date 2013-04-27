<?php
namespace Bcserv\SourcepawnIncParser;

abstract class PawnElement implements \Serializable
{
    protected $pawnParser = false;

    protected $name = '';
    protected $type = -1;
    protected $line = 0;
    protected $lineEnd = 0;
	protected $comment = null;

    public function __construct($pawnParser)
    {
        $this->pawnParser = $pawnParser;
    }
    
    public function serialize()
    {
      return serialize(array(
        'comment' => $this->comment,
        'line'    => $this->line,
        'lineEnd' => $this->lineEnd,
        'name'    => $this->name,
        'type'    => $this->type,
      ));
    }
    
    public function unserialize($data)
    {
      $data          = unserialize($data);
      $this->comment = $data['comment'];
      $this->line    = $data['line'];
      $this->lineEnd = $data['lineEnd'];
      $this->name    = $data['name'];
      $this->type    = $data['type'];
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

	public function GetComment()
	{
		return $this->comment;
	}

	public function SetComment($comment)
	{
		$this->comment = $comment;
	}
}
