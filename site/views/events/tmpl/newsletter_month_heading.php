<?php

/**
 * @package    Seminardesk
 * @subpackage com_seminardesk
 * @author     Benno Flory <benno.flory@gmx.ch>
 * @copyright  2022 Benno Flory
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;

?>
	<tr>
		<td colspan="2"><br />
		<font color="#575757">
			<strong><?= JText::sprintf( JHtml::_('date', $this->eventDate->beginDate, 'F Y')) ?></strong>
		</font><br />&nbsp;</td>
	</tr>
