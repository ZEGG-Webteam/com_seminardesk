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
$classes = implode(' ', ['facilitator', $this->facilitator->classes ?? '']);
$heading = $this->facilitator->heading ?? 'h2';
//echo json_encode('<br>' . $classes);
//echo json_encode($this->facilitator);
//die();
?>

<div class="<?= $classes ?>" itemscope="itemscope" itemtype="https://schema.org/Person">

  <div class="facilitator-picture">
    <?= ($this->facilitator->pictureUrl)?'<img src="' . $this->facilitator->pictureUrl . '" alt="' . $this->facilitator->fullName . '">':'' ?>
  </div>
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