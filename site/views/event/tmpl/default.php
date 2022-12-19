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

JHtml::_('behavior.modal'); // use with class="modal" and rel="{handler: 'iframe'}" in link

$config = SeminardeskHelperData::getConfiguration();

//-- Load CSS / JS
$document  = Factory::getDocument();
$document->addStyleSheet('/media/com_seminardesk/css/styles.css');
$document->addScript('/media/com_seminardesk/js/seminardesk.js');

//-- Set document title
$title = str_replace(['&ndash;', '&amp;'], ['-', '&'], $this->event->title);
$facilitators = implode(', ', array_column($this->event->facilitators, 'name'));
$document->setTitle($title . ' - ' . $facilitators);
?>

<div class="sd-event-details" data-api-uri="<?= $this->event->apiUri ?>" data-lang-key="<?= $this->event->langKey ?>">
  <?php if ($this->event->headerPictureUrl) : ?>
    <div id="header-picture"><img src="<?= $this->event->headerPictureUrl ?>"></div>
  <?php endif; ?>
  <h1 id="title"><?= $this->event->title; ?></h1>
  <?php if ($this->event->subtitle) : ?>
    <h2 id="subtitle"><?= $this->event->subtitle; ?></h2>
  <?php endif; ?>
  <div id="teaser"><?= $this->event->teaser; ?></div>
  
  <?php if ($this->event->settings->registrationAvailable) : ?>
  <div class="registration">
    <a href="<?= $this->event->booking_url ?>" class="btn modal" rel="{handler: 'iframe'}">
      <?= JText::_("COM_SEMINARDESK_EVENT_REGISTRATION"); ?>
    </a>
  </div>
  <?php endif; ?>
  
  <?php if ($this->event->catLinks) : ?>
    <div id="categories">
      <?= $this->event->catLinks; ?>
    </div>
  <?php endif; ?>
  
  <div id="description"<?= $this->event->descriptionTooLong?' class="async loading"':''; ?>>
    <?= $this->event->description ?>
  </div>
  
  <?php if (count($this->event->facilitators) > 0) : ?>
    <div id="facilitators">
      <h2><?= JText::_("COM_SEMINARDESK_EVENT_FACILITATORS"); ?></h2>
      <?php foreach($this->event->facilitators as $facilitator) : ?>
        <div class="fascilitator">
          <div class="fascilitator-picture"><img src="<?= $facilitator->pictureUrl ?>"></div>
          <div class="fascilitator-name"><h3><?= $facilitator->title . ' ' . $facilitator->name ?></h3></div>
          <div class="fascilitator-about"><?= $facilitator->about ?></div>
          <?php if (strlen($facilitator->about) > 300) : ?>
            <div class="readmore"><span><i class="fas fa-chevron-down"></i>
                <span class="readmore-label"><?= JText::_("COM_SEMINARDESK_EVENT_READMORE"); ?></span>
                <span class="readless-label"><?= JText::_("COM_SEMINARDESK_EVENT_READLESS"); ?></span>                  
              </span></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  
  <?php if (count($this->event->dates) > 0) : ?>
    <h2><?= JText::_("COM_SEMINARDESK_EVENT_DATES_REGISTRATION"); ?></h2>

    <div id="infoDatesPrices"><?= $this->event->infoDatesPrices; ?></div>
    <div id="infoBoardLodging"><?= $this->event->infoBoardLodging; ?></div>

    <div id="dates">
      <?php $regAvaliable = ($this->event->settings->registrationAvailable)?'all':'none'; ?>
      <?php foreach($this->event->dates as $date) : ?>
        <div class="date">
          <div class="date-date"><?= $date->dateFormatted ?></div>
          <div class="date-title"><?= $date->title ?></div>
          <div class="date-status"><?= $date->statusLabel ?></div>

          <?php if ($this->event->settings->registrationAvailable) : ?>
          <div class="date-registration">
            <?php if ($date->registrationAvailable) : ?>
            <a href="<?= $date->booking_url ?>" class="btn modal" rel="{handler: 'iframe'}">
              <?= JText::_("COM_SEMINARDESK_EVENT_REGISTRATION"); ?>
            </a>
            <?php else : ?>
              *<?php $regAvaliable = 'some'; ?>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      <?php if ($regAvaliable != 'all') : ?>
        <div class="no-registration">
          <?= (($regAvaliable == 'some')?'* ':'') . JText::_("COM_SEMINARDESK_EVENT_NO_REGISTRATION"); ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
  
  <div id="infoBoardLodging"><?= $this->event->infoBoardLodging; ?></div>
  <div id="infoMisc"><?= $this->event->infoMisc; ?></div>
  
  <?php if ($this->event->settings->registrationAvailable) : ?>
    <!--<iframe id="registration-iframe" src="<?= $this->event->booking_url ?>"></iframe>-->
  <?php endif; ?>

</div>

