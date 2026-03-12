<?php

/**
 * @package   FilterMagic
 * @copyright Copyright (c)2022-2023 Nicholas K. Dionysopoulos
 * @modified  Joomla 6 compatibility port
 * @license   GNU General Public License version 3, or later
 */

namespace Dionysopoulos\Plugin\System\FilterMagic\Helper;

defined('_JEXEC') || die;

use Joomla\CMS\Layout\FileLayout;
use Joomla\Registry\Registry;

/**
 * A replacement to Joomla's LayoutHelper.
 *
 * Unlike Joomla's unhelpful helper, providing a custom base path will NOT make it impossible to override the layout
 * in your template. Obviously Joomla's helper is only meant to be helpful to the core, not to 3PDs. Sigh.
 *
 * @since  1.0.0
 */
class LayoutHelper
{
	/**
	 * A default base path that will be used if none is provided when calling the render method.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	public static string $defaultBasePath = '';

	/**
	 * Method to render a layout with debug info.
	 *
	 * @param   string                    $layoutFile   Dot separated path to the layout file, relative to base path
	 * @param   mixed                     $displayData  Object which properties are used inside the layout file
	 * @param   string                    $basePath     Base path to use when loading layout files
	 * @param   Registry|array|null       $options      Optional custom options to load
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public static function debug(
		string $layoutFile,
		mixed $displayData = null,
		string $basePath = '',
		Registry|array|null $options = null
	): string {
		$layout = self::getFileLayout($layoutFile, self::resolvePath($basePath), $options);

		return $layout->debug($displayData);
	}

	/**
	 * Method to render the layout.
	 *
	 * @param   string                    $layoutFile   Dot separated path to the layout file, relative to base path
	 * @param   mixed                     $displayData  Object which properties are used inside the layout file
	 * @param   string                    $basePath     Base path to use when loading layout files
	 * @param   Registry|array|null       $options      Optional custom options to load
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public static function render(
		string $layoutFile,
		mixed $displayData = null,
		string $basePath = '',
		Registry|array|null $options = null
	): string {
		$layout = self::getFileLayout($layoutFile, self::resolvePath($basePath), $options);

		return $layout->render($displayData);
	}

	/**
	 * Resolve the effective base path: prefer explicit $basePath, fall back to $defaultBasePath, or null.
	 *
	 * @param   string  $basePath  Explicitly passed base path (may be empty string)
	 *
	 * @return  string|null
	 * @since   1.0.0
	 */
	private static function resolvePath(string $basePath): ?string
	{
		$resolved = !empty($basePath) ? $basePath : self::$defaultBasePath;

		return !empty($resolved) ? $resolved : null;
	}

	/**
	 * Get a FileLayout object instance.
	 *
	 * Adds the custom base path at the END of the include paths so that template overrides
	 * (which are added first by FileLayout itself) always take precedence.
	 *
	 * @param   string                    $layoutFile  Dot separated path to the layout file
	 * @param   string|null               $basePath    Base path, or null for Joomla default
	 * @param   Registry|array|null       $options     Optional custom options
	 *
	 * @return  FileLayout
	 * @since   1.0.0
	 */
	private static function getFileLayout(
		string $layoutFile,
		?string $basePath = null,
		Registry|array|null $options = null
	): FileLayout {
		$layout = new FileLayout($layoutFile, null, $options);

		if (empty($basePath)) {
			return $layout;
		}

		$paths   = $layout->getIncludePaths();
		$paths[] = $basePath;

		$layout->clearIncludePaths();
		$layout->addIncludePaths($paths);

		return $layout;
	}
}
