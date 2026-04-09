<?php
/**
 * @package     Com_Seminardesk
 * @author      Benno Flory <benno.flory@gmx.ch>
 * @copyright   2022 Benno Flory
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Seminardesk\Site\View\Facilitator;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Uri\Uri;

/**
 * View class for a single SeminarDesk Facilitator
 *
 * @since  2.0.0
 */
class HtmlView extends BaseHtmlView
{
    protected $state;
    protected $item;
    protected $params;
    protected $facilitator;
    protected $facilitatorModel;

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

        // Get facilitator information
        $this->facilitatorModel = $this->getModel('Facilitator');
        $this->facilitator = $this->facilitatorModel->getItem($app->getInput()->getCmd('id', '0'));

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
        // Note: Using Uri::root() for absolute paths because Joomla's relative media path
        // resolution (HTMLHelper::mediaPath) does not resolve 'com_seminardesk/...' URIs correctly.
        $wa = $app->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('com_seminardesk.styles', Uri::root() . 'media/com_seminardesk/css/styles.css');
        $wa->registerAndUseScript('com_seminardesk.scripts', Uri::root() . 'media/com_seminardesk/js/seminardesk.js');
        
        $menus = $app->getMenu();
        $title = null;

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
}
