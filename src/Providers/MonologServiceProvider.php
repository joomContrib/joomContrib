<?php
/**
 * @name       MonologServiceProvider
 * @package    joomContrib\Providers
 * @copyright  Copyright (C) 2014 joomContrib Team (https://github.com/orgs/joomContrib). All rights reserved.
 * @license    GNU Lesser General Public License version 2 or later; see https://www.gnu.org/licenses/lgpl.html
 */

namespace joomContrib\Providers;


use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Registry\Registry;

use Monolog\Logger;
use Monolog\Handler\NullHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SwiftMailerHandler;


/**
 * Creates a logger for the application based on config values.
 * 
 * Note: If you enable "system.log.handler_mail" in your config you need
 *       to have SwiftMailer installed!
 *
 * @since   1.0
 */
class MonologServiceProvider implements ServiceProviderInterface
{	
	/**
	 * Registers the service provider within a DI container.
	 *
	 * @param   object   $container   Joomla\DI\Container
	 *
	 * @return  void
	 * @since   1.0
	 */
	public function register(Container $container)
	{
		$that = $this;
	
		$container->set('logger', function(Container $c) use ($that)
		{
			// Get logger settings.
			$logger = $that->getLoggerSettings($c);
	
			return new Logger($logger[0], $logger[1], $logger[2]);
		},
		true, true);
	}
	
	/**
	 * Get the settings for the logger.
	 *
	 * @param   object   $c   Joomla\DI\Container
	 *
	 * @return  array
	 * @since   1.0
	 */
	public function getLoggerSettings(Container $c)
	{
		$settings = new \SplFixedArray(3);
		$config   = $c->get('config');
	
		// Set logger name.
		$settings[0] = $config->get('site.name_alias', 'syslog');
	
		// Set logger handlers.
		$settings[1] = $this->getHandlers($config);
	
		// Processor for extra fields.
		$settings[2] = $this->getProcessors($config);
	
		return $settings;
	}
	
	/**
	 * Get handlers for the logger.
	 *
	 * @param    Registry   $config
	 *
	 * @return   array      Array with handlers.
	 * @since    1.0
	 */
	protected function getHandlers(Registry $config)
	{
		$handlers = array();
	
		// Create handlers only if log is on.
		if (true === $config->get('log.on', false))
		{
			// Add default storage handler for logs.
			if (true === $config->get('log.handler_default', true))
			{
				// Get storage.
				$storage = $config->get('log.default_storage', 'file');
	
				// Set log level for default handler.
				$log_level = constant('\\Monolog\\Logger::'. strtoupper($config->get('log.default_level', 'info')));
	
				// Set the log file if storage is file or file_rotate.
				if ('file' === $storage || 'file_rotate' === $storage)
				{
					// Path settings.
					$path = INCS_PATH .'/logs/';
						
					if ('' !== $config->get('log.path', ''))
					{
						$path = $config->get('log.path');
					}
						
					// Set the log file.
					$file = $path . $config->get('site.domain_alias', 'syslog') .'.log';
				}
	
				switch ($storage)
				{
					case 'database':
						// @todo rainy day kind of stuff and don't forget to write a handler for it...
						break;
					case 'file_rotate':
						// Set RotatingFileHandler - see notes for this.
						$handlers[] = new RotatingFileHandler($file, $config->get('log.default_maxfiles', 30), $log_level);
						break;
					case 'file':
					default:
						// Set StreamHandler.
						$handlers[] = new StreamHandler($file, $log_level);
						break;
				}
			}
				
			// Add mail handler.
			if (true === $config->get('log.handler_mail', false) && '' !== $config->get('log.mail_to', ''))
			{
				// Get mail objects.
				$mail_objects = $this->getMailObjects($config);
				// Get the level.
				$log_level_m  = constant('\\Monolog\\Logger::'. strtoupper($config->get('log.mail_level', 'critical')));
				// Set SwiftMailerHandler.
				$handlers[]   = new SwiftMailerHandler($mail_objects[0], $mail_objects[1], $log_level_m);
			}
			
			// Check for dummies.
			if (empty($handlers))
			{
				$handlers[] = new NullHandler;
			}
		}
		// Use NullHandler if log is off.
		else
		{
			$handlers[] = new NullHandler;
		}
	
		// Return handlers.
		return $handlers;
	}
	
