<?php
/**
 * @name       SimpleMonologServiceProvider
 * @package    joomContrib\Providers
 * @copyright  Copyright (C) 2014 joomContrib Team (https://github.com/orgs/joomContrib). All rights reserved.
 * @license    GNU Lesser General Public License version 2 or later; see https://www.gnu.org/licenses/lgpl.html
 */

namespace joomContrib\Providers;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

use Monolog\Logger;


/**
 * Creates a logger for the application.
 *
 * @since   1.0
 */
class SimpleMonologServiceProvider implements ServiceProviderInterface
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
		$container->set('logger', function(Container $c)
		{
			$config = $c->get('config');
	
			return new Logger($config->get('site.name_alias', 'syslog'));
		},
		true, true);
	}
}
