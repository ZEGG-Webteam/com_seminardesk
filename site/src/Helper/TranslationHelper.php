<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_seminardesk
 *
 * @copyright   Copyright (C) 2026 SeminarDesk. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Seminardesk\Site\Helper;


use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\Component\Seminardesk\Site\Service\ConfigService;

/**
 * SeminarDesk Language Helper
 *
 * @since  2.0.0
 */
class TranslationHelper
{
    /**
     * Service instances (lazy-loaded)
     */
    protected static ?ConfigService $configService = null;

    /**
     * Get ConfigService instance
     */
    protected static function getConfigService(): ConfigService
    {
        if (!self::$configService) {
        self::$configService = new ConfigService(Factory::getApplication());
        }
        return self::$configService;
    }
    /**
     * Get the current language key (e.g., 'de', 'en')
     *
     * @return string Short language code in lowercase
     */
    public static function getCurrentLanguageKey(): string
    {
        $currentLanguage = Factory::getLanguage()->getTag();
        $languages = LanguageHelper::getLanguages('lang_code');
        return strtolower($languages[$currentLanguage]->sef ?? 'en');
    }

    /**
     * Get localized value from languages provided by SeminarDesk
     * 
     * @param array $fieldValues - Containing values for all languages
     * @param boolean|string $fallbackLang - true = Fallback to first language in array, OR 
     *                                   string = Fallback language key ('DE', 'EN' ...)
     * @param boolean $htmlencode - true = encode html specialchars before returning
     * @return string - Value
     */
    public static function translate($fieldValues, $htmlencode = false, $fallbackLang = true)
    {
        $config = self::getConfigService()->getConfiguration();
        $langKey = $config['langKey'];
        $value = '';

        if (is_array($fieldValues)) {

        //-- Set language field as array key
        $localizedValues = array_combine(
            array_column($fieldValues, 'language'),
            array_column($fieldValues, 'value')
        );

        //-- Return localized or fallback value
        if (array_key_exists($langKey, $localizedValues)) {
            $value = $localizedValues[$langKey];
        }
        else {
            //-- Fallback to first language
            if ($fallbackLang === true) {
            $value = reset($localizedValues);
            }
            //-- Fallback to selected language, if exists (otherwise $value is empty)
            elseif (is_string($fallbackLang) && array_key_exists($fallbackLang, $localizedValues)) {
            $value = $localizedValues[$fallbackLang];
            }
        }
        }

        //-- Encode html entities and return
        return ($htmlencode) ? htmlspecialchars($value, ENT_QUOTES) : $value;
    }
}
