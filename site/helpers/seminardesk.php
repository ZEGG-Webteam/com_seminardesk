<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Seminardesk
 * @author     Benno Flory <benno.flory@gmx.ch>
 * @copyright  2022 Benno Flory
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

JLoader::register('SeminardeskHelper', JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_seminardesk' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'seminardesk.php');

use \Joomla\CMS\Factory;
use \Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Class SeminardeskFrontendHelper
 *
 * @since  1.6
 */
class SeminardeskHelperSeminardesk
{
  /**
	 * Get an instance of the named model
	 *
	 * @param   string  $name  Model name
	 *
	 * @return null|object
	 */
	public static function getModel($name)
	{
		$model = null;

		// If the file exists, let's
		if (file_exists(JPATH_COMPONENT . '/models/' . strtolower($name) . '.php'))
		{
			require_once JPATH_COMPONENT . '/models/' . strtolower($name) . '.php';
			$model = BaseDatabaseModel::getInstance($name, 'SeminardeskModel');
		}

		return $model;
	}

	/**
	 * Gets the files attached to an item
	 *
	 * @param   int     $pk     The item's id
	 *
	 * @param   string  $table  The table's name
	 *
	 * @param   string  $field  The field's name
	 *
	 * @return  array  The files
	 */
	public static function getFiles($pk, $table, $field)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select($field)
			->from($table)
			->where('id = ' . (int) $pk);

		$db->setQuery($query);

		return explode(',', $db->loadResult());
	}

  /**
   * Gets the edit permission for an user
   *
   * @param   mixed  $item  The item
   *
   * @return  bool
   */
  public static function canUserEdit($item)
  {
      $permission = false;
      $user       = Factory::getUser();

      if ($user->authorise('core.edit', 'com_seminardesk') || (isset($item->created_by) && $user->authorise('core.edit.own', 'com_seminardesk') && $item->created_by == $user->id))
      {
          $permission = true;
      }

      return $permission;
  }
}
