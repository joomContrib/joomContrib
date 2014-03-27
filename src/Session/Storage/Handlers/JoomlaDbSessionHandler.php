<?php
/**
 * @name       JoomlaDbSessionHandler
 * @package    joomContrib\Session\Storage\Handlers
 * @copyright  Copyright (C) 2014 joomContrib Team (https://github.com/orgs/joomContrib). All rights reserved.
 * @license    GNU Lesser General Public License version 2 or later; see https://www.gnu.org/licenses/lgpl.html
 */
namespace joomContrib\Session\Storage\Handlers;

use Joomla\Database\DatabaseDriver;

/**
 * Session handler for Joomla\Database\DatabaseDriver.
 *
 * This class is inspired from and draws heavily in code and concept from
 * Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler
 * @contributor  Fabien Potencier  <fabien@symfony.com>
 * @contributor  Michael Williams  <michael.williams@funsational.com>
 */
class JoomlaDbSessionHandler implements \SessionHandlerInterface
{
	/**
	 * @var  object  $jdb   Joomla\Database\DatabaseDriver
	 */
	private $jdb;
	
	/**
	 * @var  array  $dbOptions   Database options.
	 */
	private $dbOptions;
	
	
	/**
	 * Constructor
	 *
	 * List of available options:
	 *   * db_table:    The name of the table.                      [default: #__session]
	 *   * db_id_col:   The column where to store the session id.   [default: sess_id]
	 *   * db_data_col: The column where to store the session data. [default: sess_data]
	 *   * db_time_col: The column where to store the timestamp.    [default: sess_time]
	 *
	 * @param  object  $jdb        A Joomla\Database\DatabaseDriver instance.
	 * @param  array   $dbOptions  An associative array of DB options.
	 */
	public function __construct(DatabaseDriver $jdb, array $dbOptions = array())
	{
		// Set default db table if not set.
		if (!array_key_exists('db_table', $dbOptions)) {
			$dbOptions['db_table'] = '#__session';
		}
		
		$colNames        = array('db_id_col' => 'sess_id', 'db_data_col' => 'sess_data', 'db_time_col' => 'sess_time');
		$this->jdb       = $jdb;
		$this->dbOptions = array_merge($colNames, $dbOptions);
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function open($path, $name)
	{
		return true;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function close()
	{
		return true;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function destroy($id)
	{
		// Set db/table/column/query.
		$db      = $this->jdb;
		$dbTable = $this->dbOptions['db_table'];
		$dbIdCol = $this->dbOptions['db_id_col'];
		
		try
		{
			$db->setQuery('DELETE FROM '. $db->qn($dbTable) .' WHERE '. $db->qn($dbIdCol) .' = '. $db->q($id))
				->execute();
		}
		catch (\Exception $e)
		{
			throw new \RuntimeException(sprintf('Exception was thrown when trying to manipulate session data: %s', $e->getMessage()), 0, $e);
		}
	
		return true;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function gc($lifetime)
	{
		// Set db/table/column/time/query.
		$db        = $this->jdb;
		$dbTable   = $this->dbOptions['db_table'];
		$dbTimeCol = $this->dbOptions['db_time_col'];
		$time      = (int) time() - $lifetime;
	
		try
		{
			$db->setQuery('DELETE FROM '. $db->qn($dbTable) .' WHERE '. $db->qn($dbTimeCol) .' < '. $time)
				->execute();
		}
		catch (\Exception $e)
		{
			throw new \RuntimeException(sprintf('Exception was thrown when trying to manipulate session data: %s', $e->getMessage()), 0, $e);
		}
	
		return true;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function read($id)
	{
		$db        = $this->jdb;
		$dbTable   = $this->dbOptions['db_table'];
		$dbDataCol = $this->dbOptions['db_data_col'];
		$dbIdCol   = $this->dbOptions['db_id_col'];
	
		try
		{	
			$db->setQuery('SELECT '. $db->qn($dbDataCol) .' FROM '. $db->qn($dbTable) .' WHERE '. $db->qn($dbIdCol) .' = '. $db->q($id));
			
			if ($row = $db->loadResult())
			{
				return base64_decode($row);
			}
	
			// Session does not exist, create it.
			$this->createNewSession($id);
	
			return '';
		}
		catch (\Exception $e)
		{
			throw new \RuntimeException(sprintf('Exception was thrown when trying to read the session data: %s', $e->getMessage()), 0, $e);
		}
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function write($id, $data)
	{
		$db        = $this->jdb;
		$dbTable   = $this->dbOptions['db_table'];
		$dbDataCol = $this->dbOptions['db_data_col'];
		$dbIdCol   = $this->dbOptions['db_id_col'];
		$dbTimeCol = $this->dbOptions['db_time_col'];
		
		// Session data can contain non binary safe characters so we need to encode it.
		$encoded = base64_encode($data);
		
		try
		{
			$qry = $db->getQuery(true)
					  ->update($db->qn($dbTable))
					  ->set($db->qn($dbDataCol) .' = '. $db->q($encoded))
					  ->set($db->qn($dbTimeCol) .' = '. (int) time())
					  ->where($db->qn($dbIdCol) .' = '. $db->q($id));
			
			if (!$db->setQuery($qry)->execute())
			{
				// No session exists in the database to update. This happens when we have called
				// session_regenerate_id()
				$this->createNewSession($id, $data);
			}
		}
		catch (\Exception $e)
		{
			throw new \RuntimeException(sprintf('Exception was thrown when trying to write the session data: %s', $e->getMessage()), 0, $e);
		}
	
		return true;
	}
	
	/**
	 * Creates a new session with the given $id and $data
	 *
	 * @param string $id
	 * @param string $data
	 *
	 * @return boolean True.
	 */
	private function createNewSession($id, $data = '')
	{
		// get table/column
		$db        = $this->jdb;
		$dbTable   = $this->dbOptions['db_table'];
		$dbDataCol = $this->dbOptions['db_data_col'];
		$dbIdCol   = $this->dbOptions['db_id_col'];
		$dbTimeCol = $this->dbOptions['db_time_col'];
		$qry       = $db->getQuery(true);
		
		// Session data can contain non binary safe characters so we need to encode it.
		$encoded = base64_encode($data);
		
		$qry->insert($db->qn($dbTable))
			->columns($db->qn(array($dbIdCol, $dbDataCol, $dbTimeCol)))
			->values($db->q($id) .','. $db->q($encoded) .','. (int) time());
		
		$db->setQuery($qry)->execute();
	
		return true;
	}
}
