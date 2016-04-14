#!/usr/bin/env php
<?php
use Tartana\Component\Command\Command;
use Tartana\Component\Command\Runner;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Debug\Debug;

set_time_limit(0);

/**
 *
 * @var Composer\Autoload\ClassLoader $loader
 */
$loader = require __DIR__ . '/../app/autoload.php';

$input = new ArgvInput();
$env = $input->getParameterOption([
		'--env',
		'-e'
], getenv('SYMFONY_ENV') ?: 'prod');
$debug = getenv('SYMFONY_DEBUG') !== '0' && ! $input->hasParameterOption([
		'--no-debug',
		''
]) && $env !== 'prod';

if (! file_exists(TARTANA_PATH_ROOT . '/var/data.db') && ! $input->hasParameterOption('doctrine:migrations:migrate'))
{
	$command = Command::getAppCommand('doctrine:migrations:migrate');
	$command->setCaptureErrorInOutput(true);
	$command->setOutputFile('/dev/null');
	$command->addArgument('--no-interaction');
	$runner = new Runner($env);
	$runner->execute($command);
}

$kernel = new TartanaKernel($env, $debug);
$application = new Application($kernel);
$application->setName('Tartana');
$application->setVersion(file_get_contents(__DIR__ . '/../app/config/internal/version.txt'));
$application->setDefaultCommand('default');
$application->run($input);
