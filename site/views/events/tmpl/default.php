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

use \Joomla\CMS\Factory;

JHtml::_('jquery.framework');

$app = Factory::getApplication();

//-- Load CSS / JS
$document  = Factory::getDocument();
$document->addStyleSheet('/media/com_seminardesk/css/styles.css');
$document->addScript('/media/com_seminardesk/js/seminardesk.js');

$previousEventMonth = '';
$filters = [
  'date' => $app->input->get('date', '', 'string'), 
  'cat'  => $app->input->get('cat',  0,  'integer'), 
  'org'  => $app->input->get('org',  '', 'string'), 
  'term' => $app->input->get('term', '', 'string'), 
];
?>

<div class="sd-component sd-events<?php echo ($this->pageclass_sfx)?' sd-events'.$this->pageclass_sfx:''; ?>">
  
	<?php // if ($app->input->get('show_page_heading')) : // to do: buggy ?>
		<div class="page-header">
			<h1><?= $this->escape($this->title) ?></h1>
		</div>
	<?php // endif; ?>
  
  <?php if ($document->countModules('above-events')): ?>
    <section class="above-events-container">
      <div class="row">
        <?= JHtml::_('content.prepare', '{loadposition above-events, column}') ?>
      </div>
    </section>
  <?php endif ; ?>

  <div class="sd-filter">
    <form class="sd-filter-form">
      <input type="date" name="from" id="sd-filter-date-from" placeholder="<?= JText::_("COM_SEMINARDESK_FILTER_DATE_PLACEHOLDER");?>" value="<?= $filters['date'] ?>">
      <input type="text" name="term" id="sd-filter-search-term" value="" placeholder="<?= JText::_("COM_SEMINARDESK_FILTER_TERM_PLACEHOLDER");?>">
      <select name="category" id="sd-filter-category">
        <option value="0"><?= JText::_("COM_SEMINARDESK_FILTER_CATEGORY_ALL") ?></option>
        <?php foreach($this->events->getAllEventCategories() as $key => $category) : ?>
          <option value="<?= $key ?>"<?= ($filters['cat'] == $key)?' selected':'' ?>><?= $category ?></option>
        <?php endforeach; ?>
      </select>
      <select name="organisers" id="sd-filter-organisers">
        <option value="all"><?= JText::_("COM_SEMINARDESK_FILTER_ORGANISER_ALL");?></option>
        <option value="zegg"<?= ($filters['org'] == 'zegg')?' selected':'' ?>><?= JText::_("COM_SEMINARDESK_FILTER_ORGANISER_ZEGG");?></option>
        <option value="external"<?= ($filters['org'] == 'external')?' selected':'' ?>><?= JText::_("COM_SEMINARDESK_FILTER_ORGANISER_EXTERNAL");?></option>
      </select>
      <!--<button class="btn btn-secondary" type="submit"><?= JText::_("COM_SEMINARDESK_FILTER_SUBMIT");?></button>-->
    </form>
  </div>
  
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
              <h3><?= JText::sprintf( JHtml::_('date', $eventDate->beginDate, 'F Y')) ?></h3>
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
    <div class="no-events-found<?= ($this->events->getItems())?' hidden':'' ?>">
      <p><?= JText::_("COM_SEMINARDESK_EVENTS_NO_EVENTS_FOUND");?></p>
    </div>
  </div>
  
  <?php if ($document->countModules('below-events')): ?>
    <section class="below-events-container">
      <div class="row">
        <?= JHtml::_('content.prepare', '{loadposition below-events, column}') ?>
      </div>
    </section>
  <?php endif ; ?>

  <section class="event-infos-container" id="event-infos">
    <div class="row">
      <?= JHtml::_('content.prepare', '{loadposition event-infos, column}') ?>
    </div>
  </section>

</div>
