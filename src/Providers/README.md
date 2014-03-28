## Service Providers



### Config

Configuration provider.

**Accepted parameters**

- `$path`: Absolute path to config file. May be any format that'c compatible with [RegistryFormat](https://github.com/joomla-framework/registry/tree/master/src/Format) (json, yaml, ini, php, xml)
- `$config` _(optional)_ Registry object to append new data to
 

**Example**

Registering provider

```PHP
$container->registerServiceProvider(new \joomContrib\Providers\ConfigServiceProvider(APP_ROOT . '/etc/config.yml');
```

Usage
```PHP
$config = $container->get('config');
```


**Dependencies**
```JSON
{
	"joomla/registry": "~1.1"
}
```


### Doctrine

Doctrine ORM entity manager

**Accepted parameters**

- `$paths`: Paths to Entities. If empty, will add all folders that match `[app_root]/src/Component/*/Entities` pattern
- `$metadataType`: Entities metadata type. Defaults to `annotation`.
- `$excludes`: Tables to exclude. Defaults to `array('session')`

**Usage**

Register Provider

```PHP
$container->registerServiceProvider(new \joomContrib\Providers\DoctrineServiceProvider);
```

Retrieve service

```PHP
$em = $container->get('em');
```

Dependency resolution

```PHP
use Doctrine\\ORM\\EntityManager as EntityManager;

namespace App\Models

class OrmModel
{
	public function __construct(EntityManager $em, $state = null)
	{
	}
}
```
```PHP
class OrmController
{
	public function execute()
	{
		$model = $this->container->buildObject('App\\Models\\OrmModel');
	}
}
```

Console

```
bin/doctrine
```


**Dependecies**

	- `config`
		- database
			- driver
			- host
			- name
			- user
			- password
			- prefix (optional)
	- `app_root`

```JSON
{
	"doctrine/orm": "~2.4"
}
```


### Twig Renderer

@TODO