	/**
	 * Get processors for the logger.
	 *
	 * @param    Registry   $config
	 *
	 * @return   array      Array with processor callbacks.
	 * @since    1.0
	 */
	protected function getProcessors(Registry $config)
	{
		$processors = array();
	
		// Only add processors if log is on.
		if (true === $config->get('log.on', false))
		{
			$vars = array();
				
			// Add IP data.
			if (true === $config->get('log.extra.ip', true))
			{
				$vars['ip'] = $_SERVER['REMOTE_ADDR'];
					
				if (isset($_SERVER['HTTP_CLIENT_IP']))
				{
					$vars['ip'] .= ':'. $_SERVER['HTTP_CLIENT_IP'];
				}
			}
				
			if (true === $config->get('log.extra.ip_xfor', true))
			{
				$vars['ip_xfor'] = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '';
			}
				
			// Add http method.
			if (true === $config->get('log.extra.http_method', false))
			{
				$vars['http_method']     = $_SERVER['REQUEST_METHOD'];
				$vars['http_method_app'] = '';
					
				if ($_POST && isset($_POST['_method']))
				{
					$vars['http_method_app'] = (string) preg_replace('/[^a-z]/i', '', $_POST['_method']);
				}
			}
				
			// Add user agent.
			if (true === $config->get('log.extra.useragent', false))
			{
				$vars['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
			}
			
			// Create callback for $record['extra'] (2nd json string in log).
			$processors[] = function ($record) use ($vars)
			{
				foreach ($vars as $name => $value)
				{
					$record['extra'][$name] = $value;
				}
					
				return $record;
			};
		}
	
		// Return processors.
		return $processors;
	}
	
	/**
	 * Get objects for the mail handler.
	 *
	 * @param    Registry   $config
	 *
	 * @throws   \InvalidArgumentException
	 * @return   array  \Swift_Mailer, \Swift_Message
	 * @since    1.0
	 */
	protected function getMailObjects(Registry $config)
	{
		$mail = new \SplFixedArray(2);
	
		// Set transport.
		switch ($config->get('log.mail_transport', 'smtp'))
		{
			case 'smtp':
				if (true === $config->get('log.mail_usesystem', true))
				{
					$username = $config->get('system.mail_smtp_user', '');
					$password = $config->get('system.mail_smtp_pw', '');
					$host     = $config->get('system.mail_smtp_host', '');
					$port     = $config->get('system.mail_smtp_port', 25);
				}
				else
				{
					$username = $config->get('log.mail_smtp_user', '');
					$password = $config->get('log.mail_smtp_pw', '');
					$host     = $config->get('log.mail_smtp_host', '');
					$port     = $config->get('log.mail_smtp_port', 25);
				}
					
				if ('' === $username || '' === $password || '' === $host)
				{
					throw new \InvalidArgumentException('No smtp data set for logger.', 500);
				}
				else
				{
					// Transport smtp.
					$transport = \Swift_SmtpTransport::newInstance($host, $port)
								->setUsername($username)
								->setPassword($password);
				}
					
				break;
			case 'sendmail':
				// Transport using sendmail.
				$sendmail  = $config->get('log.mail_sendmail', '/usr/sbin/sendmail -bs');
				$transport = \Swift_SendmailTransport::newInstance($sendmail);
				break;
			case 'php':
			default:
				// Transport PHP native mail function.
				$transport = \Swift_MailTransport::newInstance();
				break;
		}
			
		// Set mailer.
		$mailer       = \Swift_Mailer::newInstance($transport);
		$mail_from    = '' !== $config->get('log.mail_from', '') ? $config->get('log.mail_from') : $config->get('system.mail_from');
		$mail_from_n  = $config->get('site.name') .' Logger';
		$mail_to      = $config->get('log.mail_to');
		$mail_to_name = $config->get('site.name') .' Support';
		$subject      = $config->get('site.name') .' - Log Message!';
			
		// Create mail message object.
		$message = \Swift_Message::newInstance($subject)
					->setFrom(array($mail_from => $mail_from_n))
					->setTo(array($mail_to => $mail_to_name));
					//->setBody($body);
	
		// Write array and return.
		$mail[0] = $mailer;
		$mail[1] = $message;
	
		return $mail;
	}
}
