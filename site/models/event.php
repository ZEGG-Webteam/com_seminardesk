<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Seminardesk
 * @author     Benno Flory <benno.flory@gmx.ch>
 * @copyright  2022 Benno Flory
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.modelitem');
jimport('joomla.event.dispatcher');

use \Joomla\CMS\Factory;
use \Joomla\Utilities\ArrayHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Table\Table;
use \Joomla\CMS\Helper\TagsHelper;

/**
 * Seminardesk model.
 *
 * @since  1.6
 */
class SeminardeskModelEvent extends \Joomla\CMS\MVC\Model\ItemModel
{
	protected function populateState()
	{
		$app  = Factory::getApplication('com_seminardesk');
		$params       = $app->getParams();
		$params_array = $params->toArray();
		$this->setState('params', $params);
	}

	public function getItem ($id = null)
	{
    
	}
}
