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
use Joomla\Component\Seminardesk\Site\Helper\DataHelper;

/**
 * Methods supporting a list of Seminardesk Events
 *
 * @since  2.0.0
 */
class EventsModel extends ListModel
{
    /**
     * Array of all eventDates loaded from SeminarDesk API
     *
     * @var array
     */
    protected static $events = [];

    /**
     * Collected categories from all events
     *
     * @var array
     */
    protected static $categories = [];

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
        $filters['show_canceled'] = true;
        
        // If not yet loaded: Get events from API
        if (empty(self::$events)) {
            self::$events = DataHelper::loadEventDates($filters);
        }
        
        return self::$events;
    }

    /**
     * Get all event categories
     *
     * @return  array  Collected categories of all events
     */
    public function getAllEventCategories(): array
    {
        if (empty(self::$categories)) {
            $events = $this->getItems();
            foreach ($events as $event) {
                self::$categories += $event->categories ?? [];
            }
            self::$categories = array_unique(self::$categories);
            
            if (class_exists('Collator')) {
                $collator = new \Collator('de_DE');
                $collator->asort(self::$categories);
            }
        }

        return self::$categories;
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
        return date_create($date) ? Factory::getDate($date)->format("Y-m-d") : null;
    }
}
