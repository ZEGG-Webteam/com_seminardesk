<?php
/**
 * Render the event list as a HTML table for newsletters (with AcyMailing 5 on newletter.zegg.de)
 * Usage: Call /de/veranstaltungen/programm?layout=newsletter and follow instructions.
 * 
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

$app = Factory::getApplication();

//-- Load CSS / JS
$document  = Factory::getDocument();
$document->addStyleSheet('/media/com_seminardesk/css/styles.css');

$previousEventMonth = '';
?>

<div class="sd-component sd-events sd-newsletter">
	<h2>Veranstaltungen für den Newsletter</h2>
	<h3>Anleitung:</h3>
	<ul>
		<li>Seitenquelltext anzeigen (nicht "Untersuchen") und im Abschnitt von &lt;table&gt; bis &lt;/table&gt; die gewünschten Monate in den Newsletter-Editor (Quellcode) von AcyMailing kopieren.</li>
		<li>Events löschen, welche nicht in den NL sollen, z.B. "ZEGG Saisonierzeit" - von &lt;tr&gt; bis &lt;/tr&gt;</li>
		<li>Quellcode schliessen mit "OK"</li>
		<li>Alle Festivals fett formatieren</li>
		<li>Testversand und Links testen - Fertig!</li>
	</ul>
<table>
	<tbody>
<?php 
		foreach($this->events->getItems() as $eventDate) {
			$this->eventDate = & $eventDate;

			//-- New month heading?
			$currentMonth = (int)date('m', $eventDate->beginDate);
			if ($currentMonth !== $previousEventMonth) {
				echo $this->loadTemplate('month_heading');
				$previousEventMonth = $currentMonth;
			}
			if ($eventDate->statusLabel !== 'Abgesagt') {
				echo $this->loadTemplate('event');
			}
		}
?>
		<tr>
			<td></td>
			<td><font color="#575757">&nbsp;</font></td>
		</tr>
		<tr>
			<td></td>
			<td><font color="#575757">&raquo; <a href="https://www.zegg.de/de/veranstaltungen/programm">Alle Seminare</a></font><br />
			&nbsp;</td>
		</tr>
	</tbody>
</table>
</div>
