About
=====================

A parser written in PHP which is able to analyse sourcepawn's .inc files and comments (annotation syntax)

Example
---------

```php
<?php
use Bcserv\SourcepawnIncParser\PawnParser;

// if you are not using PHP's namespace autoloading mechanism you need this also:
// require_once "path_to_library/src/Bcserv/SourcepawnIncParser/PawnParser.php";

function pawnParserCallback($pawnElement)
{
	echo "Wow this is amazing: <pre>"
			. print_r($pawnElement, true)
			. "</pre>";
}

$pawnParser = new PawnParser(pawnParserCallback);
$pawnParser->parseFile('clients.inc');

```

Requirements
---------

* PHP >= 5.3
* A [PHP autoloader](http://php.net/manual/de/language.oop5.autoload.php) to autoload the used namespaces

Coding standards
---------

This library follows the PHP standards [psr-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md) and [psr-1](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md).

Using in Symfony 2
---------
Add this to the require section of your composer.json in the main folder of Symfony2:

```json
"bcserv/sourcepawn-inc-parser": "1.0.x-dev"
```

You maybe also need to change "minimum-stability" to "dev" if it complains about this.

Then run php composer.phar update
