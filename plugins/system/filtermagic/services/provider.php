<?php

/**
 * @package   FilterMagic
 * @copyright Copyright (c)2022-2023 Nicholas K. Dionysopoulos
 * @modified  Joomla 6 compatibility port
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Dionysopoulos\Plugin\System\FilterMagic\Extension\FilterMagic;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;

return new class implements ServiceProviderInterface {
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function register(Container $container): void
	{
		$container->set(
			PluginInterface::class,
			function (Container $container): PluginInterface {
				$config  = (array) PluginHelper::getPlugin('system', 'filtermagic');
				$subject = $container->get(DispatcherInterface::class);
				$plugin  = new FilterMagic($subject, $config);

				$plugin->setApplication(Factory::getApplication());
				$plugin->setDatabase($container->get(DatabaseInterface::class));

				return $plugin;
			}
		);
	}
};
