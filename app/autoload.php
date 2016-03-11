<?php
use Doctrine\Common\Annotations\AnnotationRegistry;
use Composer\Autoload\ClassLoader;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;

// The root of the app
define('TARTANA_PATH_ROOT', dirname(__DIR__));

/**
 *
 * @var ClassLoader $loader
 */
$loader = require __DIR__ . '/../vendor/autoload.php';

if (! file_exists(TARTANA_PATH_ROOT . '/app/config/parameters.yml'))
{
	copy(TARTANA_PATH_ROOT . '/app/config/parameters.dist.yml', TARTANA_PATH_ROOT . '/app/config/parameters.yml');

	// Regenerate the secret
	$fs = new Local(TARTANA_PATH_ROOT . '/app/config');
	$content = $fs->read('parameters.yml')['contents'];
	$content = str_replace('secret: 457152e95295f63116eb776d43ac3d0c41e58905', 'secret: ' . hash('sha1', uniqid(mt_rand(), true)), $content);
	$fs->write('parameters.yml', $content, new Config());
}

AnnotationRegistry::registerLoader([
		$loader,
		'loadClass'
]);

return $loader;
