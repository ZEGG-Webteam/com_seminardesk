<?php
/**
 * @package     SeminarDesk
 * @subpackage  com_seminardesk
 *
 * @copyright   Copyright (C) 2022 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

$app = Factory::getApplication();
$document = $app->getDocument();

// Assets are loaded via WebAssetManager in HtmlView::prepareDocument()

$previousEventMonth = '';
$filters = [
  'date' => $app->input->get('date', '', 'string'), 
  'cat'  => $app->input->get('cat',  0,  'integer'), 
  'org'  => $app->input->get('org',  '', 'string'), 
  'term' => $app->input->get('term', '', 'string'), 
  'lang' => $app->input->get->get('lang', $this->langKey, 'string'), // Only get "lang" from URL, not from other sources.
];

// Collect unique months from events for the filter dropdown
$availableMonths = [];
foreach($this->events->getItems() as $eventDate) {
  $monthKey = date('Y-m', $eventDate->beginDate) . '-01'; // Include day for easier date filter
  if (!isset($availableMonths[$monthKey])) {
    $availableMonths[$monthKey] = HTMLHelper::_('date', $eventDate->beginDate, 'F Y');
  }
}

// Add template path for sub-templates
$this->addTemplatePath(JPATH_COMPONENT . '/src/View/Events/tmpl');
?>

<div class="sd-component sd-events<?php echo ($this->pageclass_sfx)?' sd-events'.$this->pageclass_sfx:''; ?>">
  
	<?php // if ($app->input->get('show_page_heading')) : // to do: buggy ?>
		<div class="page-header">
			<h1><?= $this->escape($this->title) ?></h1>
		</div>
	<?php // endif; ?>
  
  <div class="sd-filter">
    <form class="sd-filter-form">
      <input type="text" name="term" id="sd-filter-search-term" value="" placeholder="<?= Text::_("COM_SEMINARDESK_FILTER_TERM_PLACEHOLDER");?>">
      <select name="date" id="sd-filter-date">
        <?php foreach($availableMonths as $monthKey => $monthLabel) : ?>
          <option value="<?= $monthKey ?>"<?= ($filters['date'] == $monthKey)?' selected':'' ?>><?= $monthLabel ?></option>
        <?php endforeach; ?>
      </select>
      <select name="lang" id="sd-filter-lang" data-current-lang="<?= $this->langKey ?>">
        <option value="all"<?= (!$filters['lang'] || $filters['lang'] == 'all')?' selected':'' ?>><?= Text::_("COM_SEMINARDESK_FILTER_LANGUAGE_ALL");?></option>
        <option value="de"<?= ($filters['lang'] == 'de')?' selected':'' ?>><?= Text::_("COM_SEMINARDESK_FILTER_LANGUAGE_GERMAN");?></option>
        <option value="en"<?= ($filters['lang'] == 'en')?' selected':'' ?>><?= Text::_("COM_SEMINARDESK_FILTER_LANGUAGE_ENGLISH");?></option>
        <!--<option value="es"<?= ($filters['lang'] == 'es')?' selected':'' ?>><?= Text::_("COM_SEMINARDESK_FILTER_LANGUAGE_SPANISH");?></option>-->
      </select>
      <select name="category" id="sd-filter-category">
        <option value="0"><?= Text::_("COM_SEMINARDESK_FILTER_CATEGORY_ALL") ?></option>
        <?php foreach($this->events->getAllEventCategories() as $key => $category) : ?>
          <option value="<?= $key ?>"<?= ($filters['cat'] == $key)?' selected':'' ?>><?= $category ?></option>
        <?php endforeach; ?>
      </select>
      <select name="organisers" id="sd-filter-organisers">
        <option value="all"><?= Text::_("COM_SEMINARDESK_FILTER_ORGANISER_ALL");?></option>
        <option value="zegg"<?= ($filters['org'] == 'zegg')?' selected':'' ?>><?= Text::_("COM_SEMINARDESK_FILTER_ORGANISER_ZEGG");?></option>
        <option value="external"<?= ($filters['org'] == 'external')?' selected':'' ?>><?= Text::_("COM_SEMINARDESK_FILTER_ORGANISER_EXTERNAL");?></option>
      </select>
    </form>
  </div>
  
  <?php if ($document->countModules('above-events')): ?>
    <section class="above-events-container<?= ($this->events->getItems())?' hidden':'' ?>">
      <div class="row">
        <?= HTMLHelper::_('content.prepare', '{loadposition above-events, column}') ?>
      </div>
    </section>
  <?php endif ; ?>

  <div class="sd-eventlist">
    <div class="sd-month">
    <?php 
      foreach($this->events->getItems() as $eventDate) {
        //-- New month heading?
        $currentMonth = (int)date('m', $eventDate->beginDate);
        if ($currentMonth !== $previousEventMonth) {
          // Close last month container and open new one?>
          </div>
          <div class="sd-month loading">
            <div class="sd-month-row">
              <h3><?= Text::sprintf( HTMLHelper::_('date', $eventDate->beginDate, 'F Y')) ?></h3>
            </div>
          <?php
          $previousEventMonth = $currentMonth;
        }
        
        $this->eventDate = & $eventDate;
        $this->filters = $filters;
        echo $this->loadTemplate('event');
      }
    ?>
    </div>
    <div class="no-events-found filtered<?= ($this->events->getItems())?' hidden':'' ?>">
      <p>
        <?= Text::_("COM_SEMINARDESK_EVENTS_NO_EVENTS_FOUND_IN");?><br>
        &gt; <a href="?term=<?= $this->filters['term'] ?: '' ?>"><?= Text::_("COM_SEMINARDESK_EVENTS_SEARCH_ALL");?></a>
      </p>
    </div>
    <div class="no-events-found all<?= ($this->events->getItems())?' hidden':'' ?>">
      <p><?= Text::_("COM_SEMINARDESK_EVENTS_NO_EVENTS_FOUND");?></p>
    </div>
  </div>
  
  <?php if ($document->countModules('below-events')): ?>
    <section class="below-events-container">
      <div class="row">
        <?= HTMLHelper::_('content.prepare', '{loadposition below-events, column}') ?>
      </div>
    </section>
  <?php endif ; ?>

</div>
