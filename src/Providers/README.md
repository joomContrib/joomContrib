## Service Providers

Common service providers

Some providers work with the filesystem and thus require information about the application root location.
Instead of using constants like `APPLICATION_ROOT`, register `app_root` object within the container:

For example in your front controller:

`PHP
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

### Config

Configuration provider.

#### Accepted parameters

- `$path`: Absolute path to config file. May be any format that'c compatible with [RegistryFormat](https://github.com/joomla-framework/registry/tree/master/src/Format) (json, yaml, ini, php, xml)
- `$config` _(optional)_ Registry object to append new data to
 

#### Usage

**Register**

```PHP
$container->registerServiceProvider(new \joomContrib\Providers\ConfigServiceProvider(APP_ROOT . '/etc/config.yml');
```

**Retrieve**

```PHP
$config = $container->get('config');
```


#### Dependencies

**Packages**

```JSON
{
	"joomla/registry": "~1.1"
}
```


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

__make sure path to configuration is correct in `/bin/config/cli-config.php`__

```
bin/doctrine orm:info

Found 2 mapped entities:
[OK]   Component\PresentationComponent\Entity\Profile
[OK]   Component\AdminComopnent\Entity\User
```


#### Dependecies

**Services**

- `config`

	- database
		- driver
		- host
		- name
		- user
		- password
		- prefix (optional)

- `app_root`

**Packages**

```JSON
{
	"doctrine/orm": "~2.4"
}
```


### Twig Renderer

@TODO
