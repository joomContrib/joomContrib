<?php
/**
 * @name       TablePrefixPdo
 * @package    joomContrib\Providers
 *
 * Code by Glass robot (Modifications: removed suffix option and added #__ placeholder)
 * http://stackoverflow.com/questions/1472250/pdo-working-with-table-prefixes
 */

namespace joomContrib\Providers\PdoExtensions;

/**
 * Table prefix extension
 *
 * @note  Didn't extend the PDOStatement, as table names cannot be bound: http://us3.php.net/manual/en/book.pdo.php#69304
 *
 * Actually this should be a trait for PdoExtension
 */
class TablePrefixPdo extends \PDO
{
	/**
	 * Placeholder
	 *
	 * @var  string
	 */
	protected $placeholder = '#__';

	/**
	 * @var  string
	 */
	protected $table_prefix;

	/**
	 * {@inheritDoc}
	 *
	 * @param  string  $prefix
	 *
	 * @note   to use custom statement class, use:
	 * <code>
	 *	$this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array(__NAMESPCE__ . '\Statement' => array($this));
	 * </code>
	 */
	public function __construct($dsn, $user = null, $password = null, $driver_options = array(), $prefix = null)
	{
		$this->table_prefix = $prefix;
		parent::__construct($dsn, $user, $password, $driver_options);
	}

	/**
	 * {@inheritDoc}
	 */
	public function exec($statement)
    {
        $statement = $this->tablePrefix($statement);
        return parent::exec($statement);
    }

	/**
	 * {@inheritDoc}
	 *
	 * @todo  return extended PDOStatement
	 */
	public function prepare($statement, $driver_options = array())
	{	
		$statement = $this->tablePrefix($statement);
		return parent::prepare($statement, $driver_options);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @todo  return extended PDOStatement
	 */
	public function query($statement)
	{
		$statement = $this->tablePrefix($statement);
		$args      = func_get_args();

		if (count($args) > 1)
		{
			return call_user_func_array(array($this, 'parent::query'), $args);
		}
		else
		{
			return parent::query($statement);
		}
	}

	/**
	 * Replace prefix
	 * Function is public so may be used by Statement
	 *
	 * @param   string  $statement
	 *
	 * @return  string
	 *
	 * @note    Check out Joomla\Database\DatabaseDriver:replacePrefix
	 */
	public function tablePrefix($statement)
	{
		return str_replace($this->placeholder, $this->table_prefix, $statement);
	}
}
