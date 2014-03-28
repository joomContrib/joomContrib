# Handler Example

## Joomla DatabaseDriver Session Handler

**Requires**
Symfony\Component\HttpFoundation\Session

**Handler options**
"db_table", "db_id_col", "db_data_col", "db_time_col"

**Usage example**

#### Config

```javascript
{
	"system":{
		"session":{
			"save_path": "/var/www/joomcontrib/incs",
			"storage": "database",
			
			"table_options":{
				"db_table": "#__session",
				"db_id_col": "session_id",
				"db_data_col": "data",
				"db_time_col": "time"
			},
			"storage_options":{
				"gc_maxlifetime": 900,
				"cookie_domain": "",
				"cookie_path": "/"
			}
		}
	}
}
```

#### Application method

```php
	protected function createSession()
	{
		$conf = $this->container->get('config');

		// Handlers
		switch ($conf->get('system.session.storage', 'file'))
		{
			case 'database':
				// Set column names
				$cols    = (array) $conf->get('system.session.table_options');
				$db      = $this->container->get('db');
				$handler = new JoomlaDbSessionHandler($db, $cols);
				break;
			case 'file':
				$path      = INCS_PATH .'/sessions';
				$save_path = $conf->get('system.session.save_path', $path);
				$handler   = new NativeFileSessionHandler($save_path);
				break;
			default:
				$handler = null;
				break;
		}
		
		$options       = (array) $conf->get('system.session.storage_options');
		$storage       = new NativeSessionStorage($options, $handler);
		$this->session = new Session($storage);
		
		$this->session->start();

		return $this;
	}
```
