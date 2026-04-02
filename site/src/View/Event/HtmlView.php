<?php
/**
 * @package     Com_Seminardesk
 * @author      Benno Flory <benno.flory@gmx.ch>
 * @copyright   2022 Benno Flory
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Seminardesk\Site\View\Event;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * View class for a single SeminarDesk Event
 *
 * @since  2.0.0
 */
class HtmlView extends BaseHtmlView
{
    protected $state;
    protected $item;
    protected $params;
    protected $event;
    protected $eventModel;

    /**
     * Display the view
     *
     * @param   string  $tpl  Template name
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

        // Get event information from eventDates
        $this->eventModel = $this->getModel('Event');
        $this->event = $this->eventModel->getItem($app->getInput()->getCmd('eventId', '0'));

        // Check for errors.
        $errors = $this->get('Errors');
        if (!empty($errors)) {
            throw new \Exception(implode("\n", $errors));
        }

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
        $wa = $app->getDocument()->getWebAssetManager();
        $wa->getRegistry()->addExtensionRegistryFile('com_seminardesk');
        $wa->useStyle('com_seminardesk.styles');
        $wa->useScript('com_seminardesk.scripts');
        
        $menus = $app->getMenu();
        $title = null;

        $menu = $menus->getActive();

        if ($menu) {
            $this->params->def('page_heading', $this->params->get('page_title', $menu->title));
        } else {
            $this->params->def('page_heading', Text::_('COM_SEMINARDESK_DEFAULT_PAGE_TITLE'));
        }

        $title = $this->params->get('page_title', '');

        // Set event title as page title if available
        if (!empty($this->event->title)) {
            $title = $this->event->title;
        }

        if (empty($title)) {
            $title = $app->get('sitename');
        } elseif ($app->get('sitename_pagetitles', 0) == 1) {
            $title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
        } elseif ($app->get('sitename_pagetitles', 0) == 2) {
            $title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
        }

        $this->getDocument()->setTitle($title);
    }
}
