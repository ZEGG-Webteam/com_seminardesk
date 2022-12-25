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
//      self::LABELS_FESTIVALS_ID, 
      self::LABELS_EXTERNAL_ID, 
  ];
  const DEFAULT_TENANT_ID = 'zegg';
  
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
      $tenant_id = $app->input->get('tenant_id', self::DEFAULT_TENANT_ID, 'STRING');

      // Configuration - To do: move into some propper configuration place
      self::$config = [
        'tenant_id' => $tenant_id,
        'langKey' => $langKey,
        'api' => 'https://' . $tenant_id . '.seminardesk.de/api',
        'booking_base' => 'https://booking.seminardesk.de/' . strtolower($langKey) . '/' . $tenant_id . '/',
        'eventlist_url' => 'index.php?option=com_seminardesk&view=events&Itemid=' . $app->getMenu()->getActive()->id . '&lang=' . strtolower($langKey) , 
      ];
    }
    
    return self::$config;
  }

  /**
   * Rplace multiple (>= 3) unterscores and dashes by <hr> a tag.
   * 
   * @param string $text
   * @return string
   */
  public static function replaceHR($text) {
    return preg_replace('/[_-]{3,}/', '<hr>', $text);
  }

  /**
   * Remove attributes from tags and strip some tags, if desired
   *   and replace multiple (>= 3) unterscores and dashes by <hr> a tag.
   * 
   * @param string $text
   * @param string|boolean $stripTagsExceptions - Tags or false = do not strip tags)
   * @return string
   */
  public static function cleanupHtml($text, $stripTagsExceptions = '<h1><h2><h3><h4><p><br><b><hr><strong>') {
    $text = strip_tags(preg_replace("/<([a-z][a-z0-9]*)[^>]*?(\/?)>/si",'<$1$2>', $text), $stripTagsExceptions);
    return self::replaceHR($text);
  }

  /**
   * Remove all font tags and style attributes 
   *   and replace multiple (>= 3) unterscores and dashes by <hr> a tag. (as in cleanupHtml()
   * 
   * @param string $text
   * @return string
   */
  public static function cleanupFormatting($text) {
    // Remove all style attributes
    // regex hack: (<[^x>]+) is for all tags except "img" => images should keep their styles. 
    //   x replaces img because regex pattern for <img too complicated, and regex (<[^i>]+) is catching also <li> etc.
    $text = str_replace('<x', '<img', preg_replace('/(<[^x>]+) style=".*?"/i', '$1', str_replace('<img', '<x', $text)));
    // Remove font tags
    $text = preg_replace(["/<font.*?>/im", "/<\/font>/im"], "", $text);
    return self::replaceHR(str_replace(['&nbsp;'], [' '], $text));
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
   * @param boolean|string $fallbackLang - true = Fallback to first language in array, OR 
   *                                   string = Fallback language key ('DE', 'EN' ...)
   * @param boolean $htmlencode - true = encode html entities before returning
   * @return string - Value
   */
  public static function translate($fieldValues, $htmlencode = false, $fallbackLangLang = true)
  {
    $config = self::getConfiguration();
    $langKey = $config['langKey'];
    $value = '';

    if (is_array($fieldValues)) {

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
      if ($fallbackLang === true) {
        $value = reset($localizedValues);
      }
      //-- Fallback to selected language, if exists (otherwise $value is empty)
      elseif (is_string($fallbackLang) && array_key_exists($fallbackLangLangKey, $localizedValues)) {
        $value = $localizedValues[$langKey];
      }
    }

    //-- Encode html entities and return
    return ($htmlencode) ? htmlentities($value, ENT_QUOTES) : $value;
  }

  /**
   * Get SeminarDesk API url
   *
   * @return  string Json data from Seminardesk
   * @since   3.0
   */
  public static function getSeminarDeskApiLink($route) {
    $config = self::getConfiguration();
    return $config['api'] . $route;
  }
  
  /**
   * Get data from SeminarDesk
   *
   * @return  string Json data from Seminardesk
   * @since   3.0
   */
  public static function getSeminarDeskData($api_uri)
  {
    $connector = HttpFactory::getHttp();
    try {
      $data = $connector->get($api_uri);
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
    $slug = self::translate($event->titleSlug, true);
    return JRoute::_("index.php?option=com_seminardesk&view=event&eventId=" . $event->eventId . '&slug=' . $slug);
  }
  
  /**
   * Get url for SeminarDesk booking
   * - Preselection of a specific date: ?eventDateId=<id>
   * - For preselection of multiple dates of an event (e.g. regular annual group), 
   *   the URL would be ?eventDateId=<id1>&?eventDateId=<id2>&?eventDateId=<id3>
   * 
   * @param stdClass $event - must contain id, titleSlug and eventId
   * @return string URL to embedded event booking form
   */
  public static function getBookingUrl($eventId, $slug, $eventDateIds = [])
  {
    $config = self::getConfiguration();
    $url = $config['booking_base'] . $eventId . '/' . $slug . '/embed';
    if ($eventDateIds) {
      $url .= '?eventDateId=' . implode('&eventDateId=', $eventDateIds);
    }
    return $url;
//    return self::getDetailsUrl($event) . '/embed?eventDateId=' . $event->id;
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
   * @since   3.0
   */
  public function loadEventDates()
  {
    $config = self::getConfiguration();
    $api_uri = self::getSeminarDeskApiLink('/eventDates');
    $eventDatesData = self::getSeminarDeskData($api_uri);
    
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
   * @param integer $id
   * @return stdClass Event
   *
   * @since   3.0
   */
  public function loadEvent($eventId)
  {
    $config = self::getConfiguration();
    $api_uri = self::getSeminarDeskApiLink('/events/' . $eventId);
    $eventData = self::getSeminarDeskData($api_uri);
    
    if (is_object($eventData) && $eventData) {
      $event = json_decode($eventData->body);
      //-- Get values in current language, with fallback to first language in set
      self::prepareEvent($event);
      $event->apiUri = $api_uri;
      $event->langKey = self::getCurrentLanguageKey();
    }
    
    else {
      // Error handling
      JLog::add(
        'loadEvent($id) failed! ($eventData = ' . json_encode($eventData) . ')', 
        JLog::ERROR, 
        'com_seminardesk'
      );
      $event = [];
    }
    return $event;
  }
  
  /**
   * Preprocess / prepare fields of event date for use in views
   * 
   * @param stdClass $eventDate
   * @return stdClass - $eventDate with preprocessed fields
   */
  public function prepareEventDate(&$eventDate)
  {
    $eventDate->title = self::translate($eventDate->title, true);
    $eventDate->eventDateTitle = self::translate($eventDate->eventDateTitle, true);
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
    // Get categories: Labels except LABELS_TO_HIDE
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
    $eventDate->details_url = SeminardeskHelperData::getDetailsUrl($eventDate);
//    $eventDate->booking_url = SeminardeskHelperData::getBookingUrl($eventDate...);
    $eventDate->statusLabel = SeminardeskHelperData::getStatusLabel($eventDate);
  }
  
  /**
   * Preprocess / prepare fields of event for use in views
   * 
   * @param stdClass $event
   * @return stdClass - $event with preprocessed fields
   */
  public function prepareEvent(&$event)
  {
    $config = self::getConfiguration();
    
    //-- Translations
    $event->title = self::translate($event->title, true);
    $event->titleSlug = self::translate($event->titleSlug);
    $event->subtitle = self::translate($event->subtitle, true);
    $event->teaser = self::translate($event->teaser);
    $event->labels = array_combine(
      array_column($event->labels, 'id'), 
      array_column($event->labels, 'name')
    );
    // Get categories: Labels except LABELS_TO_HIDE
    $event->categories = array_filter($event->labels, function($key){
      return !in_array($key, SeminardeskHelperData::LABELS_TO_HIDE);
    }, ARRAY_FILTER_USE_KEY);
    //-- Prepare event labels list (categories)
    $event->catLinks = [];
    foreach( $event->categories as $cat_id => $cat_name) { 
      $event->catLinks[] = '<a href="' . JRoute::_($config['eventlist_url']) . '?cat=' . $cat_id . '">' . $cat_name . '</a>';
    }
    $event->catLinks = implode(', ', $event->catLinks);
    
    $event->description = self::translate($event->description);
    // Bugfix (Joomla / SeminarDesk bug): Some descriptions contain inline images data which are blasting Joomla's regex limit (pcre.backtrack_limit).
    // => remove images from description and add class which will trigger async loading by JS.
    $event->descriptionTooLong = strlen($event->description) > ini_get( "pcre.backtrack_limit");
    if ($event->descriptionTooLong) {
      $event->description = preg_replace("/<img[^>]+\>/i", ' (' . JText::_("COM_SEMINARDESK_EVENT_LOADING_IMAGES") . ') ', $event->description); 
    }
    $event->description = self::cleanupFormatting($event->description);
    
    $event->headerPictureUrl = self::translate($event->headerPictureUrl);
    $event->infoDatesPrices = self::cleanupHtml(self::translate($event->infoDatesPrices));
    $event->infoBoardLodging = self::translate($event->infoBoardLodging);
    $event->infoMisc = self::translate($event->infoMisc);
    $event->booking_url = SeminardeskHelperData::getBookingUrl($event->id, $event->titleSlug);
    foreach($event->facilitators as $key => $facilitator) {
      $about = self::translate($facilitator->about);
      $event->facilitators[$key]->about = self::cleanupFormatting($about);
    }
    //-- Prepare event dates
    foreach($event->dates as $key => $date) {
      $date->title = self::translate($date->title);
      $date->labels = array_combine(
        array_column($date->labels, 'id'), 
        array_column($date->labels, 'name')
      );
      
      //-- Format date
      $date->beginDate = $date->beginDate / 1000;
      $date->endDate = $date->endDate / 1000;
      $date->dateFormatted = SeminardeskHelperData::getDateFormatted($date->beginDate, $date->endDate);

      //-- Prepare facilitator list, if not same as in event. Remove otherwise
      $date->facilitatorLinks = [];
      if (array_intersect(array_column($event->facilitators, 'id'), array_column($date->facilitators, 'id'))) {
        foreach ( $date->facilitators as $facilitator ) { 
//          $date->facilitatorLinks[] = '<a href="#' . $facilitator->id . '" title="Details comming soon...">' . $facilitator->name . '</a>';
          $date->facilitatorLinks[] = '<span title="Details comming soon...">' . $facilitator->name . '</span>';
        }
        $date->facilitatorLinks = implode(', ', $date->facilitatorLinks);
      }
      
      //-- Prices & fees
      foreach ( $date->attendanceFees as $key => $fee ) {
        $date->attendanceFees[$key]->name = self::translate($fee->name);
      }
//      foreach($date->availableMisc as $key => $misc) {
//        $date->availableMisc[$key]->title = self::translate($misc->title);
//        $date->availableMisc[$key]->prices = self::translate($misc->prices);
//      }

      //-- Booking
      $date->booking_url = SeminardeskHelperData::getBookingUrl($event->id, $event->titleSlug, [$date->id]);
      $date->statusLabel = SeminardeskHelperData::getStatusLabel($date);
      
      $event->$dates[$key] = $date;
    }

  }
  
  public static function fittingFilters($eventDate, $filters) {
    //-- Matching current filter? => Hide event it no
    return
      // Check date filter
      (
        !$filters['date'] || 
        $filters['date'] <= date('Y-m-d', $eventDate->endDate)
      )
      &&
      // Check category filter
      (
        $filters['cat'] == 0 ||
        in_array($filters['cat'], array_keys($eventDate->categories))
      )
      &&
      // Check organisator Filter
      (
        !in_array($filters['org'], ['zegg', 'external']) ||
        ($filters['org'] == 'zegg' && !$eventDate->isExternal) ||
        ($filters['org'] == 'external' && $eventDate->isExternal)
      );
  }
  
}
