<?php

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Log\Log;

class SeminardeskApiController extends BaseController
{
  //private $cacheHelper;
  private $cache;
  //private $logger;

  public function __construct($config = array())
  {
    parent::__construct($config);
    //$this->cacheHelper = new SeminardeskCacheHelper();
    $this->cache = Factory::getCache('com_seminardesk', 'output');
    //$this->cache = Factory::getCache('com_seminardesk', '');
    //$this->logger = Log::getLogger('com_seminardesk');
  }

  public function getSeminarDeskData($api, $route)
  {
    $cacheKey = 'seminardesk_api_' . str_replace('/', '_', $route); // Replace slashes with underscores to avoid cache key issues

    $data = $this->cache->get($cacheKey);
    if (!$data) {
      $data = $this->fetchDataFromApi($api, $route);
      $this->cache->store($data, $cacheKey);
    }
    return $data;
  }

  protected function fetchDataFromApi($api, $route)
  {
    $connector = HttpFactory::getHttp();
    try {
      $data = $connector->get($api . $route);
    } catch (\Exception $exception) {
      Log::add('Failed to fetch remote IP data: ' . $exception->getMessage(), Log::ERROR, 'com_seminardesk');
      //$this->logger->error('Failed to fetch remote IP data: ' . $exception->getMessage());
      $data = 'Failed to fetch remote IP data: ' . $exception->getMessage();
    }
    return $data;
  }
  
}
