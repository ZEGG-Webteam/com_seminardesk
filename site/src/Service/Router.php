<?php
/**
 * @package     Com_Seminardesk
 * @subpackage  Site
 * @author      Benno Flory <benno.flory@gmx.ch>
 * @copyright   2022-2026 Benno Flory
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Seminardesk\Site\Service;

use Joomla\CMS\Component\Router\RouterBase;
use Joomla\CMS\Factory;

/**
 * Router for com_seminardesk
 * 
 * Uses simple custom routing without RouterView rules.
 * URLs: /menu-path/eventId/slug or /menu-path/id/name
 */
class Router extends RouterBase
{
    /**
     * Build the route for the URL.
     *
     * @param   array  &$query  An array of URL arguments
     * @return  array  The URL segments
     */
    public function build(&$query)
    {
        $segments = [];

        // Remove menu-item config parameters (should not appear in URL)
        unset($query['facilitators_page']);
        unset($query['events_page']);

        // Event view: add eventId and slug as segments
        if (isset($query['eventId'])) {
            $segments[] = $query['eventId'];
            unset($query['eventId']);
        }
        if (isset($query['slug'])) {
            $segments[] = $query['slug'];
            unset($query['slug']);
        }

        // Facilitator view: add id and name as segments
        if (isset($query['id'])) {
            $segments[] = $query['id'];
            unset($query['id']);
        }
        if (isset($query['name'])) {
            $segments[] = $query['name'];
            unset($query['name']);
        }

        // Remove view from query (menu item determines view context)
        if (isset($query['view'])) {
            unset($query['view']);
        }

        return $segments;
    }

    /**
     * Parse the segments of a URL.
     *
     * @param   array  &$segments  The segments of the URL to parse
     * @return  array  The URL attributes
     */
    public function parse(&$segments)
    {
        $vars = [];
        $app = Factory::getApplication();
        $menu = $app->getMenu()->getActive();

        if (!$menu || empty($segments)) {
            return $vars;
        }

        $parentView = $menu->query['view'] ?? 'events';

        switch ($parentView) {
            case 'facilitators':
                $vars['id'] = $segments[0];
                $vars['view'] = 'facilitator';
                break;

            case 'events':
            default:
                $vars['eventId'] = $segments[0];
                $vars['view'] = 'event';
                break;
        }

        // Clear processed segments
        $segments = [];

        return $vars;
    }
}
