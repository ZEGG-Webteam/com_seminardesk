<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Seminardesk
 * @author     Benno Flory <benno.flory@gmx.ch>
 * @copyright  2022 Benno Flory
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

//$sameYear = date('Y', $this->eventDate->beginDate) === date('Y', $this->eventDate->endDate);
?>
<tr>
	<td><font color="#575757"><?= str_replace(' ', '', strip_tags($this->eventDate->dateFormatted)); ?></font></td>
	<td><font color="#575757">
    <a href="<?= JUri::base() . ltrim($this->eventDate->detailsUrl, '/'); ?>"><?= $this->eventDate->title; ?></a><br>
    <?= ($this->eventDate->mergedSubtitle)?($this->eventDate->mergedSubtitle . '<br>'):'' ?>
    <?= $this->eventDate->facilitatorsList; ?>
  </font></td>
</tr>
