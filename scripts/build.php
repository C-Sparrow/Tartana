<?php

// Setting some default values
set_time_limit(0);

// Some global variables
$rootDirectory = dirname(__DIR__);
$buildDirectory = $rootDirectory . '/build/source';
$composerFilePath = $rootDirectory . '/build/composer.phar';

if (is_dir($buildDirectory))
{
	rmdirr($buildDirectory);
}
mkdir($buildDirectory, 0777, true);
chdir($buildDirectory);

echo 'Copy folders to build directory.' . PHP_EOL;
echo 'Copy folder: app.' . PHP_EOL;
recurseCopy($rootDirectory . '/app', $buildDirectory . '/app');
echo 'Copy folder: cli.' . PHP_EOL;
recurseCopy($rootDirectory . '/cli', $buildDirectory . '/cli');
echo 'Copy folder: src.' . PHP_EOL;
recurseCopy($rootDirectory . '/src', $buildDirectory . '/src');
echo 'Copy folder: var.' . PHP_EOL;
recurseCopy($rootDirectory . '/var', $buildDirectory . '/var', 1, false);
recurseCopy($rootDirectory . '/var/data', $buildDirectory . '/var/data', 1);
touch($buildDirectory . '/var/logs/.gitkeep');
touch($buildDirectory . '/var/sessions/.gitkeep');
touch($buildDirectory . '/var/tmp/.gitkeep');
echo 'Copy folder: web.' . PHP_EOL;
recurseCopy($rootDirectory . '/web', $buildDirectory . '/web');
rmdirr($buildDirectory . '/web/assets');
copy($rootDirectory . '/bower.json', $buildDirectory . '/bower.json');
copy($rootDirectory . '/composer.json', $buildDirectory . '/composer.json');
copy($rootDirectory . '/composer.lock', $buildDirectory . '/composer.lock');
copy($rootDirectory . '/LICENSE', $buildDirectory . '/LICENSE');
copy($rootDirectory . '/README.md', $buildDirectory . '/README.md');

echo 'Downloading composer' . PHP_EOL;
$fp = fopen($composerFilePath, 'w+');
$ch = curl_init('https://getcomposer.org/composer.phar');
curl_setopt($ch, CURLOPT_TIMEOUT, 50);
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);
curl_close($ch);
fclose($fp);

if (! file_exists($composerFilePath))
{
	echo 'Could not download composer!!';
	die();
}

echo 'Running installer file.' . PHP_EOL;
$output = shell_exec('php ' . $composerFilePath . ' install --no-dev -d ' . $buildDirectory . ' > /dev/null 2>&1');

unlink($composerFilePath);
unlink($buildDirectory . '/composer.json');
unlink($buildDirectory . '/composer.lock');
if (file_exists($buildDirectory . '/app/config/hosters.yml'))
{
	unlink($buildDirectory . '/app/config/hosters.yml');
}
if (file_exists($buildDirectory . '/app/config/parameters.yml'))
{
	unlink($buildDirectory . '/app/config/parameters.yml');
}
if (file_exists($buildDirectory . '/app/config/test_synology.yml'))
{
	unlink($buildDirectory . '/app/config/test_synology.yml');
}

echo 'Updating javascript libraries.' . PHP_EOL;
shell_exec('bower-installer -r -p > /dev/null 2>&1');
unlink($buildDirectory . '/bower.json');

echo 'Creating dist file.' . PHP_EOL;
rmdirr($rootDirectory . '/dist/');
mkdir($rootDirectory . '/dist');

// Initialize archive object
$zip = new ZipArchive();
$zip->open($rootDirectory . '/dist/tartana.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);

// Create recursive directory iterator
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($buildDirectory), RecursiveIteratorIterator::LEAVES_ONLY);

$filters = [
		'.',
		'web/app_dev.php'
];

foreach ($files as $name => $file)
{
	// Skip directories (they would be added automatically)
	if (! $file->isDir())
	{
		// Get real and relative path for current file
		$filePath = $file->getRealPath();
		$relativePath = substr($filePath, strlen($buildDirectory) + 1);

		$add = true;
		foreach ($filters as $filter)
		{
			if (startsWith($relativePath, $filter))
			{
				$add = false;
				break;
			}
		}
		if (! $add)
		{
			continue;
		}

		// Add current file to archive
		$zip->addFile($filePath, $relativePath);
	}
}

// Zip archive will be created only after closing object
$zip->close();

echo 'Cleaning up build directory.' . PHP_EOL;
rmdirr($buildDirectory);

function recurseCopy ($src, $dst, $depth = null, $createFiles = true)
{
	$dir = opendir($src);
	if (! file_exists($dst))
	{
		mkdir($dst);
	}
	while (false !== ($file = readdir($dir)))
	{
		if (($file != '.') && ($file != '..'))
		{
			if (is_dir($src . '/' . $file) && ($depth === null | $depth > 0))
			{
				recurseCopy($src . '/' . $file, $dst . '/' . $file, $depth === null ? $depth : $depth - 1, $createFiles);
			}
			else if (! is_dir($src . '/' . $file) && $createFiles)
			{
				copy($src . '/' . $file, $dst . '/' . $file);
			}
		}
	}
	closedir($dir);
}

function startsWith ($haystack, $needle)
{
	// search backwards starting from haystack length characters from the end
	return $needle === "" || strrpos($haystack, $needle, - strlen($haystack)) !== FALSE;
}

function rmdirr ($dirname)
{
	// Sanity check
	if (! file_exists($dirname))
	{
		return false;
	}

	// Simple delete for a file
	if (is_file($dirname) || is_link($dirname))
	{
		return unlink($dirname);
	}

	// Loop through the folder
	$dir = dir($dirname);
	while (false !== $entry = $dir->read())
	{
		// Skip pointers
		if ($entry == '.' || $entry == '..')
		{
			continue;
		}

		// Recurse
		rmdirr($dirname . DIRECTORY_SEPARATOR . $entry);
	}

	// Clean up
	$dir->close();
	return rmdir($dirname);
}