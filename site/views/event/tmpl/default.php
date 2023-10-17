<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Seminardesk
 * @author     Benno Flory <benno.flory@gmx.ch>
 * @copyright  2022 Benno Flory
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Layout\LayoutHelper;

JHtml::_('jquery.framework');
JHtml::_('behavior.modal'); // use with class="modal" and rel="{handler: 'iframe'}" in link

$config = SeminardeskHelperData::getConfiguration();

//-- Load CSS / JS
$document  = Factory::getDocument();
$document->addStyleSheet('/media/com_seminardesk/css/styles.css');
$document->addScript('/media/com_seminardesk/js/seminardesk.js');

//-- Set document title
$title = str_replace(['&ndash;', '&amp;'], ['-', '&'], html_entity_decode($this->event->title));
$facilitators = implode(', ', array_column($this->event->facilitators, 'name'));
$document->setTitle($title . ' - ' . $facilitators);
?>

<div class="sd-component sd-event-details" data-api-uri="<?= $this->event->apiUri ?>" data-lang-key="<?= $this->event->langKey ?>">
  <div class="event-header">
    <?php if ($this->event->headerPictureUrl) : ?>
      <div class="header-picture"><img src="<?= $this->event->headerPictureUrl ?>"></div>
    <?php endif; ?>
    <h1 class="title"><?= $this->event->title; ?></h1>
    <?php if ($this->event->subtitle) : ?>
      <h2 class="subtitle"><?= $this->event->subtitle; ?></h2>
    <?php endif; ?>
    <div class="teaser"><?= $this->event->teaser; ?></div>
    <div class="dates-list"><?= implode(' / ', $this->event->datesList); ?></div>

    <?php if ($this->event->isBookable) : ?>
    <div class="registration">
      <a href="<?= $this->event->bookingUrl ?>" class="btn modal" rel="{handler: 'iframe'}"
         <?= ($this->event->isExternal)?(' title="' . JText::_("COM_SEMINARDESK_EVENT_REGISTRATION_A_M_FULL") . '"'):'' ?>>
        <?= JText::_("COM_SEMINARDESK_EVENT_REGISTRATION" . ($this->event->isExternal?"_A_M":"")); ?>
      </a>
    </div>
    <?php endif; ?>
  </div>

  <div class="event-details">
    <?php if ($this->event->catLinks) : ?>
      <div class="categories">
        <?= $this->event->catLinks; ?>
      </div>
    <?php endif; ?>
    
    <div id="description" class="description<?= $this->event->descriptionTooLong?' async loading"':''; ?>">
      <?= $this->event->description ?>
    </div>

    <?php if (count($this->event->dates) > 0) : ?>
      <h2><?= JText::_("COM_SEMINARDESK_EVENT_DATES_REGISTRATION"); ?></h2>

      <?php if ($this->event->infoDatesPrices) : ?>
        <div class="info-dates-prices"><?= $this->event->infoDatesPrices; ?></div>
      <?php endif; ?>
      <?php if ($this->event->infoBoardLodging) : ?>
        <div class="info-board-lodging"><?= $this->event->infoBoardLodging; ?></div>
      <?php endif; ?>

      <div class="dates">
        <?php $showRegNotice = !$this->event->settings->registrationAvailable ?>
        <?php foreach($this->event->dates as $date) : ?>
          <div class="date<?= $date->facilitatorLinks?' has-facilitators':'' ?>">
            <div class="date-date"><?= $date->dateFormatted ?></div>
            <div class="date-title"><?= $date->title ?></div>
            <div class="date-prices">
              <?php if ($date->attendanceFees) : ?>
                <?php if ($this->event->settings->showAttendanceFees) : ?>
                <div class="date-fees">
                    <?php foreach ($date->attendanceFees as $fee) : ?>
                      <?= $fee->name . ': <strong>' . (($fee->isSelfAssessment)?($fee->priceRangeFrom . '-' . $fee->priceRangeTo):$fee->priceDefault) . '€</strong><br>'; ?>
                    <?php endforeach; ?>
                  </div>
                  <div class="date-accom-meals"><?= JText::_("COM_SEMINARDESK_EVENT_ACC_MEALS_ADDITIONAL"); ?></div>
                <?php else : ?>
                  <div class="date-fees"><?= JText::_("COM_SEMINARDESK_EVENT_NO_FEES_TO_DISPLAY"); ?></div>
                <?php endif; ?>
              <?php elseif ($this->event->settings->onlyBoardAndLodging && ($date->availableLodging || $date->availableBoard)) : ?>
                <div class="date-accom-meals">
                  <?php if ($date->isExternal) : ?>
                    <?= JText::_("COM_SEMINARDESK_EVENT_ACC_MEALS_AVAILABLE_BOOKING"); ?>
                  <?php else : ?>
                    <?= JText::_("COM_SEMINARDESK_EVENT_ACC_MEALS_AVAILABLE"); ?>
                  <?php endif; ?>
                </div>
              <?php else : ?>
                <div class="date-accom-meals"><?= JText::_("COM_SEMINARDESK_EVENT_ACC_MEALS_FREE"); ?></div>
              <?php endif; ?>
            </div>
            
            <div class="date-facilitators"><?= $date->facilitatorLinks?$date->facilitatorLinks:''; ?></div>
            <div class="date-status"><?= $date->statusLabel ?></div>
            
            <div class="date-registration">
            <?php if ($date->isBookable) : ?>
              <a href="<?= $date->bookingUrl ?>" class="btn modal" rel="{handler: 'iframe'}"
                 <?= ($this->event->isExternal)?(' title="' . JText::_("COM_SEMINARDESK_EVENT_REGISTRATION_A_M_FULL") . '"'):'' ?>>
                <?= JText::_("COM_SEMINARDESK_EVENT_REGISTRATION" . ($date->isExternal?"_A_M":"")); ?>
              </a>
            <?php else : ?>
              *) <?php $showRegNotice = true; ?>
            <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if ($showRegNotice) : ?>
          <div class="no-registration">
            *) <?= JText::_("COM_SEMINARDESK_EVENT_NO_REGISTRATION"); ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($this->event->infoMisc) : ?>
      <div class="info-misc"><?= $this->event->infoMisc; ?></div>
    <?php endif; ?>

    <?php if (count($this->event->facilitators) > 0) : ?>
      <div class="sd-facilitators">
        <h2><?= JText::_("COM_SEMINARDESK_TITLE_FACILITATOR"); ?></h2>
        <?php foreach($this->event->facilitators as $facilitator) {
          $this->addTemplatePath(JPATH_COMPONENT . '/views/facilitators/tmpl');
          $facilitator->headings = "h3";
          $this->facilitator = $facilitator;
          echo $this->loadTemplate('facilitator');
        } ?>
      </div>
    <?php endif; ?>
  </div>
  
  <aside class="event-infos-container" id="event-infos">
    <div class="row">
      <?= JHtml::_('content.prepare', '{loadposition event-infos, column}') ?>
    </div>
  </aside>

</div>

