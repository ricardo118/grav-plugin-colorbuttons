<?php
namespace Grav\Plugin;

use Grav\Common\GPM\GPM;
use Grav\Common\Grav;
use Grav\Common\Page\Page;
use Grav\Common\Page\Pages;
use Grav\Common\Plugin;

class ColorButtonsPlugin extends Plugin
{

    protected $route = 'color-buttons-endpoint';
    protected $pluginRoute = 'plugins/color-buttons';
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
            'onShortcodeHandlers' => ['onShortcodeHandlers', 0],
            'onPagesInitialized' => ['colorButtonsEndpoint', 0],
            'onAssetsInitialized' => ['onAssetsInitialized', 0]
        ];
    }

    public function config()
    {
        return $this->config->get('plugins.color-buttons');
    }

    public function useShortCode()
    {
        $plugins = $this->grav['plugins'];
        $config = $this->config();

        if ($config['parser'] === 'shortcode') {
            if (is_null($plugins->get('shortcode-core'))) {
                return $this->grav['admin']->setMessage('Shortcode Core Plugin is not installed. Install or change parser to Markdown Extra.');
            }
            if (!$this->config->get('plugins')['shortcode-core']['enabled']){
                return $this->grav['admin']->setMessage('Shortcode Core Plugin is not enabled. Enable or change parser to Markdown Extra.');
            }
            return 'true';
        }
        return 'false';
    }

    public function isExtended()
    {
        $config = $this->config();
        if($config['mode'] === 'extended') {
            return 'extended';
        }
        return 'simple';
    }

    public function generateCustomCss()
    {
        $config = $this->config();
        $customCss = '';

        foreach ($config['custom'] as $class=>$color) {
            $customCss .= '.' .$class. '{';
            $customCss .= 'color:' .$color. ';}';
        }

        return $customCss;
    }

    public function colorButtonsEndpoint()
    {
        $uri = $this->grav['uri'];
        if (strpos($uri->path(), $this->route) === false) {
            return;
        }
        $colors = $this->getCustomColors();
        echo $colors;
        exit();
    }

    public function getCustomColors()
    {
        $config = $this->config();
        $colors = '<ul id="custom-chooser" class="dropdown-menu">';
        $index = 1;
        foreach ($config['custom'] as $class=>$color) {
            $index++;
            $colors .= '<li class="grav-editor-button-color js-color '.$class.'"><a data-class="'.$class.'" style="background-color:'.$color.'" class="custom-color-btn hint--top"></a></li>';
        }
        return $colors;
    }

    public function onPluginsInitialized()
    {
        /* NOT ON ADMIN */
        if (!$this->isAdmin()) {
            return;
        }

        $this->enable([
            'onAdminTwigTemplatePaths' => ['onAdminTwigTemplatePaths', 0]
        ]);

        $this->useShortCode();
    }

    public function onShortcodeHandlers()
    {
        if ($this->useShortCode() === 'true') {
            $this->grav['shortcode']->registerAllShortcodes(__DIR__ . '/shortcodes');
        }
    }

    public function onAdminTwigTemplatePaths($event)
    {
        $event['paths']=array_merge($event['paths'], [__DIR__ . '/admin/templates']);
        return $event;
    }

    public function onAssetsInitialized()
    {
        $config = $this->config();
        $assets=$this->grav['assets'];

        /*  CUSTOM
         *  JAVASCRIPT &
         *  STYLESHEETS - LOADS EVERYWHERE IN ADMIN */
        if ($this->isAdmin()) {
            $assets->addCss('user/plugins/color-buttons/admin/css/colors.css', 10);
            $assets->addInlineJs('window.ColorButtonsPlugin = window.ColorButtonsPlugin || {};window.ColorButtonsPlugin.useShortcode = '.$this->useShortCode().';', 10);
        }
        switch($config['current']){
            case 'custom':
                $assets->addInlineCss($this->generateCustomCss(), 100);
                if ($this->isAdmin()) {$assets->addJs('user/plugins/color-buttons/admin/js/custom_colors.js', 9);}
                break;
            default:
                $assets->addCss('user/plugins/color-buttons/admin/css/'. $this->isExtended() .'/'. $config['current'] .'.css', 1);
                if ($this->isAdmin()) {$assets->addJs('user/plugins/color-buttons/admin/js/'. $this->isExtended() .'/'. $config['current'] .'.js', 9);}
                break;
        }

        /*  BASE
         *  JAVASCRIPT &
         *  STYLESHEETS
         *  FOR COLORPICKER ARRAY AND RADIO PALETTES - ONLY LOADS IN PLUGIN CONFIGURATION PAge */
        if (strpos($this->grav['uri']->path(), $this->pluginRoute) !== false) {
            $assets->addJs('user/plugins/color-buttons/admin/js/colorarray.js', ['loading' => 'defer', 'priority' => 0]);
            $assets->addCss('user/plugins/color-buttons/admin/css/colorarray.css', 10);
        }
    }

}
