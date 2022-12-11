<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Seminardesk
 * @author     Benno Flory <benno.flory@gmx.ch>
 * @copyright  2022 Benno Flory
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Helper\TagsHelper;
use \Joomla\CMS\Layout\FileLayout;
use \Joomla\Utilities\ArrayHelper;

/**
 * Methods supporting a list of Seminardesk records. Reflecting EventDates
 *
 * @since  1.6
 */
class SeminardeskModelEvents extends \Joomla\CMS\MVC\Model\ListModel
{
  /** Custom config, other than https://api.joomla.org/cms-3/classes/Joomla.CMS.MVC.Model.ListModel.html
   *
   * @var array - ['api', 'booking_base', 'langKey'] // URL of the SeminarDesk API, base URL to events booking
   */
  protected $config = [];
  
  /**
   * Array of all eventDates loaded from SeminarDesk API
   * 
   * @var array
   */
  protected $eventDates = [];
  
  /**
   * Collected categories from all events
   * 
   * @var type 
   */
  protected $categories = [];
  
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see        JController
	 * @since      1.6
	 */
	public function __construct($config = array())
	{
    if (empty($config['filter_fields']))
    {
      $config['filter_fields'] = array(

      );
    }

    parent::__construct($config);

    $this->config = $config;
	}

        
        
	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   Elements order
	 * @param   string  $direction  Order direction
	 *
	 * @return void
	 *
	 * @throws Exception
	 *
	 * @since    1.6
	 */
	protected function populateState($ordering = null, $direction = null)
	{
    $app  = Factory::getApplication();

    // List state information.
    parent::populateState($ordering, $direction);

    $context = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
    $this->setState('filter.search', $context);

    // Load the parameters.
    $params = $app->getParams();
    $this->setState('params', $params);

    // Split context into component and optional section
    $parts = FieldsHelper::extract($context);

    if ($parts)
    {
        $this->setState('filter.component', $parts[0]);
        $this->setState('filter.section', $parts[1]);
    }
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   JDatabaseQuery
	 *
	 * @since    1.6
	 */
	protected function getListQuery()
	{
		$db	= $this->getDbo();
		$query	= $db->getQuery(true);

	 return $query;
	}

  /**
   * Preprocess / prepare fields of event date for use in views
   * 
   * @param object $eventDate
   * @param string $landKey
   * @param array $config containing key 'booking_base'
   * @return object - $eventDate with preprocessed fields
   */
  public function prepareEventDate(&$eventDate)
  {
    $eventDate->title = htmlentities(SeminardeskHelperEvents::getValueByLanguage($eventDate->title, $this->config['langKey']), ENT_QUOTES);
    $eventDate->eventDateTitle = htmlentities(SeminardeskHelperEvents::getValueByLanguage($eventDate->eventDateTitle, $this->config['langKey']), ENT_QUOTES);
    $eventDate->facilitators = array_combine(
      array_column($eventDate->facilitators, 'id'), 
      array_column($eventDate->facilitators, 'name')
    );
    $eventDate->facilitatorsList = htmlentities(implode(', ', $eventDate->facilitators), ENT_QUOTES);
    $eventDate->labels = array_combine(
      array_column($eventDate->labels, 'id'), 
      array_column($eventDate->labels, 'name')
    );
    $eventDate->labelsList = htmlentities(implode(', ', $eventDate->labels), ENT_QUOTES);
    // Get categories = labels except LABELS_TO_HIDE
    $eventDate->categories = array_filter($eventDate->labels, function($key){
      return !in_array($key, SeminardeskHelperEvents::LABELS_TO_HIDE);
    }, ARRAY_FILTER_USE_KEY);
    $eventDate->categoriesList = htmlentities(implode(', ', $eventDate->categories), ENT_QUOTES);
//    $eventDate->categoryLinks = implode(', ', SeminardeskHelperEvents::getCategoryLinks($eventDate->categories));
    $eventDate->statusLabel = htmlentities($eventDate->statusLabel, ENT_QUOTES);

    //-- Set special event flags (festivals, external organisers)
    $eventDate->isFeatured = array_key_exists(SeminardeskHelperEvents::LABELS_FESTIVALS_ID, $eventDate->labels);
    $eventDate->isExternal = array_key_exists(SeminardeskHelperEvents::LABELS_EXTERNAL_ID, $eventDate->labels);
    $eventDate->showDateTitle = ($eventDate->eventDateTitle && $eventDate->eventDateTitle != $eventDate->title);

    //-- Format date
    $eventDate->beginDate = $eventDate->beginDate / 1000;
    $eventDate->endDate = $eventDate->endDate / 1000;
    $eventDate->dateFormatted = SeminardeskHelperEvents::getDateFormatted($eventDate->beginDate, $eventDate->endDate);

    //-- Booking
    $eventDate->details_url = SeminardeskHelperEvents::getDetailsUrl($eventDate, $config);
//    $eventDate->booking_url = SeminardeskHelperEvents::getBookingUrl($eventDate, $config);
    $eventDate->statusLabel = SeminardeskHelperEvents::getStatusLabel($eventDate);
  }
  
  /**
   * Load EventDates from SeminarDesk API
   *
   * @return  array Event Dates (objects)
   *
   * @since   3.0
   */
  public function loadEventDates()
  {
    $eventDatesData = SeminardeskHelperEvents::getSeminarDeskData($this->config, '/EventDates');
    
    if (is_object($eventDatesData) && $eventDatesData) {
      $eventDates = json_decode($eventDatesData->body)->dates;
      
      //-- Get values in current language, with fallback to first language in set
      foreach ($eventDates as $key => &$eventDate) {
        $this->prepareEventDate($eventDate, $this->config, $config['langKey']);
      }
    }
    else {
      // Error handling
      JLog::add(
        'loadEventDates() failed! ($eventDatesData = ' . json_encode($eventDatesData) . ')', 
        JLog::ERROR, 
        'com_seminardesk'
      );
      $eventDates = [];
    }
    return $eventDates;
  }
  
	/**
	 * Method to get an array of data items
	 *
	 * @return  mixed An array of data on success, false on failure.
	 */
	public function getItems()
	{
//		$items = parent::getItems();
    // If not yet loaded: Get events from API
    if (!$this->eventDates) {
      $this->eventDates = $this->loadEventDates();
    }

		return $this->eventDates;
	}

  /**
   * Get status label in current language, or untranslated, but readable, if no 
   * translation has been found (e.g. fully_booked => Fully Booked), 
   * or empty, if no status is set. 
   * 
   * @param array $eventDates
   * @return array - Collected categories of all events
   */
  public function getAllEventCategories()
  {
    // If not yet populated: Build categories array
    if (!$this->categories) {
      $eventDates = $this->getItems();
      foreach($eventDates as $eventDate) {
        $this->categories += $eventDate->categories;
      }
      $this->categories = array_unique($this->categories);
      asort($this->categories);
    }
    
    return $this->categories;
  }
  
	/**
	 * Overrides the default function to check Date fields format, identified by
	 * "_dateformat" suffix, and erases the field if it's not correct.
	 *
	 * @return void
	 */
	protected function loadFormData()
	{
		$app              = Factory::getApplication();
		$filters          = $app->getUserState($this->context . '.filter', array());
		$error_dateformat = false;

		foreach ($filters as $key => $value)
		{
			if (strpos($key, '_dateformat') && !empty($value) && $this->isValidDate($value) == null)
			{
				$filters[$key]    = '';
				$error_dateformat = true;
			}
		}

		if ($error_dateformat)
		{
			$app->enqueueMessage(Text::_("COM_SEMINARDESK_SEARCH_FILTER_DATE_FORMAT"), "warning");
			$app->setUserState($this->context . '.filter', $filters);
		}

		return parent::loadFormData();
	}

	/**
	 * Checks if a given date is valid and in a specified format (YYYY-MM-DD)
	 *
	 * @param   string  $date  Date to be checked
	 *
	 * @return bool
	 */
	private function isValidDate($date)
	{
		$date = str_replace('/', '-', $date);
		return (date_create($date)) ? Factory::getDate($date)->format("Y-m-d") : null;
	}
}
