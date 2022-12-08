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
use \Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\LanguageHelper;

/**
 * Class SeminardeskHelperEvents: Helper for Seminardesk Events
 *
 * @since  3.0
 */
class SeminardeskHelperEvents
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
   * @param string $fallbackLangKey - 
   * @return string - Value
   */
  public static function getValueByLanguage($fieldValues, $langKey, $fallback = true)
  {
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
    //-- Fallbacks
    elseif ($fallback) {
      //-- Fallback to first language
      if ($fallback === true) {
        $value = reset($localizedValues);
      }
      //-- Fallback to selected language, if exists (otherwise $value is empty)
      elseif (is_string($fallback) && array_key_exists($fallbackLangKey, $localizedValues)) {
        $value = $localizedValues[$langKey];
      }
    }
    return $value;
  }

  /**
   * Get data from SeminarDesk
   *
   * @return  string Json data from Seminardesk
   *
   * @since   3.0
   */
  public static function getSeminarDeskData($config, $route)
  {
    $connector = HttpFactory::getHttp();
    try {
        $data = $connector->get($config['api'] . $route);
    } catch (\Exception $exception) {
        $this->logger->error('Failed to fetch remote IP data: ' . $exception->getMessage());
        $data = 'Failed to fetch remote IP data: ' . $exception->getMessage();
    }
    return $data;
  }
  
  /**
   * Get EventDates
   *
   * @return  array Event Dates (objects)
   *
   * @since   3.0
   */
  public static function getEventDates($config)
  {
    $eventDatesData = self::getSeminarDeskData($config, '/EventDates');
    $langKey = self::getCurrentLanguageKey();
    
    if (is_object($eventDatesData) && $eventDatesData) {
      $eventDates = json_decode($eventDatesData->body)->dates;
      
      //-- Get values in current language, with fallback to first language in set
      foreach ($eventDates as $key => &$eventDate) {
        self::prepareEventDate($eventDate, $config, $langKey);
      }
    }
    else {
      // Error handling
      JLog::add(
        'getEventDates() failed! ($eventDatesData = ' . json_encode($eventDatesData) . ')', 
        JLog::ERROR, 
        'com_seminardesk'
      );
      $eventDates = [];
    }
    return $eventDates;
  }
  
  /**
   * Get url for SeminarDesk detail page
   * - TO DO: Preselection of a specific date: ?eventDateId=<id> - NOT working here!
   *   See description of getBookingUrl()
   * 
   * @param object $eventDate
   * @param string $landKey
   * @param array $config containing key 'booking_base'
   * @return string URL to event detail page
   */
  public static function getDetailsUrl($eventDate, $langKey, $config)
  {
    $slug = self::getValueByLanguage($eventDate->titleSlug, $langKey);
    return $config['booking_base'] . $eventDate->eventId . '/' . $slug;
  }
  
  /**
   * Get url for SeminarDesk booking
   * - Preselection of a specific date: ?eventDateId=<id>
   * - For preselection of multiple dates of an event (e.g. regular annual group), 
   *   the URL would be ?eventDateId=<id1>&?eventDateId=<id2>&?eventDateId=<id3>
   * 
   * @param object $eventDate
   * @param string $landKey
   * @param string $booking_base_url
   * @return string URL to embedded event booking form
   */
  public static function getBookingUrl($eventDate, $langKey, $config)
  {
    return self::getDetailsUrl($eventDate, $langKey, $config) . '/embed?eventDateId=' . $eventDate->id;
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
   * Preprocess / prepare fields of event date for use in views
   * 
   * @param object $eventDate
   * @param string $landKey
   * @param array $config containing key 'booking_base'
   * @return object - $eventDate with preprocessed fields
   */
  public static function prepareEventDate(&$eventDate, $config, $langKey)
  {
    $eventDate->title = htmlentities(self::getValueByLanguage($eventDate->title, $langKey), ENT_QUOTES);
    $eventDate->eventDateTitle = htmlentities(self::getValueByLanguage($eventDate->eventDateTitle, $langKey), ENT_QUOTES);
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
      return !in_array($key, self::LABELS_TO_HIDE);
    }, ARRAY_FILTER_USE_KEY);
    $eventDate->categoriesList = htmlentities(implode(', ', $eventDate->categories), ENT_QUOTES);
    $eventDate->statusLabel = htmlentities($eventDate->statusLabel, ENT_QUOTES);

    //-- Set special event flags (festivals, external organisers)
    $eventDate->isFeatured = array_key_exists(self::LABELS_FESTIVALS_ID, $eventDate->labels);
    $eventDate->isExternal = array_key_exists(self::LABELS_EXTERNAL_ID, $eventDate->labels);
    $eventDate->showDateTitle = ($eventDate->eventDateTitle && $eventDate->eventDateTitle != $eventDate->title);

    //-- Format date
    $eventDate->beginDate = $eventDate->beginDate / 1000;
    $eventDate->endDate = $eventDate->endDate / 1000;
    $eventDate->dateFormatted = self::getDateFormatted($eventDate->beginDate, $eventDate->endDate);

    //-- Booking
    $eventDate->details_url = self::getDetailsUrl($eventDate, $langKey, $config);
//    $eventDate->booking_url = self::getBookingUrl($eventDate, $langKey, $config);
    $eventDate->statusLabel = self::getStatusLabel($eventDate);
  }
  
  /**
   * Get status label in current language, or untranslated, but readable, if no 
   * translation has been found (e.g. fully_booked => Fully Booked), 
   * or empty, if no status is set. 
   * 
   * @param array $eventDates
   * @return array - Collected categories of all events
   */
  public static function getAllEventCategories($eventDates)
  {
    $labels = [];
    foreach($eventDates as $eventDate) {
      $labels += $eventDate->categories;
    }
    $labels = array_unique($labels);
    asort($labels);
    return $labels;
  }
  
  /**
   * Get status label in current language, or untranslated, but readable, if no 
   * translation has been found (e.g. fully_booked => Fully Booked), 
   * or empty, if no status is set. 
   * 
   * @param object $eventDate
   * @return string - Status label translated
   */
  public static function getStatusLabel($eventDate)
  {
    $label = '';
    if ($eventDate->registrationAvailable) {
      if ($eventDate->status) {
        $label = JText::_("COM_SEMINARDESK_EVENTS_STATUS_" . strtoupper($eventDate->status));
        if (!$label) {
          $label = ucfirst(str_replace('_', ' ', $eventDate->status));
        }
      }
    }
//    else {
//      $label = JText::_("COM_SEMINARDESK_EVENTS_NO_REGISTRATION_AVAILABLE");
//    }
    return $label;
  }
  
}
