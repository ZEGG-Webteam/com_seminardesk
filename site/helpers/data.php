<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Seminardesk
 * @author     Benno Flory <benno.flory@gmx.ch>
 * @copyright  2022 Benno Flory
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Log\Log;

/**
 * Class SeminardeskHelperData: Helper for Seminardesk Data Handling
 *
 * @since  3.0
 */
class SeminardeskHelperData
{
  /**
   * Configurations
   */
  const LABELS_FESTIVALS_ID = 12;
  const LABELS_EXTERNAL_ID = 55;
  const LABELS_TO_HIDE = [
      1, 2, 3, 53, 54, // Languages
      self::LABELS_FESTIVALS_ID, 
      self::LABELS_EXTERNAL_ID, 
  ];
  
  /** Custom config, other than https://api.joomla.org/cms-3/classes/Joomla.CMS.MVC.Model.ListModel.html
   *
   * @var array - ['tenant_id', 'langKey', 'api', 'booking_base'] // URL of the SeminarDesk API, base URL to events booking
   */
  protected static $config = [];
  
  /**
   * 
   */
  public static function getConfiguration()
  {
    if (!self::$config) {
      $app  = Factory::getApplication();

      //-- Get key for translations from SeminarDesk (e.g. 'DE', 'EN')
      $langKey = self::getCurrentLanguageKey();

      //-- Get SeminarDesk API settings
      $tenant_id = $app->input->get('tenant_id', 'zegg', 'STRING');

      // Configuration - To do: move into some propper configuration place
      self::$config = [
        'tenant_id' => $tenant_id,
        'langKey' => $langKey,
        'api' => 'https://' . $tenant_id . '.seminardesk.de/api',
        'booking_base' => 'https://booking.seminardesk.de/' . strtolower($langKey) . '/' . $tenant_id . '/',
      ];
    }
    
    return self::$config;
  }

  /**
   * Get short language code in UC letters, e.g. 'DE' or 'EN'
   * 
   * @return string - short language code in UC letters
   */
  public static function getCurrentLanguageKey()
  {
    $currentLanguage = Factory::getLanguage()->getTag();
    $languages = LanguageHelper::getLanguages('lang_code');
    return strtoupper($languages[$currentLanguage]->sef);
  }
  
  /**
   * Get localized value from languages provided by SeminarDesk
   * 
   * @param array $fieldValues - Containing values for all languages
   * @param string $langKey - in capital letters, e.g. 'EN', 'DE'
   * @param boolean|string $fallback - true = Fallback to first language in array, OR 
   *                                   string = Fallback language key ('DE', 'EN' ...)
   * @param boolean $htmlencode - true = encode html entities before returning
   * @return string - Value
   */
  public static function translate($fieldValues, $fallback = true, $htmlencode = true)
  {
    $config = self::getConfiguration();
    $langKey = $config['langKey'];
    $value = '';
    
    //-- Set language field as array key
    $localizedValues = array_combine(
      array_column($fieldValues, 'language'),
      array_column($fieldValues, 'value')
    );
    
    //-- Return localized or fallback value
    if (array_key_exists($langKey, $localizedValues)) {
      $value = $localizedValues[$langKey];
    }
    
    //-- Fallback to first language
    if ($fallback === true) {
      $value = reset($localizedValues);
    }
    //-- Fallback to selected language, if exists (otherwise $value is empty)
    elseif (is_string($fallback) && array_key_exists($fallbackLangKey, $localizedValues)) {
      $value = $localizedValues[$langKey];
    }
    
    //-- Encode html entities and return
    return ($htmlencode) ? htmlentities($value, ENT_QUOTES) : $value;
  }

  /**
   * Get data from SeminarDesk
   *
   * @return  string Json data from Seminardesk
   *
   * @since   3.0
   */
  public static function getSeminarDeskData($route)
  {
    $config = self::getConfiguration();
    $connector = HttpFactory::getHttp();
    try {
      $data = $connector->get($config['api'] . $route);
    } catch (\Exception $exception) {
      Log::add('Failed to fetch remote IP data: ' . $exception->getMessage(), Log::ERROR, 'com_seminardesk');
      $this->logger->error('Failed to fetch remote IP data: ' . $exception->getMessage());
      $data = 'Failed to fetch remote IP data: ' . $exception->getMessage();
    }
    return $data;
  }
  
  /**
   * Get url for SeminarDesk detail page
   * - TO DO: Preselection of a specific date: ?eventDateId=<id> - NOT working here!
   *   See description of getBookingUrl()
   * 
   * @param stdClass $event - must contain id, titleSlug and eventId
   * @param string $landKey
   * @return string URL to event detail page
   */
  public static function getDetailsUrl($event)
  {
    $slug = self::translate($event->titleSlug);
    return JRoute::_("index.php?option=com_seminardesk&view=event&id=" . $event->id . '&slug=' . $slug);
  }
  
  /**
   * Get url for SeminarDesk booking
   * - Preselection of a specific date: ?eventDateId=<id>
   * - For preselection of multiple dates of an event (e.g. regular annual group), 
   *   the URL would be ?eventDateId=<id1>&?eventDateId=<id2>&?eventDateId=<id3>
   * 
   * @param stdClass $event - must contain id, titleSlug and eventId
   * @param string $landKey
   * @param string $booking_base_url
   * @return string URL to embedded event booking form
   */
  public static function getBookingUrl($event)
  {
    $config = self::getConfiguration();
    return self::getDetailsUrl($event, $config) . '/embed?eventDateId=' . $event->id;
  }
  
