<?php declare(strict_types=1);
/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @link      https://github.com/PrivateBin/PrivateBin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 */

namespace PrivateBin;

/**
 * TemplateSwitcher
 *
 * Provides tool to change application template
 */
class TemplateSwitcher
{
    /**
     * template fallback
     *
     * @access protected
     * @static
     * @var    string
     */
    protected static $_templateFallback = 'bootstrap5';

    /**
     * available templates
     *
     * @access protected
     * @static
     * @var    array
     */
    protected static $_availableTemplates = array();

    /**
     * set available templates
     *
     * @access public
     * @static
     * @param  array $templates
     */
    public static function setAvailableTemplates(array $templates)
    {
        self::$_availableTemplates = $templates;
    }

    /**
     * set the default template
     *
     * @access public
     * @static
     * @param  string $template
     */
    public static function setTemplateFallback(string $template)
    {
        if (self::isTemplateAvailable($template)) {
            self::$_templateFallback = $template;
        } else {
            error_log('failed to set "' . $template . '" as a fallback, it needs to be added to the list of `availabletemplates` in the configuration file');
        }
    }

    /**
     * get user selected template or fallback
     *
     * @access public
     * @static
     * @return string
     */
    public static function getTemplate(): string
    {
        if (array_key_exists('template', $_COOKIE)) {
            $template = basename($_COOKIE['template']);
            if (self::isTemplateAvailable($template)) {
                return $template;
            }
        }
        return self::$_templateFallback;
    }

    /**
     * get list of available templates
     *
     * @access public
     * @static
     * @return array
     */
    public static function getAvailableTemplates(): array
    {
        return self::$_availableTemplates;
    }

    /**
     * check if the provided template is available
     *
     * @access public
     * @static
     * @return bool
     */
    public static function isTemplateAvailable(string $template): bool
    {
        if (in_array($template, self::getAvailableTemplates(), true)) {
            return true;
        }
        error_log('template "' . $template . '" is not in the list of `availabletemplates` in the configuration file');
        return false;
    }
}
