<?php
/**
 * Doctrine Entity Manager service provider
 *
 * @copyright  Copyright (C) 2014 joomContrib Team. All rights reserved.
 * @license    GNU Lesser General Public License version 2 or later; see LICENSE.txt
 */

namespace joomContrib\Providers;

use Joomla\DI\Container,
	Joomla\DI\ServiceProviderInterface;

use Doctrine\ORM\Tools\Setup,
	Doctrine\ORM\EntityManager,

	Doctrine\Common\EventManager;

// Use complex annotation reader
use Doctrine\ORM\Mapping\Driver\AnnotationDriver,
	Doctrine\Common\Annotations\AnnotationReader,
	Doctrine\Common\Annotations\AnnotationRegistry;

/**
 * Registers the Doctrine Entity Manager service provider
 *
 * @since  1.0
 */
class DoctrineServiceProvider implements ServiceProviderInterface
{
	/**
	 * Tables to exclude
	 *
	 * @var    array
	 *
	 * @since  1.0
	 */
	protected $excludes = array();

	/**
	 * Paths to entities
	 *
	 * @var    array|string
	 *
	 * @since  1.0
	 */
	protected $paths;

	/**
	 * Metadata type
	 *
	 * @var    string
	 *
	 * @since  1.0
	 */
	protected $metadataType = 'annotation';

	/**
	 * Constructor.
	 *
	 * @param  string|array  $paths         Paths to lookup for entities or glob pattern
	 * @param  string        $metadataType  Format (annotation|yaml|xml)
	 * @param  array         $excludes      Tables to exclude
	 *
	 * @since  1.0
	 */
	public function __construct($metadataType = 'annotation', $paths = null, array $excludes = array('session'))
	{
		$this->paths = $paths ?: 'src/Component/*/Entity';
		$this->metadataType = $metadataType;
		$this->excludes = $excludes;
	}

	/**
	 * Get default entity paths
	 *
	 * @param   string  $lookupPattern
	 * @param   string  $app_root
	 *
	 * @return  array
	 *
	 * @since  1.0
	 */
	protected function findEntityPaths($app_root, $lookupPattern = '')
	{
		// Get paths to entities within components
		$paths = glob($app_root . '/' . $lookupPattern);


		// Build entity namespace by location where config files are stored.
		// path => className, see http://docs.doctrine-project.org/en/2.1/reference/yaml-mapping.html#simplified-yaml-driver
		if ($this->metadataType == 'simplifiedYaml')
		{
			$appRootLength = strlen($app_root . '/src/');

			$paths = array_flip($paths);
			$entitiesSubfolder = 'Entity';


			foreach ($paths as $location => &$fqcn)
			{
				// v1
				$fqcn = preg_replace(
					array('~^' . preg_quote($app_root) . '\/src\/((?:.[^\/]*\/){2}).*~', '~\/~'),
					array('${1}' . $entitiesSubfolder, '\\'), 
					$location
				);
			/*
				// V2
				$relativeLocation = str_replace('/', '\\', substr($location, $appRootLength));

				$fqcnArray = explode('\\', $relativeLocation, 3);
				$fqcnArray[2] = 'Entity';

				$fqcn = implode('\\', $fqcnArray);
			*/
			}
		}


		return $paths;
	}

	/**
	 * Registers the service provider withing a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function register(Container $container)
	{
		$app_root = ($container->exists('app_root')) ? $container->get('app_root') : '';

		// Prepare variables to pass
		$paths = (is_array($this->paths)) ? $this->paths : $this->findEntityPaths($app_root, $this->paths);

		$metadataType = $this->metadataType;
		$excludes = $this->excludes;

		// Share object
		$container->share(
			'Doctrine\\ORM\\EntityManager',
			function (Container $c) use ($paths, $metadataType, $excludes)
			{
				$evm = null;

				// Get app config
				$appConfig = $c->get('config');

				// Create a simple "default" Doctrine ORM configuration for Annotations
				$isDevMode = $appConfig->get('debug', false);

				$proxyDir = null;
				$cache = null;

				// Create doctrine configuration from entities
				// See Doctrine\orm\libr\Doctrine\ORM\Mapping\Driver
				switch($metadataType)
				{
					case 'yml':
					case 'yaml':
						$config = Setup::createYAMLMetadataConfiguration($paths, $isDevMode);
						break;

					case 'xml':
						$config = Setup::createXMLMetadataConfiguration($paths, $isDevMode);
						break;

					case null:
					case 'annotation':
						$config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode);
						break;

					// Non-standard drivers
					default:
					case 'simplifiedYaml':
						$config = Setup::createConfiguration($isDevMode, $proxyDir, $cache);
						$driver = '\\Doctrine\ORM\Mapping\Driver\\' . ucfirst($metadataType) . 'Driver';
						$config->setMetadataDriverImpl(new $driver($paths));
						break;
				}


				// Database configuration parameters
				$conn = array(
					'driver' 	=> $appConfig->get('database.driver'),
					'host' 		=> $appConfig->get('database.host'),
					'dbname' 	=> $appConfig->get('database.name'),
					'user' 		=> $appConfig->get('database.user'),
					'password' 	=> $appConfig->get('database.password'),
				);


				// Use prefix if avail before initializing EntityManager
				$prefix = $appConfig->get('database.prefix');

				if ($prefix)
				{
					$evm = new \Doctrine\Common\EventManager;

					$tablePrefix = new DoctrineExtensions\TablePrefix($prefix);
					$evm->addEventListener(\Doctrine\ORM\Events::loadClassMetadata, $tablePrefix);

					// Process all excludes
					foreach ($excludes as &$exclude)
					{
						// Strip placeholder
						if (substr($exclude, 0, 3) == '#__')
						{
							$exclude = substr($exclude, 3);
						}

						// Add prefix
						$exclude = $prefix . $exclude;
					}
				}


				// To apply excludes use regex in format: ^(?!session|abc$).*
				if (!empty($excludes))
				{
					$regexp = '~^(?!' . implode('|', $excludes) . ').*$~';
					$config->setFilterSchemaAssetsExpression($regexp);
				}

				// Obtain the entity manager
				$entityManager = EntityManager::create($conn, $config, $evm);


				return $entityManager;
			},
			true
		);

		$container->alias('em', 'Doctrine\\ORM\\EntityManager');
	}
}
