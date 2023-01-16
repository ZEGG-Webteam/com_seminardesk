<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Seminardesk
 * @author     Benno Flory <benno.flory@gmx.ch>
 * @copyright  2022 Benno Flory
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;

/**
 * View to edit
 *
 * @since  1.6
 */
class SeminardeskViewFacilitator extends \Joomla\CMS\MVC\View\HtmlView
{
  protected $state;
  protected $item;
  protected $form;
  protected $params;

  /**
   * Display the view
   *
   * @param   string  $tpl  Template name
   * @return void
   * @throws Exception
   */
  public function display($tpl = null)
  {
    $app  = Factory::getApplication();
    $user = Factory::getUser();

    $this->state  = $this->get('State');
    $this->params = $this->state->get('params');
//    $this->item   = $this->get('Item');

    // Get facilitator information
    $this->facilitatorModel = SeminardeskHelperSeminardesk::getModel('Facilitator');
    $this->facilitator = $this->facilitatorModel->getItem($app->input->getCmd('id', '0'));

    if (!empty($this->facilitator))
    {

    }

    // Check for errors.
    if (count($errors = $this->get('Errors')))
    {
      throw new Exception(implode("\n", $errors));
    }
    if ($this->_layout == 'edit')
    {
      $authorised = $user->authorise('core.create', 'com_seminardesk');

      if ($authorised !== true)
      {
        throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'));
      }
    }

    $this->_prepareDocument();

    parent::display($tpl);
  }

  /**
   * Prepares the document
   *
   * @return void
   * @throws Exception
   */
  protected function _prepareDocument()
  {
    $app   = Factory::getApplication();
    $menus = $app->getMenu();
    $title = null;

    // Because the application sets a default page title,
    // We need to get it from the menu item itself
    $menu = $menus->getActive();

    if ($menu)
    {
      $this->params->def('page_heading', $this->params->get('page_title', $menu->title));
    }
    else
    {
      $this->params->def('page_heading', Text::_('COM_SEMINARDESK_DEFAULT_PAGE_TITLE'));
    }

    $title = $this->params->get('page_title', '');

    if (empty($title))
    {
      $title = $app->get('sitename');
    }
    elseif ($app->get('sitename_pagetitles', 0) == 1)
    {
      $title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
    }
    elseif ($app->get('sitename_pagetitles', 0) == 2)
    {
      $title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
    }

    $this->document->setTitle($title);

    if ($this->params->get('menu-meta_description'))
    {
      $this->document->setDescription($this->params->get('menu-meta_description'));
    }

    if ($this->params->get('menu-meta_keywords'))
    {
      $this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
    }

    if ($this->params->get('robots'))
    {
      $this->document->setMetadata('robots', $this->params->get('robots'));
    }

  }
}
