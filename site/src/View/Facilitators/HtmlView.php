<?php
/**
 * @package     Com_Seminardesk
 * @author      Benno Flory <benno.flory@gmx.ch>
 * @copyright   2022 Benno Flory
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Seminardesk\Site\View\Facilitators;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Uri\Uri;

/**
 * View class for a list of SeminarDesk Facilitators
 *
 * @since  2.0.0
 */
class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $pagination;
    protected $state;
    protected $params;
    protected $facilitators;
    protected $title;
    protected $pageclass_sfx;
    protected $events_page;

    /**
     * Display the Facilitators view
     *
     * @param   string  $tpl  Name of the template file to parse
     *
     * @return  void
     *
     * @throws  \Exception
     */
    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        
        $this->state = $this->get('State');
        $this->params = $this->state->get('params');
        
        // Assign data to the view
        $this->facilitators = $this->getModel('Facilitators');
        $this->title = $app->getMenu()->getActive()->title ?? '';
        $this->pageclass_sfx = htmlspecialchars($app->getInput()->get('pageclass_sfx', ''), ENT_COMPAT, 'UTF-8');
        $this->events_page = $app->getInput()->get('events_page');

        // Check for errors.
        $errors = $this->get('Errors');
        if (!empty($errors)) {
            throw new \Exception(implode("\n", $errors));
        }

        // Display the view
        $this->prepareDocument();
        parent::display($tpl);
    }

    /**
     * Prepares the document
     *
     * @return  void
     */
    protected function prepareDocument(): void
    {
        $app   = Factory::getApplication();
        
        // Load CSS / JS via WebAssetManager
        // Note: Using Uri::root() for absolute paths because Joomla's relative media path
        // resolution (HTMLHelper::mediaPath) does not resolve 'com_seminardesk/...' URIs correctly.
        $wa = $app->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('com_seminardesk.styles', Uri::root() . 'media/com_seminardesk/css/styles.css');
        $wa->registerAndUseScript('com_seminardesk.scripts', Uri::root() . 'media/com_seminardesk/js/seminardesk.js');
        
        $menus = $app->getMenu();
        $title = null;

        // Because the application sets a default page title,
        // we need to get it from the menu item itself
        $menu = $menus->getActive();

        if ($menu) {
            $this->params->def('page_heading', $this->params->get('page_title', $menu->title));
        } else {
            $this->params->def('page_heading', Text::_('COM_SEMINARDESK_DEFAULT_PAGE_TITLE'));
        }

        $title = $this->params->get('page_title', '');

        if (empty($title)) {
            $title = $app->get('sitename');
        } elseif ($app->get('sitename_pagetitles', 0) == 1) {
            $title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
        } elseif ($app->get('sitename_pagetitles', 0) == 2) {
            $title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
        }

        $this->getDocument()->setTitle($title);

        if ($this->params->get('menu-meta_description')) {
            $this->getDocument()->setDescription($this->params->get('menu-meta_description'));
        }

        if ($this->params->get('menu-meta_keywords')) {
            $this->getDocument()->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
        }

        if ($this->params->get('robots')) {
            $this->getDocument()->setMetadata('robots', $this->params->get('robots'));
        }
    }

    /**
     * Check if state is set
     *
     * @param   mixed  $state  State
     *
     * @return  bool
     */
    public function getState($state)
    {
        return isset($this->state->{$state}) ? $this->state->{$state} : false;
    }
}
