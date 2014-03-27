# Service Providers



## Config

**Usage**

```PHP
$container->registerServiceProvider(new \joomContrib\Providers\ConfigServiceProvider(
	// Path to config file
	APPLICATION_ROOT . '/etc/config.yml'
);
```


**Result**
`config`


## Doctrine (Entity manager)

**Usage**

```PHP
$container->registerServiceProvider(new \joomContrib\Providers\DoctrineServiceProvider(
	// Paths to entities
	$paths = array()
	// Metdata type
	'annotation'
	// Table to exclude
	array('session')
));
```


**Result**

`Doctrine\\ORM\\EntityManager` alias `em`


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



## Twig Renderer

@TODO
