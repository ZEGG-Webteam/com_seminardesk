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

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;

/**
 * Service Factory for SeminarDesk
 * Provides lazy-loaded singleton instances of all services.
 * 
 * @since  3.0.0
 */
class ServiceFactory
{
    private static ?ConfigService $configService = null;
    private static ?ApiService $apiService = null;
    private static ?EventDateService $eventDateService = null;
    private static ?EventService $eventService = null;
    private static ?FacilitatorService $facilitatorService = null;

    /**
     * Get ConfigService instance
     */
    public static function getConfigService(): ConfigService
    {
        if (self::$configService === null) {
            self::$configService = new ConfigService(Factory::getApplication());
        }
        return self::$configService;
    }

    /**
     * Get ApiService instance
     */
    public static function getApiService(): ApiService
    {
        if (self::$apiService === null) {
            self::$apiService = new ApiService(
                self::getConfigService(),
                Factory::getContainer()->get(CacheControllerFactoryInterface::class)
            );
        }
        return self::$apiService;
    }

    /**
     * Get EventDateService instance
     */
    public static function getEventDateService(): EventDateService
    {
        if (self::$eventDateService === null) {
            self::$eventDateService = new EventDateService(
                self::getConfigService(),
                self::getApiService()
            );
        }
        return self::$eventDateService;
    }

    /**
     * Get FacilitatorService instance (with EventDateService injected)
     */
    public static function getFacilitatorService(): FacilitatorService
    {
        if (self::$facilitatorService === null) {
            self::$facilitatorService = new FacilitatorService(
                self::getConfigService(),
                self::getApiService()
            );
            self::$facilitatorService->setEventDateService(self::getEventDateService());
        }
        return self::$facilitatorService;
    }

    /**
     * Get EventService instance
     */
    public static function getEventService(): EventService
    {
        if (self::$eventService === null) {
            self::$eventService = new EventService(
                self::getConfigService(),
                self::getApiService(),
                self::getFacilitatorService()
            );
        }
        return self::$eventService;
    }

    /**
     * Reset all service instances (useful for testing)
     */
    public static function reset(): void
    {
        self::$configService = null;
        self::$apiService = null;
        self::$eventDateService = null;
        self::$eventService = null;
        self::$facilitatorService = null;
    }
}
