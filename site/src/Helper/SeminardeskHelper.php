<?php
/**
 * @package     Com_Seminardesk
 * @subpackage  Site
 * @author      Benno Flory <benno.flory@gmx.ch>
 * @copyright   2022-2026 Benno Flory
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Seminardesk\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Component\Seminardesk\Site\Model\EventModel;
use Joomla\Component\Seminardesk\Site\Model\EventsModel;
use Joomla\Component\Seminardesk\Site\Model\FacilitatorModel;
use Joomla\Component\Seminardesk\Site\Model\FacilitatorsModel;

/**
 * SeminarDesk Site Helper
 *
 * @since  2.0.0
 */
class SeminardeskHelper
{
    /**
     * Get an instance of the named model
     *
     * @param   string  $name  Model name
     *
     * @return  object|null
     */
    public static function getModel($name)
    {
        return match ($name) {
            'Event' => new EventModel(),
            'Events' => new EventsModel(),
            'Facilitator' => new FacilitatorModel(),
            'Facilitators' => new FacilitatorsModel(),
            default => null,
        };
    }

    /**
     * Gets the files attached to an item
     *
     * @param   int     $pk     The item's id
     * @param   string  $table  The table's name
     * @param   string  $field  The field's name
     *
     * @return  array  The files
     */
    public static function getFiles($pk, $table, $field)
    {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true);

        $query
            ->select($field)
            ->from($table)
            ->where('id = ' . (int) $pk);

        $db->setQuery($query);

        return explode(',', $db->loadResult());
    }

    /**
     * Gets the edit permission for a user
     *
     * @param   mixed  $item  The item
     *
     * @return  bool
     */
    public static function canUserEdit($item)
    {
        $user = Factory::getApplication()->getIdentity();

        if ($user->authorise('core.edit', 'com_seminardesk')) {
            return true;
        }

        if (isset($item->created_by) && 
            $user->authorise('core.edit.own', 'com_seminardesk') && 
            $item->created_by == $user->id) {
            return true;
        }

        return false;
    }
}
