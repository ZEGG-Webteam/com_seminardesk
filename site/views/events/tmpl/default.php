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
//JHtml::_('behavior.modal'); // use with class="modal" and rel="{handler: 'iframe'}" in link

$app = Factory::getApplication();

//-- Load CSS / JS
$document  = Factory::getDocument();
$document->addStyleSheet('/media/com_seminardesk/css/styles.css');
$document->addScript('/media/com_seminardesk/js/seminardesk.js');

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
      <input type="date" name="from" id="sd-filter-date-from" placeholder="<?= JText::_("COM_SEMINARDESK_FILTER_DATE_PLACEHOLDER");?>">
      <input type="text" name="term" id="sd-filter-search-term" value="" placeholder="<?= JText::_("COM_SEMINARDESK_FILTER_TERM_PLACEHOLDER");?>">
      <select name="category" id="sd-filter-category">
        <option value="0"><?= JText::_("COM_SEMINARDESK_FILTER_CATEGORY_ALL");?></option>
        <?php foreach($this->events->getAllEventCategories() as $key => $category) : ?>
          <option value="<?= $key ?>"><?= $category ?></option>
        <?php endforeach; ?>
      </select>
      <select name="organisers" id="sd-filter-organisers">
        <option value="all"><?= JText::_("COM_SEMINARDESK_FILTER_ORGANISER_ALL");?></option>
        <option value="zegg"><?= JText::_("COM_SEMINARDESK_FILTER_ORGANISER_ZEGG");?></option>
        <option value="external"><?= JText::_("COM_SEMINARDESK_FILTER_ORGANISER_EXTERNAL");?></option>
      </select>
      <!--<button class="btn btn-secondary" type="submit"><?= JText::_("COM_SEMINARDESK_FILTER_SUBMIT");?></button>-->
    </form>
  </div>
  
  <div class="sd-eventlist">
    <div class="sd-month">
      <?php foreach($this->events->getItems() as $eventDate) : ?>
        <?php 
        //-- New month heading?
        $currentMonth = (int)date('m', $eventDate->beginDate);
        if ($currentMonth !== $previousEventMonth) { 
          // Close last month container and open new one?>
          </div>
          <div class="sd-month">
            <div class="sd-month-row">
              <h3><?= JText::sprintf( JHtml::_('date', $eventDate->beginDate, 'F Y')) ?></h3>
            </div>
          <?php
          $previousEventMonth = $currentMonth;
        }
        //-- Set event classes
        $eventClasses = ['registration-available'];
        if ($eventDate->isFeatured)       { $eventClasses[] = 'featured';         }
        if ($eventDate->categoriesList)   { $eventClasses[] = 'has-categories';   }
        if ($eventDate->facilitatorsList) { $eventClasses[] = 'has-facilitators'; }
        if ($eventDate->isExternal)       { $eventClasses[] = 'external-event';   } 
        if (!$eventDate->isExternal)      { $eventClasses[] = 'zegg-event';       }
        ?>
  
        <div class="sd-event" itemscope="itemscope" itemtype="https://schema.org/Event" 
             data-start-date="<?= date('Y-m-d', $eventDate->beginDate) ?>"
             data-title="<?= $eventDate->title . (($eventDate->showDateTitle)?(' ' . $eventDate->eventDateTitle):'') ?>"
             data-fascilitators="<?= $eventDate->facilitatorsList ?>"
             data-categories='<?= json_encode(array_keys($eventDate->categories)); ?>'
             data-labels="<?= $eventDate->labelsList ?>">

          <a href="<?= $eventDate->details_url ?>" rel="nofollow" target="seminardesk" itemprop="url" class="<?= implode(' ', $eventClasses); ?>">
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
              <h4><?= $eventDate->title; ?></h4>
              <?= ($eventDate->showDateTitle)?('<p>' . $eventDate->eventDateTitle . '</p>'):'' ?>
            </div>
            <div class="sd-event-facilitators">
              <?= $eventDate->facilitatorsList; ?>
            </div>
            <div class="sd-event-categories">
              <?= $eventDate->categoriesList; ?>
            </div>
            <div class="sd-event-registration">
              <?= $eventDate->statusLabel; ?>
            </div>
            <div class="sd-event-external">
              <?= ($eventDate->isExternal)?JText::_("COM_SEMINARDESK_EVENTS_LABEL_EXTERNAL"):''; ?>
            </div>

          </a>

        </div>
      <?php endforeach; ?>
    </div>
    <div class="no-events-found<?= ($this->events->getItems())?' hidden':'' ?>">
      <p><?= JText::_("COM_SEMINARDESK_EVENTS_NO_EVENTS_FOUND");?></p>
    </div>
  </div>
</div>
