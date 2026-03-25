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

$app = Factory::getApplication();
$config = SeminardeskDataHelper::getConfiguration();

//-- Load CSS / JS via WebAssetManager
$wa = $app->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('com_seminardesk.styles', 'com_seminardesk/css/styles.css');
$wa->registerAndUseScript('com_seminardesk.script', 'com_seminardesk/js/seminardesk.js');

$document = $app->getDocument();

//-- Set document title
$document->setTitle($this->facilitator->fullName);

// Add template path for sub-templates (event)
$this->addTemplatePath(JPATH_COMPONENT . '/src/View/Events/tmpl');
?>

<div class="sd-component sd-facilitator-details">
  <div class="backlink">
    <a href="#"><i class="fas fa-chevron-left"></i><?= Text::_("COM_SEMINARDESK_BACK") ?></a>
  </div>
  <div class="facilitator" itemscope itemtype="https://schema.org/Person">

    <div class="facilitator-name">
      <h1><?= $this->facilitator->title ?> <span itemprop="givenName"><?= $this->facilitator->firstName ?></span> <span itemprop="familyName"><?= $this->facilitator->lastName ?></span></h1>
    </div>
    <div class="facilitator-picture">
      <?= ($this->facilitator->pictureUrl)?'<img src="' . $this->facilitator->pictureUrl . '" alt="' . $this->facilitator->fullName . '">':'' ?>
    </div>
    <div class="facilitator-about">
      <?= $this->facilitator->about ?>
    </div>
    
    <?php if ($this->facilitator->eventDates) : ?>
      <div class="sd-events">
        <h2><?= Text::_("COM_SEMINARDESK_TITLE_ITEM_VIEW_FACILITATOR_EVENTS") . ' ' . $this->facilitator->fullName ?></h2>
      <?php 
        foreach($this->facilitator->eventDates as $eventDate) {
          // Load event item using sub-template
          $this->eventDate = & $eventDate;
          $this->filters = [];
          echo $this->loadTemplate('event');
        }
      ?>
      </div>
    <?php endif; ?>
    
    <?php if ($this->facilitator->pastEventDates) : ?>
      <div class="sd-events">
        <?php if (!$this->facilitator->eventDates) : ?>
          <h2><?= Text::_("COM_SEMINARDESK_TITLE_ITEM_VIEW_FACILITATOR_PAST_EVENTS_WITH") . ' ' . $this->facilitator->fullName ?></h2>
        <?php else : ?>
          <h3><?= Text::_("COM_SEMINARDESK_TITLE_ITEM_VIEW_FACILITATOR_PAST_EVENTS") ?></h3>
        <?php endif; ?>
      <?php 
        foreach($this->facilitator->pastEventDates as $eventDate) {
          // Load event item using sub-template
          $this->eventDate = & $eventDate;
          $this->filters = [];
          echo $this->loadTemplate('event');
        }
      ?>
      </div>
    <?php endif; ?>
  
  </div>
</div>

