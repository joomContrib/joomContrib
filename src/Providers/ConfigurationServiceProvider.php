<?php
/**
 * @name       ConfigurationServiceProvider
 * @package    joomContrib\Providers
 * @copyright  Copyright (C) 2014 joomContrib Team (https://github.com/orgs/joomContrib). All rights reserved.
 * @license    GNU Lesser General Public License version 2 or later; see https://www.gnu.org/licenses/lgpl.html
 */

namespace joomContrib\Providers;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

use Joomla\Registry\Registry;

/**
 * Registers the Configuration service provider.
 *
 * @since  1.0
 */
class ConfigurationServiceProvider implements ServiceProviderInterface
{
	/**
	 * Configuration
	 *
	 * @var    Registry
	 * @since  1.0
	 */
	private $config;

	/**
	 * @var    string
	 * @since  1.0
	 */
	private $path;

	/**
	 * @var	string
	 * @since  1.1
	 */
	private $env;

	/**
	 * Constructor.
	 *
	 * @param   string    $path    The full path and file name for the configuration file.
	 * @param   Registry  $config  The config object to merge with.
	 * @param   mixed     $env     Environment suffix. Null for autodetect, false for none, string for custom
	 *
	 * @since   1.0
	 */
	public function __construct($path, Registry $config = null, $env = false)
	{
		$this->path = $path;
		$this->config = $config ?: new Registry;
		$this->env = (is_null($env)) ? getenv('JFW_ENV') : $env;
	}

	/**
	 * Gets a configuration object.
	 *
	 *
	 * @return  Registry
	 *
	 * @since   1.1
	 * @throws  \LogicException if the configuration file does not exist.
	 * @throws  \UnexpectedValueException if the configuration file could not be parsed.
	 */
	public function getConfiguration()
	{
		$dConfig = $this->config;
		$path = $this->path;

		// Environemt specified
		if ($this->env)
		{
			// Add environment as suffix
			$pathInfo = pathinfo($path);
			$tmpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.' . strtolower($this->env) . '.' . $pathInfo['extension'];

			if (is_readable($tmpPath))
			{
				$path = $tmpPath;
			}
		}

		// Check if config file exists
		if (!is_readable($path))
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

			// XML, INI or other format
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
		// PHP 5.3 compatibility
		$that = $this;

		$container->share(
			'configuration',
			function (Container $c) use ($that)
			{
				return $that->getConfiguration();
			},
			true
		);

		$container->alias('config', 'configuration');

		return;
	}
}
