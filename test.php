<?php

require_once "pawnparser.class.php";

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

function Callback_PawnElement($pawnElement)
{
	echo "<pre>" . print_r($pawnElement, true) . "</pre>";
}

$parseDir = "/repositories/sourcemod-central/plugins/include";

$pp = new PawnParser('Callback_PawnElement');

// Finally, parse our pawn include directory
//ParseDirectory($parseDir, $pp, 'Callback_PawnElement');

// Testing with a single file
$pp->parseFile($parseDir . '/timers.inc');
