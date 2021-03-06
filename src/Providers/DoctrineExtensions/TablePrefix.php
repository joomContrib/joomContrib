<?php
/**
 * Doctrine Entity Manager service provider
 *
 * @copyright  Copyright (C) 2014 joomContrib Team. All rights reserved.
 * @license    GNU Lesser General Public License version 2 or later; see LICENSE.txt
 */

namespace joomContrib\Providers\DoctrineExtensions;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;

/**
 * loadClassMetadata Listener
 *
 * @see  Doctrine Table prefixes  http://docs.doctrine-project.org/en/2.0.x/cookbook/sql-table-prefixes.html
 */
class TablePrefix implements \Doctrine\Common\EventSubscriber
{
	/**
	 * Table prefix
	 *
	 * @var    string
	 *
	 * @since  1.0
	 */
	protected $prefix = '';

	/**
	 * Set prefix
	 *
	 * @param   $prefix  string
	 *
	 * @return  void
	 *
	 * @since  1.0
	 */
	public function __construct($prefix)
	{
		$this->prefix = (string) $prefix;
	}

	public function getSubscribedEvents()
    {
        return array('loadClassMetadata');
    }

	/**
	 * Constructor.
	 *
	 * @param   LoadClassMetadataEventArgs  $eventArgs
	 *
	 * @return  void
	 *
	 * @since  1.0
	 */
	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
	{
		$classMetadata = $eventArgs->getClassMetadata();

		// Do not re-apply the prefix in an inheritance hierarchy.
		if ($classMetadata->isInheritanceTypeSingleTable() && !$classMetadata->isRootEntity())
		{
			return;
        }

		$classMetadata->setTableName($this->prefix . $classMetadata->getTableName());

		foreach ($classMetadata->getAssociationMappings() as $fieldName => $mapping)
		{
			if ($mapping['type'] == \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_MANY)
			{
				$mappedTableName = $classMetadata->associationMappings[$fieldName]['joinTable']['name'];
				$classMetadata->associationMappings[$fieldName]['joinTable']['name'] = $this->prefix . $mappedTableName;
			}
		}

		return;
	}
}
