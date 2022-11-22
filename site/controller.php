<?php
/**
 * @package     SeminarDesk
 * @subpackage  com_seminardesk
 *
 * @copyright   Copyright (C) 2022 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * SeminarDesk Component Controller
 *
 * @since  0.0.1
 */
class SeminarDeskController extends JControllerLegacy
{
  
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Display the view
	 */
	public function display($cachable = false, $urlparams = false)
	{
		$document   = JFactory::getDocument();

		// Set the default view name and format from the Request.
		$jinput     = JFactory::getApplication()->input;
//		$id         = $jinput->getInt('a_id', 0);
		$viewName   = $jinput->getCmd('view', 'events');
		$viewFormat = $document->getType();
//		$layoutName = $jinput->getCmd('layout', 'edit');

		$view = $this->getView($viewName, $viewFormat);
		if ($view) {
//			// Do any specific processing by view.
//			switch ($viewName) {
//				case 'events':
//					$model = $this->getModel($viewName);
//					break;
//				default:
//					$model = $this->getModel('events');
//					break;
//			}
//
//			// Push the model into the view
//			if ($viewName == 'events') {
//				$model1 = $this->getModel('Venue');
//
//				$view->setModel($model1, true);
//				$view->setModel($model2);
//			} elseif($viewName == 'event') {
//				$model1 = $this->getModel('ModelX');
//				$model2 = $this->getModel('ModelY');
//
//				$view->setModel($model1, true);
//				$view->setModel($model2);
//			} else {
//				$view->setModel($model, true);
//			}
//
//			$view->setLayout($layoutName);

			// Push document object into the view.
			$view->document = $document;

			$view->display();
		}
	}

}