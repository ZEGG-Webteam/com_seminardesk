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

//JHtml::_('behavior.modal'); // use with class="modal" and rel="{handler: 'iframe'}" in link
$heading = $this->facilitator->heading ?? 'h2';
?>

<div class="<?= $this->facilitator->cssClasses ?>" itemscope="itemscope" itemtype="https://schema.org/Person">

  <a class="facilitator-picture noicon wfpopup" href="<?= $this->facilitator->detailsUrl ?>" itemprop="url" rel="{handler: 'iframe'}">
    <?= ($this->facilitator->pictureUrl)?'<img src="' . $this->facilitator->pictureUrl . '" alt="' . $this->facilitator->fullName . '">':'' ?>
  </a>
  <div class="facilitator-description">
    <<?= $heading ?> class="facilitator-name"><?= $this->facilitator->title ?> <span itemprop="givenName"><?= $this->facilitator->firstName ?></span> <span itemprop="familyName"><?= $this->facilitator->lastName ?></span></<?= $heading ?>>
    <div class="facilitator-about">
      <?= SeminardeskHelperData::cleanupHtml($this->facilitator->about, '<p><br>') ?>
    </div>
  </div>
  <div class="facilitator-details">
    <a href="<?= $this->facilitator->detailsUrl ?>" itemprop="url" class="readmore noicon wfpopup" rel="{handler: 'iframe'}">
      <i class="fas fa-chevron-right"></i>
      <?= JText::_("COM_SEMINARDESK_FACILITATORS_READMORE"); ?>
    </a>
  </div>

</div>