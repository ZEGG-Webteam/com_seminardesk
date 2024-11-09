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
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Log\Log;

/**
 * Class SeminardeskDataHelper: Helper for Seminardesk Data Handling
 *
 * @since  3.0
 */
class SeminardeskDataHelper
{
  /**
   * Configurations
   */
  const DEFAULT_TENANT_ID = 'zegg';
  const LABELS_FESTIVALS_ID = 12;
  const LABELS_EXTERNAL_ID = 55;
  const LABELS_ON_APPLICATION_ID = 69;
  const LABELS_TO_HIDE = [
      1, 2, 3, 53, 54, // Languages
//      self::LABELS_FESTIVALS_ID, 
      self::LABELS_EXTERNAL_ID, 
  ];
  const FACILITATORS_TO_HIDE = [
      '& Team',
  ];
  const LODGING_TO_EXCLUDE = ['exÜN'];
  
  /** Custom config, other than https://api.joomla.org/cms-3/classes/Joomla.CMS.MVC.Model.ListModel.html
   *
   * @var array - ['tenant_id', 'langKey', 'api', 'booking_base'] // URL of the SeminarDesk API, base URL to events booking
   */
  protected static $config = [];

  /**
   * API controller
   */
  protected static $api_controller = null;
  
  /**
   * Get configuration (singleton)
   */
  public static function getConfiguration()
  {
    if (!self::$config) {
      $app  = Factory::getApplication();

      // Get key for translations from SeminarDesk (e.g. 'DE', 'EN')
      $langKey = self::getCurrentLanguageKey();

      // Get SeminarDesk API settings
      $tenant_id = $app->input->get('tenant_id', self::DEFAULT_TENANT_ID, 'STRING');
      $events_menu = $app->getMenu()->getActive()->query['events_page']??$app->getMenu()->getActive()->id;
      $facilitators_menu = $app->getMenu()->getActive()->query['facilitators_page']??$app->getMenu()->getActive()->id;
      
      self::$config = [
        'tenant_id' => $tenant_id,
        'langKey' => $langKey,
        'api' => 'https://' . $tenant_id . '.seminardesk.de/api',
        'booking_base' => 'https://booking.seminardesk.de/' . strtolower($langKey) . '/' . $tenant_id . '/',
        'eventlist_base' => 'index.php?option=com_seminardesk&Itemid=' . $events_menu . '&lang=' . strtolower($langKey), 
        'facilitators_base' => 'index.php?option=com_seminardesk&Itemid=' . $facilitators_menu . '&lang=' . strtolower($langKey), 
        'lodging_to_exclude' => self::LODGING_TO_EXCLUDE, 
      ];
    }
    
    return self::$config;
  }

  /**
   * Get API controller (singleton)
   */
  public static function getApiController()
  {
    if (!self::$api_controller) {
      JLoader::register('SeminardeskApiController', JPATH_COMPONENT . '/controllers/api.php');
      self::$api_controller = new SeminardeskApiController();
    }
    return self::$api_controller;
  }

  /**
   * Replace multiple (>= 3) unterscores and dashes by <hr> a tag.
   * 
   * @param string $text
   * @return string
   */
  public static function replaceHR($text) {
    return preg_replace('/[_-]{3,}/', '<hr>', $text);
  }
  
  /**
   * Replace characters that are missing in custom font, like superscript numbers
   * 
   * @param string $text
   * @return string
   */
  public static function replaceMissingFontChars ($text) {
    $replacements = [
        '⁰' => '<sup>0</sup>',
        '¹' => '<sup>1</sup>',
        '²' => '<sup>2</sup>',
        '³' => '<sup>3</sup>',
        '⁴' => '<sup>4</sup>',
        '⁵' => '<sup>5</sup>',
        '⁶' => '<sup>6</sup>',
        '⁷' => '<sup>7</sup>',
        '⁸' => '<sup>8</sup>',
        '⁹' => '<sup>9</sup>',
        'ⁿ' => '<sup>n</sup>',
    ];
    return str_replace(array_keys($replacements), array_values($replacements), $text);
  }

