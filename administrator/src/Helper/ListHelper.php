<?php
/**
 * @package     Com_Seminardesk
 * @author      Benno Flory <benno.flory@gmx.ch>
 * @copyright   2022 Benno Flory
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Seminardesk\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;

/**
 * Seminardesk List helper for HTML rendering.
 *
 * @since  2.0.0
 */
abstract class ListHelper
{
    /**
     * Render a toggle button for list views
     *
     * @param   int     $value  The current value (0 or 1)
     * @param   string  $view   The view name
     * @param   string  $field  The field name
     * @param   int     $i      The row index
     *
     * @return  string  The HTML for the toggle button
     */
    public static function toggle(int $value, string $view, string $field, int $i): string
    {
        $states = [
            0 => ['icon-remove', Text::_('Toggle'), 'inactive btn-danger'],
            1 => ['icon-checkmark', Text::_('Toggle'), 'active btn-success']
        ];

        $state = ArrayHelper::getValue($states, $value, $states[0]);
        $text = '<span aria-hidden="true" class="' . $state[0] . '"></span>';
        $html = '<a href="#" class="btn btn-micro ' . $state[2] . '"';
        $html .= ' onclick="return toggleField(\'cb' . $i . '\',\'' . $view . '.toggle\',\'' . $field . '\')" title="' . Text::_($state[1]) . '">' . $text . '</a>';

        return $html;
    }
}
