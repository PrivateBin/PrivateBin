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
    protected static $_templateFallback;

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
        }
    }

    /**
     * get currently loaded template
     *
     * @access public
     * @static
     * @return string
     */
    public static function getTemplate(): string
    {
        $selectedTemplate = self::getSelectedByUserTemplate();
        return $selectedTemplate ?? self::$_templateFallback;
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
        return in_array($template, self::getAvailableTemplates());
    }

    /**
     * get the template selected by user
     *
     * @access private
     * @static
     * @return string|null
     */
    private static function getSelectedByUserTemplate(): ?string
    {
        $selectedTemplate    = null;
        $templateCookieValue = $_COOKIE['template'] ?? '';

        if (self::isTemplateAvailable($templateCookieValue)) {
            $selectedTemplate = $templateCookieValue;
        }

        return $selectedTemplate;
    }
}
