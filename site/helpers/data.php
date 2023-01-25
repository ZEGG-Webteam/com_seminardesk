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
  const DEFAULT_TENANT_ID = 'zegg';
  const LABELS_FESTIVALS_ID = 12;
  const LABELS_EXTERNAL_ID = 55;
  const LABELS_TO_HIDE = [
      1, 2, 3, 53, 54, // Languages
//      self::LABELS_FESTIVALS_ID, 
      self::LABELS_EXTERNAL_ID, 
  ];
  const FACILITATORS_TO_HIDE = [
      '& Team',
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
      $tenant_id = $app->input->get('tenant_id', self::DEFAULT_TENANT_ID, 'STRING');
      $events_menu = $app->getMenu()->getActive()->query['events_page']?:$app->getMenu()->getActive()->id;
      $facilitators_menu = $app->getMenu()->getActive()->query['facilitators_page']?:$app->getMenu()->getActive()->id;
      
      self::$config = [
        'tenant_id' => $tenant_id,
        'langKey' => $langKey,
        'api' => 'https://' . $tenant_id . '.seminardesk.de/api',
        'booking_base' => 'https://booking.seminardesk.de/' . strtolower($langKey) . '/' . $tenant_id . '/',
        'eventlist_base' => 'index.php?option=com_seminardesk&Itemid=' . $events_menu . '&lang=' . strtolower($langKey) , 
        'facilitators_base' => 'index.php?option=com_seminardesk&Itemid=' . $facilitators_menu . '&lang=' . strtolower($langKey) , 
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
    return ($htmlencode) ? htmlentities($value, ENT_QUOTES) : $value;
  }

  /**
   * Get SeminarDesk API url
   *
   * @return  string Json data from Seminardesk
   * @since   3.0
   */
  public static function buildSeminarDeskApiLink($route) {
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
    if ($event->registrationAvailable && $event->status) {
      $label = JText::_("COM_SEMINARDESK_EVENTS_STATUS_" . strtoupper($event->status));
    }
    return $label;
  }
  
  /**
   * 
   * @param stdClass $eventDate
   * @param array $filters - containing keys 'date', 'cat' and 'org'
   * @return boolean - true if eventDate matches all filters, false if not.
   */
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

  /**
   * Load EventDates from SeminarDesk API
   *
   * @param $filter array - e.g. ['labels' => '1,2,3'] or ['labels' => [1],[2],[3]]
   * @return  array Event Dates (stdClass)
   * @since   3.0
   */
  public static function loadEventDates($filter = [])
  {
    $config = self::getConfiguration();
    $api_uri = self::buildSeminarDeskApiLink('/eventDates');
    $eventDatesData = self::getSeminarDeskData($api_uri);
    
    if (is_object($eventDatesData) && $eventDatesData) {
      $eventDates = json_decode($eventDatesData->body)->dates;
      
      //-- Get values in current language, with fallback to first language in set
      foreach ($eventDates as $key => &$eventDate) {
        self::prepareEventDate($eventDate);
      }
      
      //-- Apply filters
      // convert labels to array and trim each label value
      $filter['labels'] = isset($filter['labels'])?array_map('trim', explode(',', $filter['labels'])):[];

      $eventDates = array_filter($eventDates, function ($eventDate) use ($filter) {
        
        //-- Filter by labels (IDs or labels)
        if ($filter['labels']) {
          // Allow both IDs or label text as filter
          $eventLabels = (is_numeric($filter['labels'][0]))?array_keys($eventDate->labels):$eventDate->labels;
          // Compare labels with filter. If some are matching, return event
          return array_intersect($eventLabels, $filter['labels']);
        }
        else {
          return true;
        }
      });
      
      //-- Limit
      if (isset($filter['limit']) && $filter['limit'] > 0) {
        $eventDates = array_slice($eventDates, 0, $filter['limit']);
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
  public static function loadEvent($eventId)
  {
    $config = self::getConfiguration();
    $api_uri = self::buildSeminarDeskApiLink('/events/' . $eventId);
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
   * Load Facilitators from SeminarDesk API
   *
   * @return  array Facilitators (stdClass)
   * @since   3.0
   */
  public static function loadFacilitators()
  {
    $config = self::getConfiguration();
    $api_uri = self::buildSeminarDeskApiLink('/facilitators');
    $facilitatorsData = self::getSeminarDeskData($api_uri);
    
    if (is_object($facilitatorsData) && $facilitatorsData) {
      $facilitators = json_decode($facilitatorsData->body)->data;
      
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
    $config = self::getConfiguration();
    $api_uri = self::buildSeminarDeskApiLink('/facilitators/' . $facilitatorId);
    $facilitatorData = self::getSeminarDeskData($api_uri);
    $facilitator = null;
    
    if (is_object($facilitatorData) && $facilitatorData) {
      $facilitator = json_decode($facilitatorData->body);
      
      //-- Load events of this facilitator
      $api_uri = self::buildSeminarDeskApiLink('/facilitators/' . $facilitatorId . '/eventDates');
      $eventDatesData = self::getSeminarDeskData($api_uri);

      if (is_object($eventDatesData) && $eventDatesData) {
        $facilitator->eventDates = json_decode($eventDatesData->body)->data;

        //-- Get values in current language, with fallback to first language in set
        foreach ($facilitator->eventDates as $key => &$eventDate) {
          self::prepareEventDate($eventDate);
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
        'loadFacilitator('.$id.') failed! ($facilitatorData = ' . json_encode($facilitatorData) . ')', 
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
    $eventDate->title = self::translate($eventDate->title, true);
    $eventDate->titleSlug = self::translate($eventDate->titleSlug);
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
      return !in_array($key, self::LABELS_TO_HIDE);
    }, ARRAY_FILTER_USE_KEY);
    $eventDate->categoriesList = htmlentities(implode(', ', $eventDate->categories), ENT_QUOTES);
//    $eventDate->categoryLinks = implode(', ', SeminardeskHelperData::getCategoryLinks($eventDate->categories));
    $eventDate->statusLabel = SeminardeskHelperData::getStatusLabel($eventDate);

    //-- Set special event flags (festivals, external organisers)
    $eventDate->isFeatured = array_key_exists(SeminardeskHelperData::LABELS_FESTIVALS_ID, $eventDate->labels);
    $eventDate->isExternal = array_key_exists(SeminardeskHelperData::LABELS_EXTERNAL_ID, $eventDate->labels);
    $eventDate->showDateTitle = ($eventDate->eventDateTitle && $eventDate->eventDateTitle != $eventDate->title);

    //-- Set event classes
    $classes = ['registration-available'];
    if ($eventDate->isFeatured)       { $classes[] = 'featured';         }
//    if ($eventDate->categoriesList)   { $classes[] = 'has-categories';   } // Hide categories in List for now
    if ($eventDate->facilitatorsList) { $classes[] = 'has-facilitators'; }
    if ($eventDate->isExternal)       { $classes[] = 'external-event';   } 
    if (!$eventDate->isExternal)      { $classes[] = 'zegg-event';       }
    $eventDate->cssClasses = implode(' ', $classes);

    //-- Format date
    $eventDate->beginDate = $eventDate->beginDate / 1000;
    $eventDate->endDate = $eventDate->endDate / 1000;
    $eventDate->dateFormatted = SeminardeskHelperData::getDateFormatted($eventDate->beginDate, $eventDate->endDate);

    //-- URLs
    $eventDate->detailsUrl = SeminardeskHelperData::getEventUrl($eventDate);
    $eventDate->bookingUrl = SeminardeskHelperData::getBookingUrl($eventDate->eventId, $eventDate->titleSlug, $eventDate->id);
  }
  
  /**
   * Preprocess / prepare fields of event for use in views
   * 
   * @param stdClass $event
   */
  public static function prepareEvent(&$event)
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
    $event->infoDatesPrices = self::cleanupHtml(self::translate($event->infoDatesPrices));
    $event->infoBoardLodging = self::translate($event->infoBoardLodging);
    $event->infoMisc = self::translate($event->infoMisc);
    $event->bookingUrl = SeminardeskHelperData::getBookingUrl($event->id, $event->titleSlug);
    foreach($event->facilitators as $key => $facilitator) {
      self::prepareFacilitator($event->facilitators[$key]);
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
        $date->attendanceFees[$key]->fullName = self::translate($fee->name);
      }
//      foreach($date->availableMisc as $key => $misc) {
//        $date->availableMisc[$key]->title = self::translate($misc->title);
//        $date->availableMisc[$key]->prices = self::translate($misc->prices);
//      }

      //-- Booking
      $date->bookingUrl = SeminardeskHelperData::getBookingUrl($event->id, $event->titleSlug, $date->id);
      $date->statusLabel = SeminardeskHelperData::getStatusLabel($date);
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
    $facilitator->fullName = implode(' ', [$facilitator->title, $facilitator->name]);
    $facilitator->about = self::cleanupFormatting(self::translate($facilitator->about));
    $facilitator->detailsUrl = SeminardeskHelperData::getFacilitatorUrl($facilitator);
  }
  
}
