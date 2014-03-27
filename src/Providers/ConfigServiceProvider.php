<?php
/**
 * Configuration service provider
 *
 * @copyright  Copyright (C) 2014 joomContrib Team. All rights reserved.
 * @license    GNU Lesser General Public License version 2 or later; see LICENSE.txt
 */

namespace joomContrib\Providers;

use Joomla\DI\Container,
	Joomla\DI\ServiceProviderInterface,

	Joomla\Registry\Registry;

/**
 * Registers the Configuration service provider.
 *
 * @since  1.0
 */
class ConfigServiceProvider implements ServiceProviderInterface
{
	/**
	 * Configuration
	 *
	 * @var    Registry
	 * @since  1.0
	 */
	private $config;

	/**
	 * @var    String
	 * @since  1.0
	 */
	private $path;

	/**
	 * Constructor.
	 *
	 * @param   string    $path    The full path and file name for the configuration file.
	 * @param   Registry  $config  The config object to merge with.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function __construct($path, Registry $config = null)
	{
		$this->path = $path;
		$this->config = $config ?: new Registry;
	}

	/**
	 * Registers the service provider within a DI container.
	 *
	 * @param   Container $container The DI container.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \LogicException if the PULSE_CONFIG constant is not defined.
	 */
	public function register(Container $container)
	{
		$dConfig = $this->config;
		$path = $this->path;

		$container->share(
			'config', 
			function (Container $c) use ($path, $dConfig)
			{
				// Check if config file exists
				if (!file_exists($path))
				{
					throw new \LogicException('Configuration file does not exist.', 500);
				}

				// Get config type by file extension
				$configType = pathinfo($path, PATHINFO_EXTENSION);

				// Load up configuration file by type
				switch ($configType)
				{
					// PHP array
					case 'php':
						$data = include $path;
						break;

					// JSON file
					case 'json':
						$data = json_decode(file_get_contents($path), true);
						break;

					// YAML file
					case 'yml':
						$data = new Registry;
						$data->loadFile($path, 'yaml');
						break;

					// XML or INI
					default:
						$data = new Registry;
						$data->loadFile($path, $configType);
						break;
				}

				// It's a Registry
				if ($data instanceof Registry)
				{
					$dConfig->merge($data, true);
				}
				// It's an array
				else if (is_array($data))
				{
					$dConfig->loadArray($data);
				}
				// It's an object
				else if (is_object($data))
				{
					$dConfig->loadObject($data);
				}
				// Cannot parse file
				else
				{
					$parseError = 0;

					if ($configType === 'json')
					{
						$parseError = json_last_error();
					}

					throw new \UnexpectedValueException(sprintf('Configuration file could not be parsed (%s).', $parseError), 500);
				}


				return $dConfig;
			},
			true
		);

		return;
	}
}
