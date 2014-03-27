<?php
/**
 * @name       DoctrineServiceProvider
 * @package    joomContrib\Providers
 * @copyright  Copyright (C) 2014 joomContrib Team (https://github.com/orgs/joomContrib). All rights reserved.
 * @license    GNU Lesser General Public License version 2 or later; see https://www.gnu.org/licenses/lgpl.html
 */

namespace joomContrib\Providers;

use Joomla\DI\Container,
	Joomla\DI\ServiceProviderInterface;

use Doctrine\ORM\Tools\Setup,
	Doctrine\ORM\EntityManager,

	Doctrine\Common\EventManager;

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
	 * @var    array
	 *
	 * @since  1.0
	 */
	protected $paths = array();

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
	 * @param  array   $paths          Paths to lookup for entities
	 * @param  string  $metadataType   Format (annotation|yaml|xml)
	 * @param  array   $excludes       Tables to exclude
	 *
	 * @since  1.0
	 */
	public function __construct(array $paths = null, $metadataType = 'annotation', array $excludes = array('session'))
	{
		$this->excludes = $excludes;
		$this->paths = $paths;
		$this->metadataType = $metadataType;
	}

	/**
	 * Get default entity paths
	 *
	 * @param   string  $app_root
	 *
	 * @return  array
	 *
	 * @since  1.0
	 */
	protected function getEntityPaths($app_root = null)
	{
		// Get path to components
		$componentsPath = $app_root . '/src/Component';

		// Get paths to entities withing components
		$paths = glob($componentsPath . '/*/Entity');


		return $paths;
	}

	/**
	 * Registers the service provider withing a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since  1.0
	 *
	 * @see  Doctrine Table prefixes  http://docs.doctrine-project.org/en/2.0.x/cookbook/sql-table-prefixes.html
	 * @see  http://stackoverflow.com/questions/7504073/how-to-setup-table-prefix-in-symfony2
	 * @see  https://github.com/doctrine/doctrine2/blob/master/lib/Doctrine/ORM/EntityManager.php
	 */
	public function register(Container $container)
	{
		$app_root = $container->exists('app_root') ? $container->get('app_root') : null;

		// Prepare variables to pass
		$paths = $this->paths ?: $this->getEntityPaths($app_root);
		$metadataType = $this->metadataType;
		$excludes = $this->excludes;

		// Share object
		$container->share(
			'Doctrine\\ORM\\EntityManager',
			function (Container $c) use ($container, $paths, $metadataType, $excludes)
			{
				$evm = null;

				// Get app config
				$appConfig = $container->get('config');

				// Create a simple "default" Doctrine ORM configuration for Annotations
				$isDevMode = $appConfig->get('debug', true);

				// Create doctrine configuration from entities
				switch($metadataType)
				{
					case 'yaml':
						$config = Setup::createYAMLMetadataConfiguration($paths, $isDevMode);
						break;

					case 'xml':
						$config = Setup::createXMLMetadataConfiguration($paths, $isDevMode);
						break;

					default:
					case 'annotation':
						$config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode);
						break;
				}


				// Database configuration parameters
				$conn = array(
					'driver' => $appConfig->get('database.driver'),
					'host' => $appConfig->get('database.host'),
					'dbname' => $appConfig->get('database.name'),
					'user' => $appConfig->get('database.user'),
					'password' => $appConfig->get('database.password'),
				);


				// Use prefix if avail before initializing EntityManager
				$prefix = $appConfig->get('database.prefix');

				if ($prefix)
				{
					$evm = new \Doctrine\Common\EventManager;

					$tablePrefix = new Doctrine\TablePrefix($prefix);
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
						// TODO: not sure if Doctrine takes care of this
					//	$exclude = $prefix . $exclude;
					}
				}


				// Apply excludes (session tables)
				// Use regex: ^(?!session|abc$).*
				if (!empty($excludes))
				{
					$regexp = '~^(?!' . implode('|', $excludes) . ').*$~';
					$config->setFilterSchemaAssetsExpression($regexp);
				}


				// Obtaining the entity manager
				$entityManager = EntityManager::create($conn, $config, $evm);


				return $entityManager;
			},
			true
		);

		$container->alias('em', 'Doctrine\\ORM\\EntityManager');
	}
}
