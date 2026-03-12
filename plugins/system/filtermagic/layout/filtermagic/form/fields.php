<?php

/**
 * @package   FilterMagic
 * @copyright Copyright (c)2022-2023 Nicholas K. Dionysopoulos
 * @modified  Joomla 6 compatibility port
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Layout\FileLayout;

/**
 * @var array      $displayData
 * @var FileLayout $this
 * @var Form       $form
 */

$form    = $displayData['form'];
$filters = $form->getGroup('filter');

?>
<?php foreach ($filters as $field): ?>
	<?php
	// Costruisce l'attributo data-showon se necessario, con escape corretto
	$dataShowOn = '';

	if ($field->showon) {
		$conditions = FormHelper::parseShowOnConditions(
			$field->showon,
			$field->formControl,
			$field->group
		);

		// JSON encodato e poi escapato per inserimento sicuro in attributo HTML
		$dataShowOn = ' data-showon="'
			. htmlspecialchars(json_encode($conditions), ENT_QUOTES, 'UTF-8')
			. '"';
	}
	?>
	<div class="filtermagic-field-filter" <?= $dataShowOn ?>>
		<?php /* $field->label è HTML generato da Joomla — usare htmlspecialchars sul testo plain */ ?>
		<span class="visually-hidden">
			<?= htmlspecialchars($field->label, ENT_QUOTES, 'UTF-8') ?>
		</span>
		<?php /* $field->input è HTML già renderizzato da Joomla — output diretto intenzionale */ ?>
		<?= $field->input ?>
	</div>
<?php endforeach; ?>