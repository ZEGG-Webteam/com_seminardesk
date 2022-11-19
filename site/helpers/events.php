<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_seminardesk
 *
 * @author      Benno Flory
 * @copyright   Copyright (C) 2022 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\LanguageHelper;

defined('_JEXEC') or die;

/**
 * Helper for com_seminardesk
 *
 * @since  3.0
 */
class SeminardeskHelperEvents
{
  
  /**
   * 
   * @return string - short language code in UC letters, e.g. 'DE', 'EN' ...
   */
  public static function getCurrentLanguageKey()
  {
    $currentLanguage = JFactory::getLanguage()->getTag();
    $languages = LanguageHelper::getLanguages('lang_code');
    return strtoupper($languages[$currentLanguage]->sef);
  }
  
  /**
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
      foreach ($eventDates as $key => $eventDate) {
        $eventDate->title = self::getValueByLanguage($eventDate->title, $langKey);
        $eventDate->facilitators = array_column($eventDate->facilitators, 'name');
        
        //-- Format date
        $eventDate->beginDate = $eventDate->beginDate / 1000;
        $eventDate->endDate = $eventDate->endDate / 1000;
        $eventDate->dateFormatted = self::getDateFormatted($eventDate->beginDate, $eventDate->endDate);
        
        //-- Booking
        $eventDate->details_url = self::getDetailsUrl($eventDate, $langKey, $config);
//        $eventDate->booking_url = self::getBookingUrl($eventDate, $langKey, $config);
        $eventDate->statusLabel = self::getStatusLabel($eventDate);
        
        $eventDates[$key] = $eventDate;
      }
      return $eventDates;
    }
    else {
      // To do: Propper error handling
      return 'getEventDates() failed! ($eventDatesData = ' . json_encode($eventDatesData) . ')';
    }
  }
  
  /**
   * Get url for SeminarDesk detail page
   * - TO DO: Preselection of a specific date: ?eventDateId=<id> - NOT working here!
   *   See description of getBookingUrl()
   * 
   * @param object $eventDate
   * @param string $landKey
   * @param string $booking_base_url
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
    else {
      $label = JText::_("COM_SEMINARDESK_EVENTS_NO_REGISTRATION_AVAILABLE");
    }
    return $label;
  }
  
}
