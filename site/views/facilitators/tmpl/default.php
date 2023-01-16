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
?>

<div class="sd-component sd-facilitators<?php echo ($this->pageclass_sfx)?' sd-facilitators'.$this->pageclass_sfx:''; ?>">
  
	<?php // if ($app->input->get('show_page_heading')) : // to do: buggy ?>
		<div class="page-header">
			<h1><?= $this->escape($this->title) ?></h1>
		</div>
	<?php // endif; ?>
  
  <div class="sd-filter">
    <form class="sd-filter-form">
      <input type="text" name="term" id="sd-filter-search-term" value="" placeholder="<?= JText::_("COM_SEMINARDESK_FILTER_FACILITATOR_PLACEHOLDER");?>">
      <!--<button class="btn btn-secondary" type="submit"><?= JText::_("COM_SEMINARDESK_FILTER_SUBMIT");?></button>-->
    </form>
  </div>
  
  <div class="sd-facilitatorlist container">
    <div class="row">
    <?php foreach($this->facilitators->getItems() as $facilitator) : ?>
      <div class="facilitator col-lg-6" 
           itemscope="itemscope" 
           itemtype="https://schema.org/Person">

        <a href="<?= $facilitator->detailsUrl ?>" itemprop="url">
          <div class="facilitator-picture">
            <?= ($facilitator->pictureUrl)?'<img src="' . $facilitator->pictureUrl . '" alt="' . $facilitator->fullName . '">':'' ?>
          </div>
          <div class="facilitator-description">
            <h4 class="facilitator-name"><?= $facilitator->title ?> <span itemprop="givenName"><?= $facilitator->firstName ?></span> <span itemprop="familyName"><?= $facilitator->lastName ?></span></h4>
            <div class="facilitator-about">
              <?= SeminardeskHelperData::cleanupHtml($facilitator->about, '<p><br>') ?>
            </div>
          </div>
          <div class="readmore">
            <span><i class="fas fa-chevron-right"></i>
              <span class="readmore-label"><?= JText::_("COM_SEMINARDESK_FACILITATORS_READMORE"); ?></span>
              <span class="readless-label"><?= JText::_("COM_SEMINARDESK_FACILITATORS_READLESS"); ?></span>                  
            </span>
          </div>
        </a>

      </div>
    <?php endforeach; ?>
    </div>
    <div class="no-facilitators-found<?= ($this->facilitators->getItems())?' hidden':'' ?>">
      <p><?= JText::_("COM_SEMINARDESK_FACILITATORS_NO_FACILITATORS_FOUND");?></p>
    </div>
  </div>

</div>
