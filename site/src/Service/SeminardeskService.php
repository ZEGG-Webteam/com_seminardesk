<?php
/**
 * @package     Com_Seminardesk
 * @subpackage  Site
 * @author      Benno Flory <benno.flory@gmx.ch>
 * @copyright   2022-2026 Benno Flory
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Seminardesk\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;

/**
 * SeminarDesk Service
 *
 * Provides core functionality for the SeminarDesk component with proper dependency injection.
 *
 * @since  2.0.0
 */
class SeminardeskService
{
    /**
     * @var MVCFactoryInterface
     */
    private MVCFactoryInterface $mvcFactory;

    /**
     * @var DatabaseInterface
     */
    private DatabaseInterface $db;

    /**
     * @var CMSApplicationInterface
     */
    private CMSApplicationInterface $app;

    /**
     * Constructor
     *
     * @param   MVCFactoryInterface      $mvcFactory  The MVC factory
     * @param   DatabaseInterface        $db          The database driver
     * @param   CMSApplicationInterface  $app         The application
     */
    public function __construct(
        MVCFactoryInterface $mvcFactory,
        DatabaseInterface $db,
        CMSApplicationInterface $app
    ) {
        $this->mvcFactory = $mvcFactory;
        $this->db = $db;
        $this->app = $app;
    }

    /**
     * Get an instance of the named model
     *
     * @param   string  $name    Model name
     * @param   string  $prefix  Model prefix (default: 'Site')
     * @param   array   $config  Configuration array
     *
     * @return  object|null
     */
    public function getModel(string $name, string $prefix = 'Site', array $config = []): ?object
    {
        try {
            return $this->mvcFactory->createModel($name, $prefix, $config);
        } catch (\Exception $e) {
            return null;
        }
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
    public function getFiles(int $pk, string $table, string $field): array
    {
        $query = $this->db->getQuery(true);

        $query
            ->select($this->db->quoteName($field))
            ->from($this->db->quoteName($table))
            ->where($this->db->quoteName('id') . ' = ' . (int) $pk);

        $this->db->setQuery($query);
        $result = $this->db->loadResult();

        return $result ? explode(',', $result) : [];
    }

    /**
     * Gets the edit permission for a user
     *
     * @param   mixed  $item  The item
     *
     * @return  bool
     */
    public function canUserEdit(mixed $item): bool
    {
        $user = $this->app->getIdentity();

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

    /**
     * Get the MVC factory
     *
     * @return  MVCFactoryInterface
     */
    public function getMvcFactory(): MVCFactoryInterface
    {
        return $this->mvcFactory;
    }

    /**
     * Get the database driver
     *
     * @return  DatabaseInterface
     */
    public function getDatabase(): DatabaseInterface
    {
        return $this->db;
    }

    /**
     * Get the application
     *
     * @return  CMSApplicationInterface
     */
    public function getApplication(): CMSApplicationInterface
    {
        return $this->app;
    }
}
