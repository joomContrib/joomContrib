<?php
/**
 * @name       DatabaseServiceProvider
 * @package    joomContrib\Providers
 * @copyright  Copyright (C) 2014 joomContrib Team (https://github.com/orgs/joomContrib). All rights reserved.
 * @license    GNU Lesser General Public License version 2 or later; see https://www.gnu.org/licenses/lgpl.html
 */

namespace joomContrib\Providers;

use Joomla\Database\DatabaseFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * Creates a database object for the application.
 * 
 * Config options:
 * "database":{
 * 		"host": "localhost",
 * 		"driver": "mysqli",
 * 		"database": "db name",
 * 		"prefix": "pref_",
 * 		"user": "username",
 * 		"password": "123456789",
 * 		"debug": false
 * 	}
 *
 * @since   1.0
 */
class DatabaseServiceProvider implements ServiceProviderInterface
{
	/**
	 * Registers the service provider within a DI container.
	 *
	 * @param   object   $container   Joomla\DI\Container
	 *
	 * @return  void
	 * @since   1.0
	 */
	public function register(Container $container)
	{
		// Set a container for the db (shared, protected).
		$container->set('db', function (Container $c)
		{
			// Get config from container.
			$config  = $c->get('config');
			// Get logger.
			$logger  = $c->get('logger');
			// Set options for database.
			$options = (array) $config->get('database');
			// Set debug.
			$debug   = $config->get('dbo.debug', false);
			
			// Create database factory and get driver.
			$factory = new DatabaseFactory;
			$db      = $factory->getDriver($options['driver'], $options);
			
			// Set logger, debug and select db.
			$db->setLogger($logger);
			$db->setDebug($debug);
			$db->select($options['database']);

			// Push a msg to the log.
			$logger->debug('Database Container created.');
			
			return $db;
		},
		true, true);
	}
}
