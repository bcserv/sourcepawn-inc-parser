<?php

const TEMPLATE_DEBUG = true;

require_once 'pawnparser.class.php';
require_once 'lib/Twig/Autoloader.php';
Twig_Autoloader::register();

$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader, array('debug' => TEMPLATE_DEBUG));
if (TEMPLATE_DEBUG)
    $twig->addExtension(new Twig_Extension_Debug());

function ParseDirectory($dir, $pp, $callbackFunc, $recursive=true)
{
	$handle = opendir($dir);

	if ($handle === false) {
		return;
	}

	while (($file = readdir($handle)) !== false) {
		
		if ($file == '.' || $file == '..') {
			continue;
		}

		$path = $dir . '/' . $file;

		if (is_dir($path)) {
			echo "Dir: $dir/$file <br />";
			if ($recursive) {
				ParseDirectory($dir);
			}
		}
		else {
			echo "File: $path <br />";
			$pp->parseFile($path, $callbackFunc);
		}
	}

	closedir($handle);
}

function comment_to_docstring($comment)
{
    return preg_replace('/^\s*\* ?|^[ ]*/m', '', $comment);
}

class JSPawnFunction
{
    public $name;
    public $arguments;
    public $docstring;
    public $isStatic;
    public $returnType;
    public $body;
    public $types;
    
    public $element;
    
    const TYPE_INVALID  = -1;
    const TYPE_BRIEF    = 0;
    const TYPE_PARAM    = 1;
    const TYPE_NOTE     = 2;
    const TYPE_RETURN   = 3;
    const TYPE_NORETURN = 4;
    const TYPE_ERROR    = 5;
    static $TYPES = array(
        'brief' => JSPawnFunction::TYPE_BRIEF,
        'return' => JSPawnFunction::TYPE_RETURN,
        'noreturn' => JSPawnFunction::TYPE_NORETURN,
        'error' => JSPawnFunction::TYPE_ERROR,
        'notes' => JSPawnFunction::TYPE_NOTE,
        'param' => JSPawnFunction::TYPE_PARAM
    );
    
    function __construct($name, array $arguments, $body, $isStatic, $types, 
                         $returnType, $comment, $element)
    {
        $this->name = $name;
        $this->arguments = $arguments;
        $this->body = $body;
        $this->isStatic = $isStatic;
        $this->returnType = $returnType;
        $this->types = $types;
        $this->element = $element;
        
        $this->setComment($comment);
    }
    
    public function setComment($comment)
    {
        $this->docstring = comment_to_docstring($comment);
        $this->docinfo = JSPawnFunction::parse_docstring($this->docstring);
    }

    static function parse_docstring($docstring)
    {
        $parsed = array(
            'brief' => '',
            'return' => '',
            'error' => '',
            'notes' => array(),
            'params' => array()
        );
        
        $lastType = JSPawnFunction::TYPE_BRIEF;
        $lastText = '';
        $lastName = '';
        
        $lines = preg_split('/\r?\n/', $docstring);
        array_push($lines, null);
        
        foreach ($lines as $line)
        {
            $matches = null;
            if (preg_match('/^@([^\s]+)\s+(.*)$/', $line, $matches) === 0)
                $lastText .= ' ' . trim($line) . "\n";
            
            else
            {
                switch ($lastType)
                {
                    case JSPawnFunction::TYPE_BRIEF:
                        $parsed['brief'] = trim($lastText);
                        break;
                    
                    case JSPawnFunction::TYPE_RETURN:
                        $parsed['return'] = trim($lastText);
                        break;
                    
                    case JSPawnFunction::TYPE_ERROR:
                        $parsed['error'] = trim($lastText);
                        break;
                    
                    case JSPawnFunction::TYPE_NOTE:
                        $parsed['notes'][] = trim($lastText);
                        break;
                    
                    case JSPawnFunction::TYPE_PARAM:
                        $parsed['params'][] = array(
                            'name' => $lastName,
                            'text' => trim($lastText)
                        );
                        break;
                };
                
                $lastText = '';
                $lastName = '';
                
                if (!array_key_exists($matches[1], JSPawnFunction::$TYPES))
                    $lastType = JSPawnFunction::TYPE_INVALID;
                else
                {
                    $lastType = JSPawnFunction::$TYPES[$matches[1]];
                    
                    if ($lastType === JSPawnFunction::TYPE_PARAM && preg_match('/([a-zA-Z_][a-zA-Z0-9_]*)\s+(.*)$/', $matches[2], $paraminfo) !== 0)
                    {
                        $lastName = $paraminfo[1];
                        $lastText = $paraminfo[2];
                    }
                    else if ($lastType === JSPawnFunction::TYPE_NORETURN)
                        $parsed['return'] = null;
                    else if (preg_match('/^\s*(.*)\s*$/', $matches[2], $text))
                        $lastText = $text[1];
                }
            }
        }
        
        return $parsed;
    }
}

class JavaScriptEmitter
{
    public $functions = array();
    public $comments = array();
    
    public function handlePawnElement($pawnElement)
    {
        if ($pawnElement instanceof PawnComment)
        {
            $comment = $pawnElement;
            $this->comments[$comment->GetLineEnd()] = $comment;
        }
        
        elseif ($pawnElement instanceof PawnFunction)
        {
            $func = $pawnElement;
            
            $comment = '';
            $key = $func->GetLineStart() - 1;
            if (array_key_exists($key, $this->comments))
                $comment = $this->comments[$key]->GetText();
            
            $jsfunc = new JSPawnFunction(
                $func->GetName(),
                $func->GetArguments(),
                $func->GetBody(),
                $func->IsStatic(),
                $func->GetTypes(),
                $func->GetReturnType(),
                $comment,
                $func
            );
            array_push($this->functions, $jsfunc);
        }
    }
    
    public function findComments()
    {
        foreach ($this->functions as $jsfunc)
        {
            $key = $jsfunc->element->GetLineStart() - 1;
            if (array_key_exists($key, $this->comments))
                $jsfunc->setComment($this->comments[$key]);
        }
    }
};



$parseDir = "pawn/smlib";

$jse = new JavaScriptEmitter();
$pp = new PawnParser(array($jse, 'handlePawnElement'));

// Finally, parse our pawn include directory
//ParseDirectory($parseDir, $pp, 'Callback_PawnElement');

// Testing with a single file
$pp->parseFile($parseDir . '/clients.inc');

echo $twig->render('index.htm', array('jse' => $jse));
?>