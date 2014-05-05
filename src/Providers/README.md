## Service Providers

### Naming conventions

Services are registered by the returned class name and aliased by provider name, ie:

The `DatabaseServiceProvider`
- registered as `\\Joomla\\Database\\DatabaseDriver`
- alias `database`

Using this it's possible to build objects using Dependency Injection Container:

```PHP
use Joomla\Database\DatabaseDriver;

class Helper
{
	public function __construct(DatabaseDriver $db)
	{
		$this->db = $db;
	}

	public function getSomething()
	{
		return $this->db->execute('SELECT * FROM #__something')->loadObjectList();
	}
}

class MyController
{
	use ContainerAwareTrait;

	public function execute()
	{
		$container = $this->getContainer();

		$helper = $container->buildObject('Helper');

		return $helper->getSomething();
	}
}

```


### Common service providers

Some providers work with the filesystem and thus require information about the application root location.
Instead of using constants like `APPLICATION_ROOT`, register `app_root` object within the container:

For example in your front controller:

```PHP
// Define app root
$app_root = realpath(__DIR__ . '/..');

// Add autoloader
include $app_root . '/vendor/autoload.php';


// Instantiate app
$app = new \app\WebApplication;

// Add app_root to app container
$container = $app->getContainer();
$container->share('app_root', $app_root, true);

$app->execute();
```

---

### Configuration

Configuration provider.

#### Accepted parameters

- `$path`: Absolute path to config file. May be any format that is compatible with [RegistryFormat](https://github.com/joomla-framework/registry/tree/master/src/Format) (json, yaml, ini, php, xml)
- `$config` _(optional)_: Registry object to append new data to
- `$env` _(optional)_: Environment suffix to append to config filename. When null will load from `JFW_ENV` environment variable
 

#### Usage

**Register**

```php
use joomContrib\Providers\ConfigurationServiceProvider;

$container->registerServiceProvider(new ConfigurationServiceProvider($appRoot . '/etc/config.yml');
```

**Retrieve**

```php
$config = $container->get('config');
```

**Load without DI**
```php
use joomContrib\Providers\ConfigurationServiceProvider;

$config = (new ConfigServiceProvider($appRoot . '/etc/config.yml'))->getConfiguration();
```

#### Dependencies

**Packages**

```JSON
{
	"joomla/registry": "~1.1"
}
```

---

### Doctrine

Doctrine ORM entity manager

#### Accepted parameters

- `$paths`: Paths to Entities or glob pattern to lookup. If empty, will add all folders that match `[app_root]/src/Component/*/Entities` pattern.
- `$metadataType`: Entities metadata type. Available options are `annotation` (default), `xml`, `yaml`.
- `$excludes`: Tables to exclude. Defaults to `array('session')`


#### Usage

**Register**

```PHP
$container->registerServiceProvider(new \joomContrib\Providers\DoctrineServiceProvider);
```

**Retrieve**

```PHP
$em = $container->get('em');
```

**Dependency resolution**

```PHP
use Doctrine\\ORM\\EntityManager as EntityManager;

namespace App\Model

class OrmModel
{
	public function __construct(EntityManager $em, $state = null)
	{
		$this->em = $em;
	}
}
```
```PHP
class OrmController
{
	public function execute()
	{
		$model = $this->container->buildObject('App\\Model\\OrmModel');
	}
}
```

**Console**

_Note: make sure path to configuration is correct in `/bin/config/cli-config.php`_

```
>cd bin
>doctrine orm:info

Found 2 mapped entities:
[OK]   Component\PresentationComponent\Entity\Profile
[OK]   Component\AdminComopnent\Entity\User
```


#### Dependecies

**Services**

- `config`

	- database: driver, host, database, user, password, prefix (optional)

- `app_root`

**Packages**

```JSON
{
	"doctrine/orm": "~2.4"
}
```

---

### PDO

PDO provider, so you don't have to require `Joomla\Database` for trivial tasks.

_Warning: Using two connections (PDO + Joomla\Daabase) may cause problems, see [MySQL server has gone away](http://dev.mysql.com/doc/refman/5.0/en/gone-away.html)_

Features:

 - May use table prefixes now
 - Uses configuration `pdo` options with fallback to `database` options


#### Usage

**Register**

```PHP
$container->registerServiceProvider(new \joomContrib\Providers\PdoServiceProvider);
```

**Retrieve and Use**

```PHP
$pdo = $container->get('pdo');

$sth = $pdo->prepare('SELECT * FROM #__user WHERE username = :username');
$sth->execute(array(':name' => $username));

$data = $sth->fetchObject();
```


#### Dependencies

**Services**

- `config`

	- database: driver, host, name, user, password, prefix (optional)
	- pdo (same as above, optional)

**PHP**

The adequate php extension must be installed `php_pdo_[driver]`.

**Packages**

none


---


### Twig Renderer

@TODO
