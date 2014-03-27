<?php
/**
 * Doctrine console configuration
 */
use Doctrine\ORM\Tools\Console\ConsoleRunner;

use joomContrib\Providers\ConfigServiceProvider,
	joomContrib\Providers\DoctrineServiceProvider;


// Get application root, if we are in /bin/config
$app_root = realpath(__DIR__ . '/../..');


// Bootstrap
require_once $app_root . '/vendor/autoload.php';

// Add critical container entries
$container = (new \Joomla\DI\Container)
	->share('app_root', $app_root, false)
	->registerServiceProvider(new ConfigServiceProvider($app_root . '/app/config/config.yml'))
	->registerServiceProvider(new DoctrineServiceProvider);


// Mechanism to retrieve EntityManager
$entityManager = $container->get('EntityManager');


return ConsoleRunner::createHelperSet($entityManager);
