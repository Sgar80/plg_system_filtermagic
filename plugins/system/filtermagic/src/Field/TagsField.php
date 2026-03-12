<?php

/**
 * @package   FilterMagic
 * @copyright Copyright (c)2022-2023 Nicholas K. Dionysopoulos
 * @modified  Joomla 6 compatibility port
 * @license   GNU General Public License version 3, or later
 */

namespace Dionysopoulos\Plugin\System\FilterMagic\Field;

defined('_JEXEC') || die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class TagsField extends ListField
{
	/**
	 * A flexible tag list that respects access controls.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	// J6: protected + typed string
	protected $type = 'Tags';

	/**
	 * Flag to work with nested tag field.
	 * Renamed from $isNested to avoid collision with the isNested() method.
	 *
	 * @var    bool|null
	 * @since  1.0.0
	 */
	protected ?bool $isNestedMode = null;

	/**
	 * com_tags parameters.
	 *
	 * @var    Registry|null
	 * @since  1.0.0
	 */
	protected ?Registry $comParams = null;

	/**
	 * Name of the layout being used to render the field.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	// J6: typed string
	protected $layout = 'joomla.form.field.tag';

	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->comParams = ComponentHelper::getParams('com_tags');
	}

	/**
	 * Determine if the field should render as nested.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function isNested(): bool
	{
		if ($this->isNestedMode === null) {
			$modeAttr = isset($this->element['mode']) ? (string) $this->element['mode'] : null;

			$this->isNestedMode = ($modeAttr === 'nested')
				|| ($modeAttr === null && $this->comParams->get('tag_field_ajax_mode', 1) == 0);
		}

		return $this->isNestedMode;
	}

	/**
	 * Determines if the field allows custom values.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function allowCustom(): bool
	{
		$custom = isset($this->element['custom']) ? (string) $this->element['custom'] : '';

		if (in_array($custom, ['0', 'false', 'deny'], true)) {
			return false;
		}

		// J6: Factory::getUser() rimosso — usare getApplication()->getIdentity()
		$user = Factory::getApplication()->getIdentity();

		return $user !== null && $user->authorise('core.create', 'com_tags');
	}

	/**
	 * Check whether AJAX remote search is enabled.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function isRemoteSearch(): bool
	{
		if (isset($this->element['remote-search'])) {
			return !in_array((string) $this->element['remote-search'], ['0', 'false', ''], true);
		}

		return $this->comParams->get('tag_field_ajax_mode', 1) == 1;
	}

	/**
	 * Method to get the field input for a tag field.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function getInput(): string
	{
		$data = $this->getLayoutData();

		if (!is_array($this->value) && !empty($this->value)) {
			if ($this->value instanceof TagsHelper) {
				$this->value = empty($this->value->tags) ? [] : $this->value->tags;
			} elseif (is_string($this->value)) {
				$this->value = explode(',', $this->value);
			} elseif (is_int($this->value)) {
				$this->value = [$this->value];
			}

			$data['value'] = $this->value;
		}

		$data['remoteSearch']  = $this->isRemoteSearch();
		$data['options']       = $this->getOptions();
		$data['isNested']      = $this->isNested();
		$data['allowCustom']   = $this->allowCustom();
		$data['minTermLength'] = (int) $this->comParams->get('min_term_length', 3);

		return $this->getRenderer($this->layout)->render($data);
	}

	/**
	 * Method to get a list of tags.
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	protected function getOptions(): array
	{
		// FIX: il ternario originale con ?: [0,1] era ambiguo — gestiamo esplicitamente
		$publishedAttr = (string) ($this->element['published'] ?? '');
		$published     = $publishedAttr !== '' ? $publishedAttr : [0, 1];

		$rootTag        = isset($this->element['root']) && (string) $this->element['root'] !== ''
			? (string) $this->element['root']
			: null;

		$app            = Factory::getApplication();
		$language       = null;
		$options        = [];
		$prefillLimit   = 30;
		$isRemoteSearch = $this->isRemoteSearch();

		// J6: $this->getDatabase() corretto (FormField ha DatabaseAwareTrait da J4.2)
		$db    = $this->getDatabase();
		$query = $db->getQuery(true)
			->select(
				[
					$db->quoteName('a.id', 'value'),
					$db->quoteName('a.path'),
					$db->quoteName('a.title', 'text'),
					$db->quoteName('a.level'),
					$db->quoteName('a.published'),
					$db->quoteName('a.lft'),
				]
			)
			->from($db->quoteName('#__tags', 'a'));

		// Filtro lingua in contesto multilingua o da attributo XML
		if ($app->isClient('site') && Multilanguage::isEnabled()) {
			if (ComponentHelper::getParams('com_tags')->get('tag_list_language_filter') === 'current_language') {
				$language = [$app->getLanguage()->getTag(), '*'];
			}
		} elseif (!empty($this->element['language'])) {
			$langAttr = (string) $this->element['language'];
			$language = strpos($langAttr, ',') !== false
				? explode(',', $langAttr)
				: [$langAttr];
		}

		if ($language) {
			$query->whereIn($db->quoteName('a.language'), $language, ParameterType::STRING);
		}

		// Filtro root tag
		if ($rootTag === null) {
			$query->where($db->quoteName('a.lft') . ' > 0');
		} else {
			$subQuery = $db->getQuery(true)
				->select('1')
				->from($db->quoteName('#__tags', 'searchTag'))
				->where($db->quoteName('searchTag.id') . ' = ' . (int) $rootTag)
				->where($db->quoteName('a.lft') . ' > ' . $db->quoteName('searchTag.lft'))
				->where($db->quoteName('a.rgt') . ' < ' . $db->quoteName('searchTag.rgt'));

			$query->where('EXISTS(' . $subQuery . ')');
		}

		// Filtro published
		if (is_numeric($published)) {
			$published = (int) $published;
			$query->where($db->quoteName('a.published') . ' = :published')
				->bind(':published', $published, ParameterType::INTEGER);
		} elseif (is_array($published)) {
			$query->whereIn($db->quoteName('a.published'), ArrayHelper::toInteger($published));
		}

		$query->order($db->quoteName('a.lft') . ' ASC');

		// Remote search: preload top N tags + tag selezionati
		if ($isRemoteSearch) {
			$topQuery = $db->getQuery(true)
				->select($db->quoteName('tag_id'))
				->from($db->quoteName('#__contentitem_tag_map'))
				->group($db->quoteName('tag_id'))
				->order('count(*)')
				->setLimit($prefillLimit);

			$db->setQuery($topQuery);
			$topIds = $db->loadColumn();

			if (!empty($this->value) && is_array($this->value)) {
				$topIds = array_unique(array_merge($topIds, $this->value));
			}

			$query->setLimit($prefillLimit);

			if (!empty($topIds)) {
				$preQuery = clone $query;
				$preQuery->clear('limit')
					->whereIn($db->quoteName('a.id'), $topIds);

				$db->setQuery($preQuery);

				try {
					$options = $db->loadObjectList();
				} catch (\RuntimeException $e) {
					return [];
				}

				$count        = count($options);
				$prefillLimit = $prefillLimit - $count;
				$query->setLimit($prefillLimit);

				if ($count > 0) {
					$query->whereNotIn($db->quoteName('a.id'), ArrayHelper::getColumn($options, 'value'));
				}
			}
		}

		// Carica i restanti tag se necessario
		if (!$isRemoteSearch || $prefillLimit > 0) {
			$db->setQuery($query);

			try {
				$options = array_merge($options, $db->loadObjectList());
			} catch (\RuntimeException $e) {
				return [];
			}
		}

		// Disabilita il tag corrente se siamo nel form com_tags.tag (evita auto-parentaggio)
		if ($this->form->getName() === 'com_tags.tag') {
			$id = (int) $this->form->getValue('id', 0);

			foreach ($options as $option) {
				if ((int) $option->value === $id) {
					$option->disable = true;
				}
			}
		}

		// Merge opzioni aggiuntive da XML
		$options = array_merge(parent::getOptions(), $options);

		// Prepara visualizzazione nested o flat
		if ($this->isNested()) {
			$this->prepareOptionsNested($options);
		} else {
			$options = TagsHelper::convertPathsToNames($options);
		}

		return $options;
	}

	/**
	 * Add "- " prefix to nested tags based on their level.
	 *
	 * @param   array  $options  Array of tag option objects (passed by reference).
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function prepareOptionsNested(array &$options): void
	{
		foreach ($options as $option) {
			$repeat       = isset($option->level) ? max(0, $option->level - 1) : 0;
			$option->text = str_repeat('- ', $repeat) . $option->text;
		}
	}
}
