<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;

/**
 *
 * @var Composer\Autoload\ClassLoader $loader
 */
$loader = require __DIR__ . '/../app/autoload.php';
Debug::enable();

$kernel = new TartanaKernel('dev', true);
$kernel->loadClassCache();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
