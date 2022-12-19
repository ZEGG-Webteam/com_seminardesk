<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Seminardesk
 * @author     Benno Flory <benno.flory@gmx.ch>
 * @copyright  2022 Benno Flory
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Factory;
use Joomla\CMS\Categories\Categories;

/**
 * Class SeminardeskRouter
 *
 */
class SeminardeskRouter extends RouterView
{
  private $noIDs;
  public function __construct($app = null, $menu = null)
  {
    $params = JComponentHelper::getComponent('com_seminardesk')->params;
    $this->noIDs = (bool) $params->get('sef_ids');

    parent::__construct($app, $menu);

    $this->attachRule(new MenuRules($this));

    if ($params->get('sef_advanced', 0))
    {
      $this->attachRule(new StandardRules($this));
      $this->attachRule(new NomenuRules($this));
    }
    else
    {
      JLoader::register('SeminardeskRulesLegacy', __DIR__ . '/helpers/legacyrouter.php');
      $this->attachRule(new SeminardeskRulesLegacy($this));
    }
  }

  /**
   * Build the route for the com_seminardesk component
   *
   * @param   array  &$query  An array of URL arguments
   * @return  array  The URL arguments to use to assemble the subsequent URL.
   * @since   3.3
   */
  public function build(&$query)
  {
    $app  = Factory::getApplication();
    $segments = [];

//    // Add cat
//    if (isset($query['cat']))
//    {
//      $segments[] = $query['cat'];
//      unset($query['cat']);
//    }
    // Add eventId
    if (isset($query['eventId']))
    {
      $segments[] = $query['eventId'];
      unset($query['eventId']);
    }
    // Add slug
    if (isset($query['slug']))
    {
      $segments[] = $query['slug'];
      unset($query['slug']);
    }

    if (isset($query['view']))
    {
      unset($query['view']);
    }

    return $segments;
  }

  /**
   * Parse the segments of a URL.
   *
   * @param   array  &$segments  The segments of the URL to parse.
   * @return  array  The URL attributes to be used by the application.
   * @since   3.3
   */
  public function parse(&$segments)
  {
    $vars = [];
    $vars['eventId']   = $segments[0];
    $vars['view'] = 'event';

    return $vars;
  }

}
