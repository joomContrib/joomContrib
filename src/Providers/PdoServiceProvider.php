<?php
/**
 * @name       PdoServiceProvider
 * @package    joomContrib\Providers
 * @copyright  Copyright (C) 2014 joomContrib Team (https://github.com/orgs/joomContrib). All rights reserved.
 * @license    GNU Lesser General Public License version 2 or later; see https://www.gnu.org/licenses/lgpl.html
 */

namespace joomContrib\Providers;

use PDO;
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
 *
 * @note    To use table prefix: http://stackoverflow.com/questions/1472250/pdo-working-with-table-prefixes
 */
class PdoServiceProvider implements ServiceProviderInterface
{
	/**
	 * Registers the service provider within a DI container.
	 *
	 * @param   object   $container   Joomla\DI\Container
	 *
	 * @return  void
	 * @since   1.0
	 *
	 * @thorws  \LogicException  Driver not available
	 * @thorws  \PDOException    Connection failed
	 */
	public function register(Container $container)
	{
		// Set a container for the db (shared, protected).
		$container->set('PDO', function (Container $c)
		{
			// Get config from container.
			$config  = $c->get('config');

			// Create options from a database key, and overwrite with PDO values
			// Note: empty values are not overwritten, see  https://github.com/joomla-framework/registry/pull/2/files
			$options = array_merge(array('driver_options' => array()), (array) $config->get('database'), (array) $config->get('pdo'));

			// Make sure requested PDO driver exists
			if (!in_array($options['driver'], PDO::getAvailableDrivers()))
			{
				throw new \LogicException(sprintf('PDO driver %s is not availble', $options['driver']));
			}

			// Data source name
			$dsn = $options['driver'] . ':host=' . $options['host']. ';dbname=' . $options['database'];


			// Create new PDO object
			if (isset($options['prefix']))
			{
				$pdo = new PdoExtensions\TablePrefixPdo($dsn, $options['user'], $options['password'], $options['driver_options'], $options['prefix']);
			}
			else
			{
				$pdo = new PDO($dsn, $options['user'], $options['password'], $options['driver_options']);
			}

			// Set error mode attribute to throw Exceptions
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			return $pdo;
		},
		true, true);

		$container->alias('pdo', 'PDO');
	}
}
