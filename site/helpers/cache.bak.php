<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
//use Joomla\CMS\Cache\CacheController;

class SeminardeskCacheHelper
{
    private $cache;

    public function __construct()
    {
        //$this->cache = Joomla\CMS\Cache\Cache::getInstance('callback', array('defaultgroup' => 'com_seminardesk'));
        //$this->cache = JCache::getInstance('callback', array('defaultgroup' => 'com_seminardesk'));
        $this->cache = Factory::getCache('com_seminardesk', 'callback');
    }

    public function getCacheItem($key, $callback, $ttl = 900)
    {
      //die(json_encode($this->cache->getAll()));
      if ($this->cache->contains($key)) {
        $data = $this->cache->get($key);

        if (!$data) {
            $data = call_user_func($callback);
            //$this->cache->store($data, $key, 'com_seminardesk', $ttl);
            $this->cache->store('test123', $key);
            echo ('<br>from api: ' . $key . ': ');
        }
        else {
          echo '<br>from cache: ' . $key . ': ';
        }

        return $data;
      }
    }

    public function clearCache()
    {
        $this->cache->clean('com_seminardesk');
    }
}