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
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Layout\LayoutHelper;

JHtml::_('behavior.modal'); // use with class="modal" and rel="{handler: 'iframe'}" in link

//-- Load CSS / JS
$document  = Factory::getDocument();
$document->addStyleSheet('/media/com_seminardesk/css/styles.css');
$document->addScript('/media/com_seminardesk/js/seminardesk.js');

?>

<div class="event-details" data-api-uri="<?= $this->event->apiUri ?>" data-lang-key="<?= $this->event->langKey ?>">
  <div id="header-picture"><img src="<?= $this->event->headerPictureUrl ?>"></div>
  <h1 id="title"><?= $this->event->title; ?></h1>
  <?php if ($this->event->subtitle) : ?>
    <h2 id="subtitle"><?= $this->event->subtitle; ?></h2>
  <?php endif; ?>
  <p id="teaser"><?= $this->event->teaser; ?></p>
  
  <?php if ($this->event->settings->registrationAvailable) : ?>
    <a href="<?= $this->event->booking_url ?>" class="btn modal" rel="{handler: 'iframe'}">
      Anmelden
    </a>
  <?php endif; ?>
  
  <div id="description" class="async loading">Loading description...<!-- load from API, because of inline images which are too big to be handled by joomla (regex) --></div>
  
  <?php if (count($this->event->facilitators) > 0) : ?>
    <div id="facilitators">
      <h2>Seminarleiter*innen</h2>
      <?php foreach($this->event->facilitators as $facilitator) : ?>
        <div class="fascilitator">
          <div class="fascilitator-picture"><img src="<?= $facilitator->pictureUrl ?>"></div>
          <div class="fascilitator-name"><?= $facilitator->title ?> <?= $facilitator->name ?></div>
          <div class="fascilitator-about"><?= $facilitator->about ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  
  <?php if (count($this->event->dates) > 0) : ?>
    <div id="dates">
      <h2>Daten</h2>
      <?php foreach($this->event->dates as $date) : ?>
        <div class="date">
          <div class="date-date"<?= $date->dateFormatted ?></div>
          <div class="date-title"><?= $date->title ?></div>
          <div class="date-status"><?= $date->statusLabel ?></div>
          
          <?php if ($this->event->settings->registrationAvailable && $date->registrationAvailable) : ?>
            <a href="<?= $date->booking_url ?>" class=" btn modal" rel="{handler: 'iframe'}">
              Anmelden
            </a>
          <?php endif; ?>
          
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  
  <div id="infoDatesPrices"><?= $this->event->infoDatesPrices; ?></div>
  <div id="infoBoardLodging"><?= $this->event->infoBoardLodging; ?></div>
  <div id="infoMisc"><?= $this->event->infoMisc; ?></div>

</div>

