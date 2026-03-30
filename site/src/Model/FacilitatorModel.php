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
use Joomla\Component\Seminardesk\Site\Service\ServiceFactory;

/**
 * Seminardesk Facilitator model.
 *
 * @since  2.0.0
 */
class FacilitatorModel extends ItemModel
{
    /**
     * Facilitator object from SeminarDesk API
     *
     * @var object|null
     */
    protected static $facilitator = null;

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
     * Method to get a single facilitator
     *
     * @param   int|string|null  $facilitatorId  The facilitator ID
     *
     * @return  object|null  The facilitator object or null
     */
    public function getItem($facilitatorId = null): ?object
    {
        if (self::$facilitator === null && $facilitatorId !== null) {
            self::$facilitator = ServiceFactory::getFacilitatorService()->loadFacilitator($facilitatorId);
        }
        
        return self::$facilitator;
    }
}
