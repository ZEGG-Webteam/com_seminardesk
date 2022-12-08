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

jimport('joomla.application.component.view');

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;

/**
 * View class for a list of SeminarDesk Events
 *
 * @since  1.6
 */
class SeminardeskViewEvents extends \Joomla\CMS\MVC\View\HtmlView
{
	protected $items;
	protected $pagination;
	protected $state;
	protected $params;

	/**
	 * Display the Events view
	 *
	 * @param   string  $tpl  Name of the template file to parse; automatically searches through the template paths.
	 * @return void
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		$app = Factory::getApplication();
		
		$this->state = $this->get('State');
		$this->params = $this->state->get('params');
		
    //-- Get key for translations from SeminarDesk (e.g. 'DE', 'EN')
    $this->langKey = SeminardeskHelperEvents::getCurrentLanguageKey();

    //-- Get SeminarDesk API settings
    $this->tenant_id = $app->input->get('tenant_id', 'zegg', 'STRING');

    // Configuration - To do: move into some propper configuration place
    $config = [
      'api' => 'https://' . $this->tenant_id . '.seminardesk.de/api',
      'booking_base' => 'https://booking.seminardesk.de/' . strtolower($this->langKey) . '/' . $this->tenant_id . '/',
    ];

    // Assign data to the view
    $this->title = $app->getMenu()->getActive()->title;
    $this->pageclass_sfx = htmlspecialchars($app->input->get('pageclass_sfx'), ENT_COMPAT, 'UTF-8');
    $this->eventDates = SeminardeskHelperEvents::getEventDates($config);
    $this->eventCategories = SeminardeskHelperEvents::getAllEventCategories($this->eventDates);

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		// Display the view
		$this->_prepareDocument();
		parent::display($tpl);
	}

	/**
	 * Prepares the document
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function _prepareDocument()
	{
		$app   = Factory::getApplication();
		$menus = $app->getMenu();
		$title = null;

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $menus->getActive();

		if ($menu)
		{
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		}
		else
		{
			$this->params->def('page_heading', Text::_('COM_SEMINARDESK_DEFAULT_PAGE_TITLE'));
		}

		$title = $this->params->get('page_title', '');

		if (empty($title))
		{
			$title = $app->get('sitename');
		}
		elseif ($app->get('sitename_pagetitles', 0) == 1)
		{
			$title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
		}
		elseif ($app->get('sitename_pagetitles', 0) == 2)
		{
			$title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
		}

		$this->document->setTitle($title);

		if ($this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}

		if ($this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}

		if ($this->params->get('robots'))
		{
			$this->document->setMetadata('robots', $this->params->get('robots'));
		}
    
	}

	/**
	 * Check if state is set
	 *
	 * @param   mixed  $state  State
	 * @return bool
	 */
	public function getState($state)
	{
		return isset($this->state->{$state}) ? $this->state->{$state} : false;
	}
}
