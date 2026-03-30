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
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Component\Seminardesk\Site\Service\ServiceFactory;

/**
 * Methods supporting a list of Seminardesk Facilitators
 *
 * @since  2.0.0
 */
class FacilitatorsModel extends ListModel
{
    /**
     * Array of all facilitators loaded from SeminarDesk API
     *
     * @var array
     */
    protected static $facilitators = [];

    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [];
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * @param   string  $ordering   Elements order
     * @param   string  $direction  Order direction
     *
     * @return  void
     */
    protected function populateState($ordering = null, $direction = null): void
    {
        $app = Factory::getApplication();

        parent::populateState($ordering, $direction);

        $context = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $context);

        // Load the parameters.
        $params = $app->getParams();
        $this->setState('params', $params);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return  \Joomla\Database\DatabaseQuery
     */
    protected function getListQuery(): \Joomla\Database\DatabaseQuery
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        return $query;
    }

    /**
     * Method to get an array of data items
     *
     * @return  array  An array of data on success
     */
    public function getItems(): array
    {
        // If not yet loaded: Get facilitators from API
        if (empty(self::$facilitators)) {
            self::$facilitators = ServiceFactory::getFacilitatorService()->loadFacilitators();
        }
        
        return self::$facilitators;
    }

    /**
     * Overrides the default function to check Date fields format, identified by
     * "_dateformat" suffix, and erases the field if it's not correct.
     *
     * @return  mixed
     */
    protected function loadFormData()
    {
        $app = Factory::getApplication();
        $filters = $app->getUserState($this->context . '.filter', []);
        $errorDateformat = false;

        foreach ($filters as $key => $value) {
            if (strpos($key, '_dateformat') && !empty($value) && $this->isValidDate($value) === null) {
                $filters[$key] = '';
                $errorDateformat = true;
            }
        }

        if ($errorDateformat) {
            $app->enqueueMessage(Text::_("COM_SEMINARDESK_SEARCH_FILTER_DATE_FORMAT"), "warning");
            $app->setUserState($this->context . '.filter', $filters);
        }

        return parent::loadFormData();
    }

    /**
     * Checks if a given date is valid and in a specified format (YYYY-MM-DD)
     *
     * @param   string  $date  Date to be checked
     *
     * @return  string|null
     */
    private function isValidDate(string $date): ?string
    {
        $date = str_replace('/', '-', $date);
        return (date_create($date)) ? Factory::getDate($date)->format("Y-m-d") : null;
    }
}
