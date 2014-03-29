<?php
/**
 * @name       LoggerAwareTrait
 * @package    joomContrib\Traits
 * @copyright  Copyright (C) 2014 joomContrib Team (https://github.com/orgs/joomContrib). All rights reserved.
 * @license    GNU Lesser General Public License version 2.1 or later; see https://www.gnu.org/licenses/lgpl.html
 */

namespace joomContrib\Traits;


use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;


trait LoggerAwareTrait
{
	/**
	 * Logger
	 * 
	 * @var     LoggerInterface
	 * @since   1.0
	 */
	private $logger;
	
	
	/**
	 * Get the logger.
	 *
	 * @return  LoggerInterface
	 * @since   1.0
	 */
	public function getLogger()
	{
		// If a logger hasn't been set, use NullLogger
		if (!($this->logger instanceof LoggerInterface))
		{
			$this->logger = new NullLogger;
		}
	
		return $this->logger;
	}
	
	/**
	 * Set the logger.
	 *
	 * @param   LoggerInterface  $logger  The logger.
	 *
	 * @return  $this
	 * @since   1.0
	 */
	public function setLogger(LoggerInterface $logger)
	{
		$this->logger = $logger;
	
		return $this;
	}
}
