<?php
namespace Bcserv\SourcepawnIncParser\PawnElement;

use Bcserv\SourcepawnIncParser\PawnElement;

class PawnComment extends PawnElement
{
    const PAWNCOMMENT_TYPE_SINGLELINE = 0;
    const PAWNCOMMENT_TYPE_MULTILINE  = 1;

    protected $tags = array();
    protected $text;
    protected $raw;

    public function serialize()
    {
      return serialize(array(
        'tags'   => $this->tags,
        'text'   => $this->text,
        'raw'    => $this->raw,
        'parent' => parent::serialize(),
      ));
    }

    public function unserialize($data)
    {
      $data       = unserialize($data);
      $this->tags = $data['tags'];
      $this->text = $data['text'];
      $this->raw  = $data['raw'];
      
      parent::unserialize($data['parent']);
    }

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
            $this->type = self::PAWNCOMMENT_TYPE_SINGLELINE;
        }
        else {
            $this->type = self::PAWNCOMMENT_TYPE_MULTILINE;
        }
        
        $lastChar = "";

        while (($char = $this->_ReadChar($pp)) !== false) {
            
            if ($this->type == self::PAWNCOMMENT_TYPE_SINGLELINE && $char == "\n") {
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
        
        // Parse tags for multi-line comments
        if ($this->type == self::PAWNCOMMENT_TYPE_MULTILINE) {
            $this->ParseTags();
        }
    }

    protected function ParseTags()
    {
        $this->text = trim(preg_replace('/^\s*\**( |\t)?/m', '', $this->text));
        if (!preg_match('/^\s*@\w+/m', $this->text, $matches, PREG_OFFSET_CAPTURE))
            return;
        
        $meta = substr($this->text, $matches[0][1]);
        $this->text = trim(substr($this->text, 0, $matches[0][1]));
        
        $tags = preg_split('/^\s*@/m', $meta, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($tags as $tag) {
            $segs = preg_split('/\s+/', trim($tag), 2);
            $tagName = $segs[0];
            $param = isset($segs[1]) ? trim($segs[1]) : '';
            
            // Additional parsing for "param" tag
            if ($tagName == 'param') {
                if (!isset($this->tags[$tagName])) {
                    $this->tags[$tagName] = array();
                }
                
                $segs = preg_split('/\s+/', $param, 2);
                $this->tags[$tagName][$segs[0]] = $segs[1];
            }
            else if (isset($this->tags[$tagName])) {
                if (!is_array($this->tags[$tagName])) {
                    $this->tags[$tagName] = (array)$this->tags[$tagName];
                }
                
                $this->tags[$tagName][] = $param;
            }
            else {
                $this->tags[$tagName] = $param;
            }
        }
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

    public function GetTags()
    {
        return $this->tags;
    }
}
