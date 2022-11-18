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
JHTML::_('behavior.modal');

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
  
  <?php if ($this->eventDates) : ?>
    <?php foreach($this->eventDates as $eventDate) : ?>
  
      <?php 
        //-- Month headings?
        $currentMonth = (int)date('m', $eventDate->beginDate);
        if ($currentMonth !== $previousEventMonth) { ?>
          <div class="sd-month-row">
            <h3><?= JText::sprintf( JHTML::_('date', $eventDate->beginDate, 'F Y')) ?></h3>
          </div>
          <?php
          $previousEventMonth = $currentMonth;
        }
      ?>
  
      <div class="sd-event">
        
        <?php if ($eventDate->registrationAvailable) : ?>
        <a class="modal registration-available" href="<?= $eventDate->booking_url ?>" rel="{handler: 'iframe'}">
        <?php else : ?>
        <a class="no-registration-available no-link" title="<?php echo JText::_("COM_SEMINARDESK_EVENTS_NO_REGISTRATION_AVAILABLE"); ?>">
        <?php endif; ?>
          
          <?php $sameYear = date('Y', $eventDate->beginDate) === date('Y', $eventDate->endDate); ?>
          <div class="sd-event-date <?= (!$sameYear)?' not-same-year':'' ?>">
            <?= $eventDate->dateFormatted; ?>
          </div>
          <div class="sd-event-title">
            <?= $eventDate->title; ?>
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
  <?php else : ?>
    <p><?php echo JText::_("COM_SEMINARDESK_EVENTS_NO_EVENTS_FOUND");?></p>
  <?php endif; ?>
</div>
