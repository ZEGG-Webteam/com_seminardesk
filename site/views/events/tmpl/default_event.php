<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Seminardesk
 * @author     Benno Flory <benno.flory@gmx.ch>
 * @copyright  2022 Benno Flory
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

//-- Matching current filter? => Hide event if no
$matchingFilters = SeminardeskDataHelper::matchingFilters($this->eventDate, $this->filters);
?>
<div class="sd-event loading<?= (!$matchingFilters)?' hidden':'' ?>" 
     itemscope itemtype="https://schema.org/Event" 
     data-start-date="<?= date('Y-m-d', $this->eventDate->beginDate) ?>"
     data-end-date="<?= date('Y-m-d', $this->eventDate->endDate) ?>"
     data-categories='<?= json_encode(array_keys($this->eventDate->categories)); ?>'
     data-labels="<?= $this->eventDate->labelsList ?>"
     data-languages="<?= $this->eventDate->languageList ?>"
     data-searchable-text="<?= $this->eventDate->searchableText ?>">
  <a <?= ($this->eventDate->detailpageAvailable)?('href="' . $this->eventDate->detailsUrl . '" itemprop="url" target="_parent" '):'' ?>class="<?= $this->eventDate->cssClasses ?>">
    <?php $sameYear = date('Y', $this->eventDate->beginDate) === date('Y', $this->eventDate->endDate); ?>
    <div class="sd-event-date <?= (!$sameYear)?' not-same-year':'' ?>">
      <time itemprop="startDate" 
            datetime="<?= date('c', $this->eventDate->beginDate) ?>" 
            content="<?= date('c', $this->eventDate->beginDate) ?>">
        <?= $this->eventDate->dateFormatted; ?>
      </time>
      <time itemprop="endDate" datetime="<?= date('c', $this->eventDate->endDate) ?>"></time>
    </div>
    <div class="sd-event-title">
      <h4 itemprop="name"><?= $this->eventDate->title; ?></h4>
      <?php if ($this->eventDate->mergedSubtitle) : ?>
        <p><?= $this->eventDate->mergedSubtitle ?></p>
      <?php endif; ?>
    </div>
    <div class="sd-event-facilitators" itemprop="organizer">
      <?= $this->eventDate->facilitatorsList; ?>
    </div>
    <div class="sd-event-categories">
      <!--<?= $this->eventDate->categoriesList; ?> <!-- hide categories for now -->
    </div>
    <div class="sd-event-registration">
      <?php if ($this->eventDate->endDate > time()) : ?>
        <?= $this->eventDate->statusLabel; ?> <!-- show status for future events only -->
      <?php endif; ?>
    </div>
    <div class="sd-event-external">
      <?= ($this->eventDate->isExternal)?JText::_("COM_SEMINARDESK_EVENTS_LABEL_EXTERNAL_EVENT"):''; ?>
    </div>
    <div class="sd-event-location hidden" itemprop="location" itemscope itemtype="https://schema.org/Place">
      <span itemprop="name">ZEGG Bildungszentrum gGmbH</span>
      <div class="address" itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
        <span itemprop="streetAddress">Rosa-Luxemburg-Strasse 89</span><br>
        <span itemprop="postalCode">14806</span> <span itemprop="addressLocality">Bad Belzig</span>, <span itemprop="addressCountry">DE</span>
      </div>
    </div>
  </a>

</div>
