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

use Joomla\CMS\Component\Router\Rules\RulesInterface;
use Joomla\CMS\Categories\Categories;
/**
 * Legacy router
 *
 * @since  1.6
 */
class SeminardeskRulesLegacy implements RulesInterface
{
    /**
     * Constructor for this legacy router
     *
     * @param   JComponentRouterView  $router  The router this rule belongs to
     *
     * @since       3.6
     * @deprecated  4.0
     */
    public function __construct($router)
    {
        $this->router = $router;
    }

    /**
     * Preprocess the route for the com_content component
     *
     * @param   array  &$query  An array of URL arguments
     *
     * @return  void
     *
     * @since       3.6
     * @deprecated  4.0
     */
    public function preprocess(&$query)
    {
    }

    /**
     * Build the route for the com_content component
     *
     * @param   array  &$query     An array of URL arguments
     * @param   array  &$segments  The URL arguments to use to assemble the subsequent URL.
     *
     * @return  void
     *
     * @since       3.6
     * @deprecated  4.0
     */
    public function build(&$query, &$segments)
    {
        $segments = array();
        $view     = null;

        if (isset($query['task']))
        {
            $taskParts  = explode('.', $query['task']);
            $segments[] = implode('/', $taskParts);
            $view       = $taskParts[0];
            unset($query['task']);
        }

        if (isset($query['view']))
        {
            $segments[] = $query['view'];
            $view = $query['view'];
            
            unset($query['view']);
        }

        if (isset($query['id']))
        {

            if ($view !== null)
            {
                
            }
            else
            {
                $segments[] = $query['id'];
            }
            
            unset($query['id']);
        }
    }

    /**
     * Parse the segments of a URL.
     *
     * @param   array  &$segments  The segments of the URL to parse.
     * @param   array  &$vars      The URL attributes to be used by the application.
     *
     * @return  void
     *
     * @since       3.6
     * @deprecated  4.0
     */
    public function parse(&$segments, &$vars)
    {
        $vars = array();

        // View is always the first element of the array
        $vars['view'] = array_shift($segments);
        $model        = SeminardeskHelpersSeminardesk::getModel($vars['view']);

        while (!empty($segments))
        {
            $segment = array_pop($segments);

            // If it's the ID, let's put on the request
            if (is_numeric($segment))
            {
                $vars['id'] = $segment;
            }
            else
            {
                
				$id = null;
				if (method_exists($model, 'getItemIdByAlias'))
				{
					$id = $model->getItemIdByAlias(str_replace(':', '-', $segment));
				}
				if (!empty($id))
				{
					$vars['id'] = $id;
				}
				else
				{
					$vars['task'] = $vars['view'] . '.' . $segment;
				}
            }
        }
    }
}
