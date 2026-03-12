<?php

/**
 * @package   FilterMagic
 * @copyright Copyright (c)2022-2023 Nicholas K. Dionysopoulos
 * @modified  Joomla 6 compatibility port
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Uri\Uri;

/**
 * @var array      $displayData
 * @var FileLayout $this
 * @var Form       $form
 */

$form    = $displayData['form'];
$filters = $form->getGroup('filter');

if (empty($filters)) {
	return;
}

// bin2hex(random_bytes) è più diretto di md5(random_bytes) per generare ID univoci
$formName    = 'plgSystemFilterMagic_' . bin2hex(random_bytes(8));
$formNameEsc = htmlspecialchars($formName, ENT_QUOTES, 'UTF-8');
$actionUrl   = htmlspecialchars(Uri::current(), ENT_QUOTES, 'UTF-8');

?>
<form name="<?= $formNameEsc ?>" action="<?= $actionUrl ?>" method="post"
	class="my-2 d-flex flex-row gap-2 align-items-center bg-light border rounded border-1 p-2" id="<?= $formNameEsc ?>">
	<?php /* Span semanticamente corretto: non è una label associata a un singolo input */ ?>
	<span class="visually-hidden" aria-hidden="true">
		<?= Text::_('JSEARCH_FILTER_LABEL') ?>
	</span>

	<?= $this->sublayout('fields', $displayData) ?>

	<input type="hidden" name="filtermagic[reset]" id="<?= $formNameEsc ?>_reset" value="0" />

	<button type="submit" class="btn btn-primary">
		<span class="fa fa-search" aria-hidden="true"></span>
		<?= Text::_('JSEARCH_FILTER') ?>
	</button>

	<button type="reset" class="btn btn-secondary plgSystemFilterMagicClear" data-form="<?= $formNameEsc ?>">
		<?= Text::_('JSEARCH_FILTER_CLEAR') ?>
	</button>
</form>