<?php
/**
 * @package     Com_Seminardesk
 * @author      Benno Flory <benno.flory@gmx.ch>
 * @copyright   2022 Benno Flory
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Seminardesk\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;

/**
 * API Controller for SeminarDesk
 *
 * @since  2.0.0
 */
class ApiController extends BaseController
{
    /**
     * @var object Cache instance
     */
    private $cache;

    /**
     * Constructor
     *
     * @param   array  $config  Configuration array
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->cache = Factory::getCache('com_seminardesk', 'output');
    }

    /**
     * Get data from SeminarDesk API with caching
     *
     * @param   string  $api    The API base URL
     * @param   string  $route  The API route
     *
     * @return  mixed  The API response data
     */
    public function getSeminarDeskData(string $api, string $route)
    {
        $cacheKey = 'seminardesk_api_' . str_replace('/', '_', $route);

        $data = $this->cache->get($cacheKey);
        if (!$data) {
            $data = $this->fetchDataFromApi($api, $route);
            $this->cache->store($data, $cacheKey);
        }
        
        return $data;
    }

    /**
     * Fetch data from the API
     *
     * @param   string  $api    The API base URL
     * @param   string  $route  The API route
     *
     * @return  mixed  The API response
     */
    protected function fetchDataFromApi(string $api, string $route)
    {
        $connector = HttpFactory::getHttp();
        
        try {
            $data = $connector->get($api . $route);
        } catch (\Exception $exception) {
            Log::add('Failed to fetch remote IP data: ' . $exception->getMessage(), Log::ERROR, 'com_seminardesk');
            $data = 'Failed to fetch remote IP data: ' . $exception->getMessage();
        }
        
        return $data;
    }
}