  /**
   * Format start - end date interval, omitting same months / years
   * 
   * @param integer|string $startDate - timestamp in seconds
   * @param integer|string $endDate - timestamp in seconds
   * @param boolean $withYear - always add year? If false, the year is only rendered if year from/to is different
   * @return string
   */
  public static function getDateFormatted($beginDate, $endDate, $separator = ' - ', $withYear = false)
  {
    $dateParts = [];
    $sameYear = date('Y', $beginDate) == date('Y', $endDate);
    
    //-- Set formatted start date if different from end date
    if (date('d.m.Y', $beginDate) !== date('d.m.Y', $endDate)) {
      if (date('m.Y', $beginDate) == date('m.Y', $endDate)) {
        $dateParts[] = date('d.', $beginDate);
      }
      elseif ($sameYear) {
        $dateParts[] = date('d.m.', $beginDate);
      }
      else {
        $dateParts[] = date('d.m.Y', $beginDate);
      }
    }
    //-- Add end date (with or without year)
    $dateParts[] = date(($withYear || !$sameYear)?'d.m.Y':'d.m.', $endDate);
    
    //-- Join and return
    $separator = '<span class="date-separator">' . $separator . '</span>';
    return (
      '<span class="sd-event-begindate">' 
      . implode(
          '</span>' . $separator . '<span class="sd-event-enddate">', 
          $dateParts
        )
      . '</span>'
    );
  }
  
  /**
   * Render categories as links 
   * 
   * @param array $categories
   * @return array - category links
   */
  public static function getCategoryLinks($categories, $link_url = '.')
  {
    array_walk($categories, function(&$category, $key, $link_url) {
      $category = '<a href="' . $link_url . '?category=' . $key . '">' . htmlentities($category, ENT_QUOTES) . '</a>';
    }, $link_url);
    return $categories;
  }
  
  /**
   * Get status label in current language, or untranslated, but readable, if no 
   * translation has been found (e.g. fully_booked => Fully Booked), 
   * or empty, if no status is set. 
   * 
   * @param stdClass $event - must contain registrationAvailable and status
   * @return string - Status label translated
   */
  public static function getStatusLabel($event)
  {
    $label = '';
    if ($event->registrationAvailable) {
      if ($event->status) {
        $label = JText::_("COM_SEMINARDESK_EVENTS_STATUS_" . strtoupper($event->status));
        if (!$label) {
          $label = ucfirst(str_replace('_', ' ', $event->status));
        }
      }
    }
//    else {
//      $label = JText::_("COM_SEMINARDESK_EVENTS_NO_REGISTRATION_AVAILABLE");
//    }
    return $label;
  }
  
  /**
   * Load EventDates from SeminarDesk API
   *
   * @return  array Event Dates (stdClass)
   *
   * @since   3.0
   */
  public function loadEventDates()
  {
    $config = self::getConfiguration();
    $eventDatesData = self::getSeminarDeskData('/EventDates');
    
    if (is_object($eventDatesData) && $eventDatesData) {
      $eventDates = json_decode($eventDatesData->body)->dates;
      
      //-- Get values in current language, with fallback to first language in set
      foreach ($eventDates as $key => &$eventDate) {
        self::prepareEventDate($eventDate);
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
   * Load a single event from SeminarDesk API
   *
   * @return stdClass Event
   *
   * @since   3.0
   */
  public function loadEvent()
  {
    $config = self::getConfiguration();
    $eventData = self::getSeminarDeskData('/Event');
    
    if (is_object($eventData) && $eventData) {
      $event = json_decode($eventData->body)->dates;
      die('<br>--' . json_encode($event) . '--<br>');
      //-- Get values in current language, with fallback to first language in set
//      self::prepareEventDate($event);
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
   * Preprocess / prepare fields of event date for use in views
   * 
   * @param stdClass $eventDate
   * @param string $landKey
   * @return stdClass - $eventDate with preprocessed fields
   */
  public function prepareEventDate(&$eventDate)
  {
    $config = self::getConfiguration();
    $eventDate->title = self::translate($eventDate->title, $config['langKey']);
    $eventDate->eventDateTitle = self::translate($eventDate->eventDateTitle, $config['langKey']);
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
      return !in_array($key, SeminardeskHelperData::LABELS_TO_HIDE);
    }, ARRAY_FILTER_USE_KEY);
    $eventDate->categoriesList = htmlentities(implode(', ', $eventDate->categories), ENT_QUOTES);
//    $eventDate->categoryLinks = implode(', ', SeminardeskHelperData::getCategoryLinks($eventDate->categories));
    $eventDate->statusLabel = htmlentities($eventDate->statusLabel, ENT_QUOTES);

    //-- Set special event flags (festivals, external organisers)
    $eventDate->isFeatured = array_key_exists(SeminardeskHelperData::LABELS_FESTIVALS_ID, $eventDate->labels);
    $eventDate->isExternal = array_key_exists(SeminardeskHelperData::LABELS_EXTERNAL_ID, $eventDate->labels);
    $eventDate->showDateTitle = ($eventDate->eventDateTitle && $eventDate->eventDateTitle != $eventDate->title);

    //-- Format date
    $eventDate->beginDate = $eventDate->beginDate / 1000;
    $eventDate->endDate = $eventDate->endDate / 1000;
    $eventDate->dateFormatted = SeminardeskHelperData::getDateFormatted($eventDate->beginDate, $eventDate->endDate);

    //-- Booking
    $eventDate->details_url = SeminardeskHelperData::getDetailsUrl($eventDate, $config);
//    $eventDate->booking_url = SeminardeskHelperData::getBookingUrl($eventDate, $config);
    $eventDate->statusLabel = SeminardeskHelperData::getStatusLabel($eventDate);
  }
  
}
