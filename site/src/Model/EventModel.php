<?php
/**
 * @package     Com_Seminardesk
 * @author      Benno Flory <benno.flory@gmx.ch>
 * @copyright   2022 Benno Flory
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Seminardesk\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\Component\Seminardesk\Site\Helper\DataHelper;

/**
 * Seminardesk Event model.
 *
 * @since  2.0.0
 */
class EventModel extends ItemModel
{
    /**
     * Event object from SeminarDesk API
     *
     * @var object|null
     */
    protected static $event = null;

    /**
     * Method to auto-populate the model state.
     *
     * @return  void
     */
    protected function populateState(): void
    {
        $app = Factory::getApplication();
        $params = $app->getParams();
        $this->setState('params', $params);
    }

    /**
     * Method to get a single event
     *
     * @param   int|string|null  $eventId  The event ID
     *
     * @return  object|null  The event object or null
     */
    public function getItem($eventId = null): ?object
    {
        if (self::$event === null && $eventId !== null) {
            self::$event = DataHelper::loadEvent($eventId);
        }
        
        return self::$event;
    }
}
