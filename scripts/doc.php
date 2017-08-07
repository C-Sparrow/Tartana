<?php

// Setting some default values
set_time_limit(0);

// Some global variables
$rootDirectory = dirname(__DIR__);

$host = '127.0.0.1';

if ($argc > 1) {
	$host = $argv[1];
}

exec($rootDirectory . '/vendor/bin/couscous preview ' . $host . ':8000 --livereload  &> /dev/null &');

$docDir = dirname($rootDirectory) . '/Tartanaapp';
if (file_exists($docDir)) {
	while (true) {
		exec($rootDirectory . '/vendor/bin/couscous generate --target=' . $docDir);
		sleep(3);
	}
}
