<?php
/**
 * @package     Com_Seminardesk
 * @author      Benno Flory <benno.flory@gmx.ch>
 * @copyright   2022 Benno Flory
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\CategoryFactory;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Component\Seminardesk\Administrator\Extension\SeminardeskComponent;
use Joomla\Component\Seminardesk\Site\Service\ApiService;
use Joomla\Component\Seminardesk\Site\Service\ConfigService;
use Joomla\Component\Seminardesk\Site\Service\EventDateService;
use Joomla\Component\Seminardesk\Site\Service\EventService;
use Joomla\Component\Seminardesk\Site\Service\FacilitatorService;
use Joomla\Component\Seminardesk\Site\Service\SeminardeskService;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     */
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new CategoryFactory('\\Joomla\\Component\\Seminardesk'));
        $container->registerServiceProvider(new MVCFactory('\\Joomla\\Component\\Seminardesk'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Joomla\\Component\\Seminardesk'));
        $container->registerServiceProvider(new RouterFactory('\\Joomla\\Component\\Seminardesk'));

        // Register ConfigService
        $container->set(
            ConfigService::class,
            function (Container $container) {
                return new ConfigService(
                    $container->get(CMSApplicationInterface::class)
                );
            }
        );

        // Register ApiService
        $container->set(
            ApiService::class,
            function (Container $container) {
                return new ApiService(
                    $container->get(ConfigService::class),
                    $container->get(CacheControllerFactoryInterface::class)
                );
            }
        );

        // Register EventDateService
        $container->set(
            EventDateService::class,
            function (Container $container) {
                return new EventDateService(
                    $container->get(ConfigService::class),
                    $container->get(ApiService::class)
                );
            }
        );

        // Register FacilitatorService
        $container->set(
            FacilitatorService::class,
            function (Container $container) {
                $service = new FacilitatorService(
                    $container->get(ConfigService::class),
                    $container->get(ApiService::class)
                );
                // Inject EventDateService for preparing event dates
                $service->setEventDateService($container->get(EventDateService::class));
                return $service;
            }
        );

        // Register EventService
        $container->set(
            EventService::class,
            function (Container $container) {
                return new EventService(
                    $container->get(ConfigService::class),
                    $container->get(ApiService::class),
                    $container->get(FacilitatorService::class)
                );
            }
        );

        // Register SeminardeskService (legacy, may be removed)
        $container->set(
            SeminardeskService::class,
            function (Container $container) {
                return new SeminardeskService(
                    $container->get(MVCFactoryInterface::class),
                    $container->get(DatabaseInterface::class),
                    $container->get(CMSApplicationInterface::class)
                );
            }
        );

        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new SeminardeskComponent($container->get(ComponentDispatcherFactoryInterface::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                $component->setRouterFactory($container->get(RouterFactoryInterface::class));

                return $component;
            }
        );
    }
};
