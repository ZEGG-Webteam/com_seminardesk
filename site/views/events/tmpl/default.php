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

JHtml::_('jquery.framework');
//JHTML::_('behavior.modal'); // use with class="modal" and rel="{handler: 'iframe'}" in link

$app = JFactory::getApplication();

//-- Load CSS / JS
$document  = JFactory::getDocument();
$document->addStyleSheet('/components/com_seminardesk/assets/css/styles.css');
$document->addScript('/components/com_seminardesk/assets/js/seminardesk.js');

$previousEventMonth = '';
?>
  
<div class="sd-events<?php echo ($this->pageclass_sfx)?' sd-events'.$this->pageclass_sfx:''; ?>">
  
	<?php // if ($app->input->get('show_page_heading')) : // to do: buggy ?>
		<div class="page-header">
			<h1><?= $this->escape($this->title) ?></h1>
		</div>
	<?php // endif; ?>
  
  <div class="btn-warning"><!-- temporary!! -->
    <?= JText::_("COM_SEMINARDESK_TEMP_WARNING");?>
  </div>
  
  <div class="sd-filter">
    <form class="sd-filter-form">
      <input type="date" name="filter_date_from" id="sd-filter-date-from" placeholder="<?= JText::_("COM_SEMINARDESK_FILTER_DATE_PLACEHOLDER");?>">
      <input type="text" name="filter_search_term" id="sd-filter-search-term" value="" placeholder="<?= JText::_("COM_SEMINARDESK_FILTER_TERM_PLACEHOLDER");?>">
      <button class="btn btn-secondary" type="submit">Suchen</button>
    </form>
  </div>
  
  <div class="sd-eventlist">
    <div class="sd-month">
      <?php foreach($this->eventDates as $eventDate) : ?>
        <?php 
        //-- New month heading?
        $currentMonth = (int)date('m', $eventDate->beginDate);
        if ($currentMonth !== $previousEventMonth) { 
          // Close last month container and open new one?>
          </div>
          <div class="sd-month">
            <div class="sd-month-row">
              <h3><?= JText::sprintf( JHTML::_('date', $eventDate->beginDate, 'F Y')) ?></h3>
            </div>
          <?php
          $previousEventMonth = $currentMonth;
        }
        
        //-- Add labels data and featured class for festivals
        $featuredClass = (in_array('Festival', array_column($eventDate->labels, 'name')))?' featured':'';
        $labelData = implode(',', array_column($eventDate->labels, 'name'));
        ?>
  
        <div class="sd-event" itemscope="itemscope" itemtype="https://schema.org/Event" 
             data-start-date="<?= date('Y-m-d', $eventDate->beginDate) ?>"
             data-title="<?= $eventDate->title ?>"
             data-fascilitators="<?= implode(' ', $eventDate->facilitators) ?>"
             data-labels="<?= $labelData ?>">

          <a href="<?= $eventDate->details_url ?>" target="seminardesk" itemprop="url" class="registration-available<?= $featuredClass ?>">
            <?php $sameYear = date('Y', $eventDate->beginDate) === date('Y', $eventDate->endDate); ?>
            <div class="sd-event-date <?= (!$sameYear)?' not-same-year':'' ?>">
              <time itemprop="startDate" 
                    datetime="<?= date('c', $eventDate->beginDate) ?>" 
                    content="<?= date('c', $eventDate->beginDate) ?>">
                <?= $eventDate->dateFormatted; ?>
              </time>
              <time itemprop="endDate" datetime="<?= date('c', $eventDate->endDate) ?>"></time>
            </div>
            <div class="sd-event-title">
              <?= $eventDate->fullTitle; ?>
            </div>
            <div class="sd-event-facilitators">
              <?= implode(', ', $eventDate->facilitators); ?>
            </div>
            <div class="sd-event-registration">
              <?= $eventDate->statusLabel; ?>
            </div>

          </a>

        </div>
      <?php endforeach; ?>
    </div>
    <div class="no-events-found<?= ($this->eventDates)?' hidden':'' ?>">
      <p><?= JText::_("COM_SEMINARDESK_EVENTS_NO_EVENTS_FOUND");?></p>
    </div>
  </div>
</div>
