<?php
/**
 * @package     Com_Seminardesk
 * @subpackage  Site
 * @author      Benno Flory <benno.flory@gmx.ch>
 * @copyright   2022-2026 Benno Flory
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Seminardesk\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Component\Seminardesk\Site\Helper\FormatHelper;
use Joomla\Component\Seminardesk\Site\Helper\TranslationHelper;

/**
 * Event Service for SeminarDesk
 * Handles all logic related to events, such as loading from the API and formatting.
 *
 * @since  2.0.0
 */
class EventService
{
    /**
     * @var ConfigService
     */
    private ConfigService $configService;

    /**
     * @var ApiService
     */
    private ApiService $apiService;

    /**
     * @var FacilitatorService
     */
    private FacilitatorService $facilitatorService;

    /**
     * Constructor
     *
     * @param  ConfigService      $configService      The config service
     * @param  ApiService         $apiService         The API service
     * @param  FacilitatorService $facilitatorService The facilitator service
     */
    public function __construct(
        ConfigService $configService,
        ApiService $apiService,
        FacilitatorService $facilitatorService
    ) {
        $this->configService = $configService;
        $this->apiService = $apiService;
        $this->facilitatorService = $facilitatorService;
    }

    /**
     * Load a single event from SeminarDesk API
     *
     * @param string $eventId
     * @return object|array Event or empty array
     *
     * @since   3.0
     */
    public function loadEvent($eventId)
    {
        $event = $this->apiService->getEvent($eventId);
        
        if ($event) {
            // Get translations, labels, facilitators, categories etc. for event
            $this->prepareEvent($event);
            $event->apiUri = $this->configService->getApiUrl() . '/events/' . $eventId;
            $event->langKey = $this->configService->getCurrentLanguageKey();
        }
        else {
            $event = [];
        }
        
        return $event;
    }

