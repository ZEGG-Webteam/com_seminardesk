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

/**
 * HTML View class for the SeminarDesk Component
 *
 * @since  0.0.1
 */
class SeminarDeskViewSeminarDesk extends JViewLegacy
{
	/**
	 * Display the SeminarDesk view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 */
	function display($tpl = null)
	{
		// Assign data to the view
		$this->msg = 'SeminarDesk Home';

		// Display the view
		parent::display($tpl);
	}
}