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
class SeminarDeskViewEvents extends JViewLegacy
{
	/**
	 * Display the Events view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 */
	function display($tpl = null)
	{
    $app = JFactory::getApplication();
    
    //-- Get key for translations from SeminarDesk (e.g. 'DE', 'EN')
    $this->langKey = SeminardeskHelperEvents::getCurrentLanguageKey();
    
    //-- Get SeminarDesk API settings
    $this->tenant_id = $app->input->get('tenant_id', 'zegg', 'STRING');
    
    // Configuration - To do: move into some propper configuration place
    $config = [
      'api' => 'https://' . $this->tenant_id . '.seminardesk.de/api',
      'booking_base' => 'https://booking.seminardesk.de/' . $this->tenant_id . '/',
    ];

		// Assign data to the view
    $this->title = $app->getMenu()->getActive()->title;
    $this->pageclass_sfx = htmlspecialchars($app->input->get('pageclass_sfx'), ENT_COMPAT, 'UTF-8');
    $this->eventDates = SeminardeskHelperEvents::getEventDates($config);

		// Display the view
		parent::display($tpl);
	}
}