    /**
     * Get url for SeminarDesk booking
     * 
     * @param integer $eventId
     * @param string $slug
     * @param integer|array $eventDateIds
     * @return string URL to embedded event booking form
     */
    public function getBookingUrl($eventId, $slug, $eventDateIds = []): string
    {
        $config = $this->configService->getConfiguration();
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
     * Get all accommodation prices excluding given config list (exÜn).
     * 
     * @param object $date - A single date of an event - event->date
     * @return array - List of all lodging prices, except for $config['lodging_to_exclude']
     */
    public function getLodgingPrices($date): array
    {
        $config = $this->configService->getConfiguration();
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
    public function getBoardPrices($date): array
    {
        $boardPrices = [];
        foreach ($date->availableBoard as $board) {
            $boardPrices = array_merge($boardPrices, array_column($board->prices, 'price'));
        }
        return $boardPrices;
    }

    /**
     * Get status label for a date
     * 
     * @param stdClass $date
     * @return string
     */
    public function getStatusLabel($date): string
    {
        $label = '';
        if ($date->status) {
            $key = "COM_SEMINARDESK_EVENTS_STATUS_" . strtoupper($date->status);
            
            if (property_exists($date, 'detailpageAvailable') && !$date->detailpageAvailable) {
                $key = "COM_SEMINARDESK_EVENTS_STATUS_DETAILS_LATER";
            }
            if (FormatHelper::hasLabel($date, ConfigService::LABELS_WITHOUT_REGISTRATION_ID)) {
                $key = "COM_SEMINARDESK_EVENTS_STATUS_WITHOUT_REGISTRATION";
            }
            if (FormatHelper::hasLabel($date, ConfigService::LABELS_ON_APPLICATION_WITH_REGISTRATION_ID)) {
                $key = "COM_SEMINARDESK_EVENTS_STATUS_AVAILABLE";
            }
            if (time() > $date->endDate) {
                $key = "COM_SEMINARDESK_EVENTS_STATUS_PAST_EVENT";
            }
            
            $label = Text::_($key);
            if ($label == $key) {
                $label = ucwords($date->status, "_");
            }
        }
        return $label;
    }

    /**
     * Preprocess / prepare fields of event for use in views
     * 
     * @param stdClass $event
     */
    public function prepareEvent(&$event): void
    {
        $config = $this->configService->getConfiguration();
        $app = Factory::getApplication();
        
        // Translations
        $event->title = TranslationHelper::translate($event->title, true);
        $event->titleSlug = TranslationHelper::translate($event->titleSlug);
        $event->subtitle = TranslationHelper::translate($event->subtitle, true);
        $event->teaser = TranslationHelper::translate($event->teaser);
        
        $event->labels = array_combine(
            array_column($event->labels, 'id'), 
            array_column($event->labels, 'name')
        );
        $event->isExternal = FormatHelper::hasLabel($event, ConfigService::LABELS_EXTERNAL_ID);
        
        // Get categories: Labels except LABELS_TO_HIDE
        $event->categories = array_filter($event->labels, function($key){
            return !in_array($key, ConfigService::LABELS_TO_HIDE);
        }, ARRAY_FILTER_USE_KEY);
        
        // Prepare event labels list (categories)
        $event->catLinks = [];
        foreach($event->categories as $cat_id => $cat_name) { 
            $event->catLinks[] = '<a href="' . Route::_($config['eventlist_base'] . '&view=events') . '?cat=' . $cat_id . '">' . $cat_name . '</a>';
        }
        $event->catLinks = implode(', ', $event->catLinks);
        
        $event->description = TranslationHelper::translate($event->description);
        // Bugfix (Joomla / SeminarDesk bug): Some descriptions contain inline images data which are blasting Joomla's regex limit (pcre.backtrack_limit).
        // => remove images from description and add class which will trigger async loading by JS.
        $event->descriptionTooLong = strlen($event->description) > ini_get("pcre.backtrack_limit");
        if ($event->descriptionTooLong) {
            $event->description = preg_replace("/<img[^>]+\>/i", ' (' . Text::_("COM_SEMINARDESK_EVENT_LOADING_IMAGES") . ') ', $event->description); 
        }
        $event->description = FormatHelper::cleanupFormatting($event->description);
        
        $event->headerPictureUrl = TranslationHelper::translate($event->headerPictureUrl);
        $event->infoDatesPrices = FormatHelper::cleanupHtml(TranslationHelper::translate($event->infoDatesPrices), '<h1><h2><h3><h4><p><br><b><hr><strong><a><ul><ol><li>', false);
        $event->infoBoardLodging = TranslationHelper::translate($event->infoBoardLodging);
        $event->infoMisc = TranslationHelper::translate($event->infoMisc);
        $event->bookingUrl = $this->getBookingUrl($event->id, $event->titleSlug);
        
        foreach($event->facilitators as $key => $facilitator) {
            $this->facilitatorService->prepareFacilitator($event->facilitators[$key]);
        }
        
        // Prepare event dates
        $count_canceled = 0;
        $event->onApplication = FormatHelper::hasLabel($event, ConfigService::LABELS_ON_APPLICATION_ID);
        $event->availableDespiteWaitlist = FormatHelper::hasLabel($event, ConfigService::LABELS_ON_APPLICATION_WITH_REGISTRATION_ID);
        $event->isBookable = false;
        $event->isSelfAssessment = false;
        
        // Sort dates by beginDate
        usort($event->dates, function($a, $b) {
            return $a->beginDate - $b->beginDate;
        });
        
        foreach($event->dates as $key => $date) {
            $date->title = TranslationHelper::translate($date->title);
            $date->labels = array_combine(
                array_column($date->labels, 'id'), 
                array_column($date->labels, 'name')
            );
            
            // Format date
            $date->beginDate = $date->beginDate / 1000;
            $date->endDate = $date->endDate / 1000;
            $date->dateFormatted = FormatHelper::getDateFormatted($date->beginDate, $date->endDate);

            // Prepare facilitator list, if not same as in event. Remove otherwise
            $date->facilitatorLinks = [];
            if (array_intersect(array_column($event->facilitators, 'id'), array_column($date->facilitators, 'id'))) {
                foreach ($date->facilitators as $facilitator) { 
                    $date->facilitatorLinks[] = '<span title="Details comming soon...">' . $facilitator->name . '</span>';
                }
                $date->facilitatorLinks = implode(', ', $date->facilitatorLinks);
            }
            
            // Prices & fees
            foreach ($date->attendanceFees as $feeKey => $fee) {
                $date->attendanceFees[$feeKey]->name = TranslationHelper::translate($fee->name);
                $event->isSelfAssessment = $event->isSelfAssessment || $fee->isSelfAssessment;
            }
            $date->lodgingPrices = $this->getLodgingPrices($date);
            $date->boardPrices = $this->getBoardPrices($date);

            // Booking
            $date->isExternal = FormatHelper::hasLabel($date, ConfigService::LABELS_EXTERNAL_ID);
            $event->isExternal = $event->isExternal || $date->isExternal;
            $date->bookingUrl = $this->getBookingUrl($event->id, $event->titleSlug, $date->id);
            $date->statusLabel = $this->getStatusLabel($date);
            $date->onApplication = $event->onApplication || FormatHelper::hasLabel($date, ConfigService::LABELS_ON_APPLICATION_ID);
            $date->availableDespiteWaitlist = FormatHelper::hasLabel($date, ConfigService::LABELS_ON_APPLICATION_WITH_REGISTRATION_ID);
            $event->availableDespiteWaitlist = $event->availableDespiteWaitlist || $date->availableDespiteWaitlist;
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
        
        // Get list of dates, limit to 5
        $event->datesList = array_column($event->dates, 'dateFormatted');

        $count = count($event->datesList);
        if ($count > 5) {
            $event->datesList = array_slice($event->datesList, 0, 5);
            array_push($event->datesList, '...');
        }
        
        // Add labels to dates list for canceled dates
        if (count($event->dates) > 1 && $count_canceled == count($event->dates)) {
            // >1 dates and ALL canceled: ONE label for all dates
            $event->datesList[0] = Text::_("COM_SEMINARDESK_EVENT_ALL_CANCELED") . ': ' . $event->datesList[0];
        } elseif ($count_canceled > 0) {
            // Only one date, or >1 and some events canceled, others not 
            // => Individual labels per date
            foreach($event->dates as $key => $date) {
                if ($date->status == 'canceled') {
                    $event->datesList[$key] .= ' (' . Text::_("COM_SEMINARDESK_EVENT_CANCELED") . ')';
                }
            }
        }
    }
}