  /**
   * Remove attributes from tags and strip some tags, if desired
   *   and replace multiple (>= 3) unterscores and dashes by <hr> a tag.
   * 
   * @param string $text
   * @param string|boolean $stripTagsExceptions - Tags or false = do not strip tags)
   * @param boolean $stripAttrs - remove all arributes within tags? e.g. style, etc.
   * @return string
   */
  public static function cleanupHtml($text, $stripTagsExceptions = '<h1><h2><h3><h4><p><br><b><hr><strong>', $stripAttrs = true) {
    if ($stripAttrs) {
      $text = preg_replace("/<([a-z][a-z0-9]*)[^>]*?(\/?)>/si",'<$1$2>', $text);
    }
    $text = strip_tags($text, $stripTagsExceptions);
    $text = str_replace('&nbsp;' , ' ', $text); // Current font does not support &nbsp;
    return self::replaceHR($text);
  }

  /**
   * Remove all font tags and style attributes 
   * and replace multiple (>= 3) unterscores and dashes by <hr> a tag. (as in cleanupHtml()
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
   * @param boolean $htmlencode - true = encode html specialchars before returning
   * @return string - Value
   */
  public static function translate($fieldValues, $htmlencode = false, $fallbackLang = true)
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
      else {
        //-- Fallback to first language
        if ($fallbackLang === true) {
          $value = reset($localizedValues);
        }
        //-- Fallback to selected language, if exists (otherwise $value is empty)
        elseif (is_string($fallbackLang) && array_key_exists($fallbackLangLang, $localizedValues)) {
          $value = $localizedValues[$langKey];
        }
      }
    }

    //-- Encode html entities and return
    return ($htmlencode) ? htmlspecialchars($value, ENT_QUOTES) : $value;
  }

  /**
   * Get SeminarDesk API url
   *
   * @return  string Json data from Seminardesk
   * @since   3.0
   */
  public static function getSeminarDeskApi() {
    $config = self::getConfiguration();
    return $config['api'];
  }
  
  /**
   * Function used to create a slug associated to an "ugly" string.
   * See https://stackoverflow.com/questions/2955251/php-function-to-make-slug-url-string
   *
   * @param string $string the string to transform.
   *
   * @return string the resulting slug.
   */
  public static function createSlug($string) {

      $table = array(
              'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
              'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'AE', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
              'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
              'Õ'=>'O', 'Ö'=>'OE', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'UE', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
              'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'ae', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
              'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
              'ô'=>'o', 'õ'=>'o', 'ö'=>'oe', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'ue', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b',
              'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r'
      );

      // Replace special chars etc.
      $slug = strtolower(strtr($string, $table));
      // Replace remaining non characters
      $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', $slug);
      // Remove duplicate divider
      $slug = preg_replace('~-+~', '-', $slug);
      
      return $slug;
  }

  /**
   * Get url for SeminarDesk booking
   * - Preselection of a specific date: ?eventDateId=<id>
   * - For preselection of multiple dates of an event (e.g. regular annual group), 
   *   the URL would be ?eventDateId=<id1>&eventDateId=<id2>&eventDateId=<id3>
   * 
   * @param integer $eventId
   * @param string $slug
   * @param integer|array $eventDateIds - one or more DateId to preselect for booking
   * @return string URL to embedded event booking form
   */
  public static function getBookingUrl($eventId, $slug, $eventDateIds = [])
  {
    $config = self::getConfiguration();
    $url = $config['booking_base'] . $eventId . '/' . $slug . '/embed';
    if (is_array($eventDateIds) && count($eventDateIds) > 0) {
      $url .= '?eventDateId=' . implode('&eventDateId=', $eventDateIds);
    }
    elseif (is_numeric($eventDateIds)) {
      $url .= '?eventDateId=' . $eventDateIds;
    }
    return $url;
  }
  
  /**
   * Get url for SeminarDesk event detail page
   * - TO DO: Preselection of a specific date: ?eventDateId=<id> - NOT working here!
   *   See description of getBookingUrl()
   * 
   * @param stdClass $event - must contain id, titleSlug and eventId
   * @return string URL to event detail page
   */
  public static function getEventUrl($event)
  {
    $config = self::getConfiguration();
    return JRoute::_($config['eventlist_base'] . "&view=event&eventId=" . $event->eventId . '&slug=' . $event->titleSlug);
  }
  
  /**
   * Get url for SeminarDesk facilitator detail page
   * 
   * @param stdClass $facilitator - must contain id and name
   * @return string URL to facilitator detail page
   */
  public static function getFacilitatorUrl($facilitator)
  {
    $config = self::getConfiguration();
    return JRoute::_($config['facilitators_base'] . "&view=facilitator&id=" . $facilitator->id . '&name=' . self::createSlug($facilitator->name));
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
    $withYear = $withYear || (date('Y', $beginDate) != date('Y')); // If event is in future / past year, add year
    
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
   * @param stdClass $eventDate - containing status field
   * @return string - Status label translated
   */
  public static function getStatusLabel($eventDate)
  {
    $label = '';
    if ($eventDate->status) {
      // Set status label dynamically
      $key = "COM_SEMINARDESK_EVENTS_STATUS_" . strtoupper($eventDate->status);
      
      // Special case: If label "Anmeldestatus/Auf Bewerbung" are set
      if (self::hasLabel($eventDate, self::LABELS_ON_APPLICATION_ID)) {
        $key = "COM_SEMINARDESK_EVENTS_STATUS_ON_APPLICATION";
      }
      
      // Special case: If detailpageAvailable is set to false
      // Note: the attribute detailpageAvailable only exists in the 
      // API response from https://zegg.seminardesk.de/api/eventDates/
      // but not in the API response for a single event oder date. 
      if (property_exists($eventDate, 'detailpageAvailable') && !$eventDate->detailpageAvailable) {
        $key = "COM_SEMINARDESK_EVENTS_STATUS_DETAILS_LATER";
      }

      // Translate status. If no translation found, use status as label
      $label = JText::_($key);
      if ($label == $key) {
        $label = ucwords($eventDate->status, "_");
      }
    }
    return $label;
  }
  
  /**
   * Get all accomodation prices excluding given config list (exÜn).
   * 
   * @param object $date - A single date of an event - event->date
   * @return array - List of all lodging prices, except for $config['lodging_to_exclude']
   */
  public function getLodgingPrices($date) {
    $config = self::getConfiguration();
    $lodgingPrices = [];
    foreach ($date->availableLodging as $lodging) {
      if (!in_array($lodging->id, $config['lodging_to_exclude'])) {
        $lodgingPrices = array_merge($lodgingPrices, array_column($lodging->prices, 'price'));
      }
    }
    return $lodgingPrices;
  }
  
  /**
   * Get prices for meals etc. excluding given config list
   * 
   * @param object $date - A single date of an event - event->date
   * @return array - List of min/max boarding prices
   */
  public function getBoardPrices($date) {
    $boardPrices = [];
    foreach ($date->availableBoard as $board) {
      $boardPrices = array_merge($boardPrices, array_column($board->prices, 'price'));
    }
    return $boardPrices;
  }
  
  /**
   * Check if given label ID is assigned in the eventDates labels list.
   * 
   * @param stdClass $event
   * @param integer $label
   * @return boolean
   */
  public static function hasLabel($event, $label) {
    return array_key_exists($label, $event->labels);
  }
  
  /**
   * 
   * @param stdClass $eventDate
   * @param array $filters - containing keys 'date', 'cat', 'org' and/or 'term'
   * @return boolean - true if eventDate matches all filters, false if not.
   */
  public static function matchingFilters($eventDate, $filters) {
    //-- If term is set: Match them
    if ($filters['term']) {
      $terms = explode(' ', strtolower(trim(str_replace('  ', ' ', $filters['term']))));
      $eventSearchableText = strtolower(
        $eventDate->title . ' ' . 
        implode($eventDate->facilitators) . ' ' . 
        implode($eventDate->categories));
      $terms_matching = array_filter($terms, function ($term) use ($eventSearchableText) {
        return strpos($eventSearchableText, $term) !== false;
      });
    }

    //-- Matching current filters?
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
      // Check organisator filter
      (
        !in_array($filters['org'], ['zegg', 'external']) ||
        ($filters['org'] == 'zegg' && !$eventDate->isExternal) ||
        ($filters['org'] == 'external' && $eventDate->isExternal)
      )
      &&
      // Check search term filter (full text search)
      (
        !$filters['term'] || count($terms_matching) == count($terms)
      );
  }

  /**
   * Apply filters to event list
   * @param type $eventDates
   * @param type $filters
   */
  public static function filterEvents(&$eventDates, $filters = [])
  {
    //-- Apply filters
    // convert labels to array and trim each label value
    $filters['labels'] = isset($filters['labels'])?array_map('trim', explode(',', $filters['labels'])):[];
    $filters['label_exceptions'] = isset($filters['label_exceptions'])?array_map('trim', explode(',', $filters['label_exceptions'])):[];

    $eventDates = array_filter($eventDates, function ($eventDate) use ($filters) {
      //-- Show canceled events?
      $show_canceled = $filters['show_canceled'] ?? false;
      if (!$show_canceled && $eventDate->status == "canceled") {
        return false;
      }
      
      //-- Show ongoing events (that have already started >= 1 day ago)
      $hide_ongoing = $filters['hide_ongoing'] ?? false;
      if ($hide_ongoing && $eventDate->beginDate < strtotime('tomorrow')) {
        return false;
      }

      //-- Filter by labels (IDs or labels)
      if ($filters['labels']) {
        // Allow both IDs or label text as filter
        $eventLabels = (is_numeric($filters['labels'][0]))?array_keys($eventDate->labels):$eventDate->labels;
        // Compare labels with filter. If some are matching, return event
        $labelsMatching = count(array_intersect($eventLabels, $filters['labels'])) > 0;
      }
      else {
        $labelsMatching = true;
      }

      //-- Filter by label exceptions (IDs or labels)
      if ($filters['label_exceptions'] && $eventDate->labels) {
        // Allow both IDs or label text as filter
        $eventLabels = (is_numeric($filters['label_exceptions'][0]))?array_keys($eventDate->labels):$eventDate->labels;
        // Compare labels with filter. If some are matching, return event
        $labelExceptionsMatching = count(array_intersect($eventLabels, $filters['label_exceptions'])) > 0;
      }
      else {
        $labelExceptionsMatching = false;
      }

      if ($filters['term']) {
        $terms = explode(' ', $filters['term']);
        $termsMatching = false;
        foreach ($terms as $term) {
          $termsMatching |= (strpos($eventDate->title . ' ' . $eventDate->facilitatorsList . ' ' . $eventDate->labelsList, $term) !== false);
        }
      }
      else {
        $termsMatching = true;
      }
      return $labelsMatching && !$labelExceptionsMatching && $termsMatching;
    });

    //-- Limit
    if (isset($filters['limit']) && $filters['limit'] > 0) {
      $eventDates = array_slice($eventDates, 0, $filters['limit']);
    }
  }

  /**
   * Load and filter EventDates
   *
   * @param $filters array - e.g. ['labels' => '1,2,3'] or ['labels' => [1],[2],[3]] or ['labels_exceptions' => 1,2,3'] or ['limit' => 5] or ['show_canceled' => true]
   * @return  array Event Dates (stdClass)
   * @since   3.0
   */
  public static function loadEventDates($filters = [])
  {
    $eventDates = [];

    // Load event dates from API or cache
    $api = self::getSeminarDeskApi();
    $data = self::getApiController()->getSeminarDeskData($api, '/eventDates');

    if (is_object($data) && $data) {
      // Extract dates from JSON response
      $eventDates = json_decode($data->body)->dates;
      
      // Get values in current language, with fallback to first language in set
      foreach ($eventDates as $key => &$eventDate) {
        self::prepareEventDate($eventDate);
      }
      
      // Apply filters
      self::filterEvents($eventDates, $filters);
    }
    else {
      // Error handling
      Log::add(
        'loadEventDates() failed! ($data = ' . json_encode($data) . ')', 
        Log::ERROR, 
        'com_seminardesk'
      );
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
  public static function loadEvent($eventId)
  {
    $route = '/events/' . $eventId;
    $api = self::getSeminarDeskApi();
    $data = self::getApiController()->getSeminarDeskData($api, $route);
    
    if (is_object($data) && $data) {
      $event = json_decode($data->body);
      //-- Get values in current language, with fallback to first language in set
      self::prepareEvent($event);
      $event->apiUri = self::getSeminarDeskApi() . $route;
      $event->langKey = self::getCurrentLanguageKey();
    }
    
    else {
      // Error handling
      JLog::add(
        'loadEvent($id) failed! ($eventData = ' . json_encode($data) . ')', 
        JLog::ERROR, 
        'com_seminardesk'
      );
      $event = [];
    }
    return $event;
  }
  
  /**
   * Load Facilitators from SeminarDesk API
   *
   * @return  array Facilitators (stdClass)
   * @since   3.0
   */
  public static function loadFacilitators()
  {
    $api = self::getSeminarDeskApi();
    $data = self::getApiController()->getSeminarDeskData($api, '/facilitators');
    
    if (is_object($data) && $data) {
      $facilitators = json_decode($data->body)->data;
      
      //-- Get values in current language, with fallback to first language in set
      foreach ($facilitators as $key => &$facilitator) {
        self::prepareFacilitator($facilitator);
      }

      // Filter FACILITATORS_TO_HIDE
      $facilitators = array_filter($facilitators, function($facilitator) {
        return !in_array($facilitator->name, self::FACILITATORS_TO_HIDE);
      });

      //-- Order llist by facilitator name
      usort($facilitators, function($a, $b) { 
        return strcmp($a->name, $b->name);
      });
    }
    else {
      // Error handling
      JLog::add(
        'loadFacilitators() failed! (facilitatorsData = ' . json_encode($facilitatorsData) . ')', 
        JLog::ERROR, 
        'com_seminardesk'
      );
      $facilitators = [];
    }
    return $facilitators;
  }
    
  /**
   * Load a single facilitator from SeminarDesk API
   *
   * @param integer $id
   * @return stdClass Facilitator
   *
   * @since   3.0
   */
  public static function loadFacilitator($facilitatorId)
  {
    $api = self::getSeminarDeskApi();
    $data = self::getApiController()->getSeminarDeskData($api, '/facilitators/' . $facilitatorId);
    
    if (is_object($data) && $data) {
      $facilitator = json_decode($data->body);
      
      //-- Load events of this facilitator
      $route = '/facilitators/' . $facilitatorId . '/eventDates';
      $api = self::getSeminarDeskApi();
      $data = self::getApiController()->getSeminarDeskData($api, $route);

      if (is_object($data) && $data) {
        $eventDatesBody = json_decode($data->body);
        if ($eventDatesBody && $eventDatesBody->data) {
          $facilitator->eventDates = $eventDatesBody->data;

          //-- Get values in current language, with fallback to first language in set
          foreach ($facilitator->eventDates as $key => &$eventDate) {
            self::prepareEventDate($eventDate);
          }
        }
        else {
          $facilitator->eventDates = null;
        }
      }
      else {
        $facilitator->eventDates = [];
      }
      
      //-- Get values in current language, with fallback to first language in set
      self::prepareFacilitator($facilitator);
    }
    
    else {
      // Error handling
      JLog::add(
        'loadFacilitator('.$id.') failed! ($facilitatorData = ' . json_encode($data) . ')', 
        JLog::ERROR, 
        'com_seminardesk'
      );
      $facilitator = [];
    }
    
    return $facilitator;
  }

  /**
   * Preprocess / prepare fields of event date for use in views
   * 
   * @param stdClass $eventDate
   */
  public static function prepareEventDate(&$eventDate)
  {
    // Map and translate fields - some have changed the structure (new sub object "eventInfo"), so include a fallback
    $eventDate->eventId = $eventDate->eventId??$eventDate->eventInfo->id;
    $eventDate->title = self::translate($eventDate->title, true);
    $eventDate->titleSlug = self::translate($eventDate->titleSlug??$eventDate->eventInfo->titleSlug);
    $eventDate->subtitle = self::translate($eventDate->subtitle??$eventDate->eventInfo->subtitle);
    $eventDate->eventDateTitle = self::translate($eventDate->eventDateTitle, true);
    $eventDate->teaser = self::translate($eventDate->teaser??$eventDate->eventInfo->teaser);
    $eventDate->teaserPictureUrl = self::translate($eventDate->teaserPictureUrl??$eventDate->eventInfo->teaserPictureUrl);
    $eventDate->detailpageAvailable = $eventDate->detailpageAvailable??$eventDate->eventInfo->detailpageAvailable??true;
//    $eventDate->description = self::translate($eventDate->eventInfo->description); // See below: Adding to html response slows down page loading
    // Set 2nd title / subtitle to eventDateTitle, subtitile or teaser, if different from title
    $eventDate->mergedSubtitle = ($eventDate->title != $eventDate->eventDateTitle)?$eventDate->eventDateTitle:'';
    $eventDate->mergedSubtitle .= (!$eventDate->mergedSubtitle && $eventDate->title != $eventDate->subtitle)?$eventDate->subtitle:'';
//    $eventDate->mergedSubtitle .= (!$eventDate->mergedSubtitle && $eventDate->title != $eventDate->teaser)?$eventDate->teaser:'';

    // Additional fields: Link to event website
    $eventDate->website = '';
    foreach ($eventDate->additionalFields as $item) {
      if ($item->field->name == 'Event-Website') {
        $eventDate->website = $item->value;
        break;
      }
    }

    // Add facilitators list
    $eventDate->facilitators = array_combine(
      array_column($eventDate->facilitators, 'id'), 
      array_column($eventDate->facilitators, 'name')
    );
    $eventDate->facilitatorsList = htmlentities(implode(', ', $eventDate->facilitators), ENT_QUOTES);
    // Add labels list
    $eventDate->labels = array_combine(
      array_column($eventDate->labels, 'id'), 
      array_column($eventDate->labels, 'name')
    );
    $eventDate->labelsList = htmlspecialchars(implode(', ', $eventDate->labels), ENT_QUOTES);
    // Get categories: Labels except LABELS_TO_HIDE
    $eventDate->categories = array_filter($eventDate->labels, function($key){
      return !in_array($key, self::LABELS_TO_HIDE);
    }, ARRAY_FILTER_USE_KEY);
    $eventDate->categoriesList = htmlspecialchars(implode(', ', $eventDate->categories), ENT_QUOTES);
//    $eventDate->categoryLinks = implode(', ', SeminardeskDataHelper::getCategoryLinks($eventDate->categories));
    $eventDate->statusLabel = SeminardeskDataHelper::getStatusLabel($eventDate);
    // Add searchable text for filters
    $eventDate->searchableText = htmlspecialchars(strip_tags(implode(' ', [
        $eventDate->title,
        $eventDate->subtitle,
        $eventDate->eventDateTitle,
        $eventDate->teaser,
//        $eventDate->description, // Slows down page loading (+150kB / +0.5s)
        $eventDate->facilitatorsList,
        implode(' ', array_keys($eventDate->categories)),
        $eventDate->labelsList,
    ])));
    
    // Fix event date / time
    $eventDate->beginDate = $eventDate->beginDate / 1000;
    $eventDate->endDate = $eventDate->endDate / 1000;

    //-- Set special event flags (festivals, external organisers)
    $eventDate->isFeatured = self::hasLabel($eventDate, self::LABELS_FESTIVALS_ID);
    $eventDate->isExternal = self::hasLabel($eventDate, self::LABELS_EXTERNAL_ID);
    $eventDate->onApplication = self::hasLabel($eventDate, self::LABELS_ON_APPLICATION_ID);
    $eventDate->isPastEvent = $eventDate->endDate < time();

    //-- Set event classes
    $classes = ['registration-available'];
    if ($eventDate->isFeatured)           { $classes[] = 'featured';         }
    if (!$eventDate->detailpageAvailable) { $classes[] = 'no-detail-page';  }
//    if ($eventDate->categoriesList)     { $classes[] = 'has-categories';   } // Hide categories in List for now
    if ($eventDate->facilitatorsList)     { $classes[] = 'has-facilitators'; }
    if ($eventDate->website)              { $classes[] = 'has-event-website'; }
    if ($eventDate->isExternal)           { $classes[] = 'external-event';   } 
    if (!$eventDate->isExternal)          { $classes[] = 'zegg-event';       }
    if ($eventDate->isPastEvent)          { $classes[] = 'past-event';       }
    if ($eventDate->status == 'canceled') { $classes[] = 'is-canceled';      }
    $eventDate->cssClasses = implode(' ', $classes);

    //-- Format date
    $eventDate->dateFormatted = SeminardeskDataHelper::getDateFormatted($eventDate->beginDate, $eventDate->endDate);

    //-- URLs
    $eventDate->detailsUrl = ($eventDate->detailpageAvailable)?SeminardeskDataHelper::getEventUrl($eventDate):'';
    $eventDate->bookingUrl = SeminardeskDataHelper::getBookingUrl($eventDate->eventId, $eventDate->titleSlug, $eventDate->id);
  }
  
  /**
   * Preprocess / prepare fields of event for use in views
   * 
   * @param stdClass $event
   */
  public static function prepareEvent(&$event)
  {
    $config = self::getConfiguration();
    $app = Factory::getApplication();
    
    //-- Translations
    $event->title = self::translate($event->title, true);
    $event->titleSlug = self::translate($event->titleSlug);
    $event->subtitle = self::translate($event->subtitle, true);
    $event->teaser = self::translate($event->teaser);
    
    $event->labels = array_combine(
      array_column($event->labels, 'id'), 
      array_column($event->labels, 'name')
    );
    $event->isExternal = self::hasLabel($event, self::LABELS_EXTERNAL_ID);
    
    //-- Get categories: Labels except LABELS_TO_HIDE
    $event->categories = array_filter($event->labels, function($key){
      return !in_array($key, self::LABELS_TO_HIDE);
    }, ARRAY_FILTER_USE_KEY);
    //-- Prepare event labels list (categories)
    $event->catLinks = [];
    foreach( $event->categories as $cat_id => $cat_name) { 
      $event->catLinks[] = '<a href="' . JRoute::_($config['eventlist_base'] . '&view=events') . '?cat=' . $cat_id . '">' . $cat_name . '</a>';
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
    $event->infoDatesPrices = self::cleanupHtml(self::translate($event->infoDatesPrices), '<h1><h2><h3><h4><p><br><b><hr><strong><a>', false);
    $event->infoBoardLodging = self::translate($event->infoBoardLodging);
    $event->infoMisc = self::translate($event->infoMisc);
    $event->bookingUrl = SeminardeskDataHelper::getBookingUrl($event->id, $event->titleSlug);
    foreach($event->facilitators as $key => $facilitator) {
      self::prepareFacilitator($event->facilitators[$key]);
    }
    //-- Prepare event dates
    $count_canceled = 0;
    $event->onApplication = self::hasLabel($event, self::LABELS_ON_APPLICATION_ID);
    $event->isBookable = false;
    foreach($event->dates as $key => $date) {
      $date->title = self::translate($date->title);
      $date->labels = array_combine(
        array_column($date->labels, 'id'), 
        array_column($date->labels, 'name')
      );
      
      //-- Format date
      $date->beginDate = $date->beginDate / 1000;
      $date->endDate = $date->endDate / 1000;
      $date->dateFormatted = SeminardeskDataHelper::getDateFormatted($date->beginDate, $date->endDate);

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
      $date->lodgingPrices = self::getLodgingPrices($date);
      $date->boardPrices = self::getBoardPrices($date);
//      foreach($date->availableMisc as $key => $misc) {
//        $date->availableMisc[$key]->title = self::translate($misc->title);
//        $date->availableMisc[$key]->prices = self::translate($misc->prices);
//      }

      //-- Booking
      $date->isExternal = self::hasLabel($date, self::LABELS_EXTERNAL_ID);
      $date->bookingUrl = SeminardeskDataHelper::getBookingUrl($event->id, $event->titleSlug, $date->id);
      $date->statusLabel = SeminardeskDataHelper::getStatusLabel($date);
      $event->isExternal = $event->isExternal || $date->isExternal;
      $date->onApplication = $event->onApplication || self::hasLabel($date, self::LABELS_ON_APPLICATION_ID);
      $date->isBookable = (
        $event->settings->registrationAvailable 
        && $date->registrationAvailable 
        && $date->status != "fully_booked" 
        && $date->status != "canceled" 
        && !$date->isPastEvent
        // For events and dates with "on application" status, only show booking button if url param "bookable" is set
        && (!$date->onApplication || $app->input->exists('bookable'))
      );
      $event->isBookable = $event->isBookable || $date->isBookable;
      if ($date->status == 'canceled') $count_canceled++;
    }
    
    //-- Get list of dates, limit to 5
    $event->datesList = array_column($event->dates, 'dateFormatted');
    $count = count($event->datesList);
    if ($count > 5) {
      $event->datesList = array_slice($event->datesList, 0, 5);
      array_push($event->datesList, '...');
    }
    //-- Add labels to dates list for canceled dates
    if (count($event->dates) > 1 && $count_canceled == count($event->dates)) {
      // >1 dates and ALL canceled: ONE label for all dates
      $event->datesList[0] = JText::_("COM_SEMINARDESK_EVENT_ALL_CANCELED") . ': ' . $event->datesList[0];
    } elseif ($count_canceled > 0) {
      // Only one date, or >1 and some events canceled, others not 
      // => Individual labels per date
      foreach($event->dates as $key => $date) {
        if ($date->status == 'canceled') {
          $event->datesList[$key] .= ' (' . JText::_("COM_SEMINARDESK_EVENT_CANCELED") . ')';
        }
      }
    }
  }
  
  /**
   * Preprocess fields of facilitator for use in views
   * 
   * @param stdClass $facilitator
   */
  public static function prepareFacilitator(&$facilitator)
  {
    //-- Fullname (title + name), translations and URLs
    $facilitator->fullName = trim(implode(' ', [$facilitator->title, $facilitator->name]));
    $facilitator->about = self::cleanupFormatting(self::translate($facilitator->about));
    $facilitator->detailsUrl = SeminardeskDataHelper::getFacilitatorUrl($facilitator);
    
    //-- Add css classes
    $classes = ['facilitator'];
    if (!$facilitator->pictureUrl) { $classes[] = 'no-image'; }
    if (!$facilitator->about)      { $classes[] = 'no-description'; }
    $facilitator->cssClasses = implode(' ', $classes);

    //-- Sort event dates by beginDate (not done by SeminarDesk, has been announced 2022)
    usort($facilitator->eventDates, function($a, $b) {
        return strcmp($a->beginDate, $b->beginDate);
    });
    
    //-- Separate past and future events. Past events only from 1 year ago and in reverse order
    $facilitator->pastEventDates = array_filter($facilitator->eventDates, function($eventDate) {
      return $eventDate->endDate < time() && (intval(date("Y")) - intval(date("Y", $eventDate->endDate)) <= 1);
    });
    $facilitator->pastEventDates = array_reverse($facilitator->pastEventDates);
    $facilitator->eventDates = array_filter($facilitator->eventDates, function($eventDate) {
      return $eventDate->endDate >= time();
    });
  }
  
}
