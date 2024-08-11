<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Seminardesk
 * @author     Benno Flory <benno.flory@gmx.ch>
 * @copyright  2022 Benno Flory
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\MVC\Controller\BaseController;

// Include dependancies
jimport('joomla.application.component.controller');

JLoader::registerPrefix('Seminardesk', JPATH_COMPONENT);
JLoader::register('SeminardeskController', JPATH_COMPONENT . '/controller.php');
JLoader::register('SeminardeskHelper', JPATH_COMPONENT . '/helpers/seminardesk.php');
JLoader::register('SeminardeskDataHelper', JPATH_COMPONENT . '/helpers/data.php');

// Execute the task.
$controller = BaseController::getInstance('Seminardesk');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
