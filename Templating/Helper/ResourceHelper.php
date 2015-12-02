<?php

/**
 * This file is part of the DinecatThemeBundle package.
 * @copyright   2015 UAB Dinecat, http://dinecat.com/
 * @license     http://dinecat.com/licenses/mit MIT License
 * @link        https://github.com/dinecat/ThemeBundle
 */

namespace Dinecat\ThemeBundle\Templating\Helper;

use Dinecat\ThemeBundle\Model\ResourceDistributor;
use Symfony\Component\Templating\Helper\Helper;

/**
 * Resource template helper.
 * @package DinecatThemeBundle\Templating
 * @author  Mykola Zyk <relo.san.pub@gmail.com>
 */
class ResourceHelper extends Helper
{
    /**
     * @var ResourceDistributor
     */
    protected $distributor;

    /**
     * @var string  Host for assets.
     */
    protected $assetHost;

    /**
     * @var string  Current theme name.
     */
    protected $theme;

    /**
     * Prepared resources.
     * @var array {
     *     @var array   $css    Css resources.
     *     @var array   $js     Javascript resources.
     *     @var array   $icon   Favicon/Apple icon resources.
     * }
     */
    protected $assets = ['css' => [], 'js' => [], 'icon' => []];

    /**
     * @var bool    TRUE if resources prepared, FALSE otherwise.
     */
    protected $isBuilt = false;

    /**
     * Rules for preparing resources.
     * @var array {
     *     @var array   $css    Css rules.
     *     @var array   $js     Javascript rules.
     *     @var array   $preset Preset rules.
     *     @var array   $loaded Registered resources.
     * }
     */
    protected $rules = ['css' => [], 'js' => [], 'preset' => [], 'loaded' => []];

    /**
     * Constructor.
     * @param   ResourceDistributor $distributor
     */
    public function __construct(ResourceDistributor $distributor)
    {
        $this->distributor = $distributor;
    }

    /**
     * Set stylesheet resource.
     * @param   string  $name   Resource name.
     * @param   string  $body   Resource body [optional, if needed].
     */
    public function setCss($name, $body = null)
    {
        $this->rules['css'][$name] = ['type' => $body === null ? 'resource' : 'content', 'body' => $body];
    }

    /**
     * Set javascript resource.
     * @param   string  $name       Resource name.
     * @param   string  $body       Resource body [optional, if needed].
     * @param   bool    $onBottom   Place js on bottom or head [optional, default true, i.e. on bottom].
     */
    public function setJs($name, $body = null, $onBottom = true)
    {
        $this->rules['js'][$name] = [
            'type' => $body === null ? 'resource' : 'content',
            'body' => $body,
            'bottom' => $onBottom
        ];
    }

    /**
     * Set resources preset group.
     * @param   string  $name   Preset name.
     */
    public function setPreset($name)
    {
        $this->rules['preset'][$name] = null;
    }

    /**
     * Select resources theme.
     * @param   string  $theme  Theme name.
     */
    public function selectTheme($theme)
    {
        $this->theme = $theme;
    }

    /**
     * Rendering resources on head section.
     */
    public function renderHeadResources()
    {
        echo $this->buildCss();
        echo $this->buildJs(false);
        echo $this->buildIcons();
    }

    /**
     * Rendering resources on bottom of body section.
     */
    public function renderBottomResources()
    {
        echo $this->buildJs();
    }

    /**
     * Build css styles tag's.
     * @return  string
     */
    public function buildCss()
    {
        if (!$this->isBuilt) {
            $this->processResources();
        }

        $out = '';
        $inline = '';
        foreach ($this->assets['css'] as $item) {
            switch ($item['res']) {
                case 'inline':
                    $inline .= $item['body'];
                    break;
                case 'link':
                    $out .= '<link rel="stylesheet" type="text/css"'
                        . ($item['media'] ? ' media="' . $item['media'] . '"' : '')
                        . ' href="' . $item['source'] . '" />';
                    break;
                case 'file':
                    $out .= '<link rel="stylesheet" type="text/css"'
                        . ($item['media'] ? ' media="' . $item['media'] . '"' : '')
                        . ' href="' . str_replace('%host%', $this->assetHost, $item['path']) . '" />';
                    break;
                default:
                    break;
            }
        }
        return $out . ($inline ? '<style>' . $inline . '</style>' : '');
    }

    /**
     * Build js script tag's.
     * @param   bool    $isBottom   Build js for bottom (TRUE) or top (FALSE) block [optional, default true].
     * @return  string
     */
    public function buildJs($isBottom = true)
    {
        if (!$this->isBuilt) {
            $this->processResources();
        }

        $out = '';
        $inline = '';
        foreach ($this->assets['js'] as $item) {
            if ($item['bottom'] !== $isBottom) {
                continue;
            }

            switch ($item['res']) {
                case 'inline':
                    $inline .= $item['body'];
                    break;
                case 'link':
                    $out .= '<script type="text/javascript"' . ($item['async'] ? ' async' : '')
                        . ' src="' . $item['source'] . '"></script>';
                    break;
                case 'file':
                    $out .= '<script type="text/javascript"' . ($item['async'] ? ' async' : '')
                        . ' src="' . str_replace('%host%', $this->assetHost, $item['path']) . '"></script>';
                    break;
                default:
                    break;
            }
        }
        return $out . ($inline ? '<script type="text/javascript">/*<![CDATA[*/' . $inline . '/*]]>*/</script>' : '');
    }

    /**
     * Build icons tag's.
     * @return  string
     */
    public function buildIcons()
    {
        if (!$this->isBuilt) {
            $this->processResources();
        }

        $out = '';
        foreach ($this->assets['icon'] as $item) {
            $out .= '<link rel="' . $item['rel'] . '"'
                . (array_key_exists('sizes', $item) ? ' sizes="' . $item['sizes'] . '"' : '')
                . ' href="' . str_replace('%host%', $this->assetHost, $item['path']) . '" />';
        }
        return $out;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'resource';
    }

    /**
     * Process resources.
     */
    protected function processResources()
    {
        $this->assetHost = $this->distributor->getHost($this->theme);

        // detect base preset & get presets definitions
        $basePreset = null;
        if (!empty($this->rules['preset'])) {
            foreach ($this->rules['preset'] as $name => &$definition) {
                $definition = $this->distributor->getPreset($name, $this->theme);
                if (!$definition) {
                    unset($this->rules['preset'][$name]);
                } elseif (!$definition['appendable']) {
                    $basePreset = $name;
                }
            }
            unset($definition);
        }

        // get base preset
        if ($basePreset) {
            $preset = $this->rules['preset'][$basePreset];
            unset($this->rules['preset'][$basePreset]);
        } else {
            $preset = $this->distributor->getPreset(null, $this->theme);
        }
        if ($preset) {
            foreach ($preset['items'] as $name) {
                if (empty($this->rules['loaded'][$name])) {
                    if ($definition = $this->distributor->getResource($name, $this->theme)) {
                        $this->assets[$definition['type']][$name] = $definition;
                    }
                    $this->rules['loaded'][$name] = true;
                }
            }
        }

        // append presets
        if (!empty($this->rules['preset'])) {
            foreach ($this->rules['preset'] as $preset) {
                foreach ($preset['items'] as $name) {
                    if (empty($this->rules['loaded'][$name])) {
                        if ($definition = $this->distributor->getResource($name, $this->theme)) {
                            $this->assets[$definition['type']][$name] = $definition;
                        }
                        $this->rules['loaded'][$name] = true;
                    }
                }
            }
        }

        // add resources
        if (!empty($this->rules['css'])) {
            foreach ($this->rules['css'] as $name => $resource) {
                if (empty($this->rules['loaded'][$name])) {
                    if ($resource['type'] === 'resource') {
                        if ($definition = $this->distributor->getResource($name, $this->theme)) {
                            $this->assets[$definition['type']][$name] = $definition;
                        }
                    } elseif ($resource['type'] === 'content') {
                        $this->assets['css'][$name] = [
                            'type' => 'css',
                            'res' => 'inline',
                            'name' => $name,
                            'body' => $resource['body']
                        ];
                    }
                    $this->rules['loaded'][$name] = true;
                }
            }
        }
        if (!empty($this->rules['js'])) {
            foreach ($this->rules['js'] as $name => $resource) {
                if (empty($this->rules['loaded'][$name])) {
                    if ($resource['type'] === 'resource') {
                        if ($definition = $this->distributor->getResource($name, $this->theme)) {
                            $this->assets[$definition['type']][$name] = $definition;
                        }
                    } elseif ($resource['type'] === 'content') {
                        $this->assets['js'][$name] = [
                            'type' => 'js',
                            'res' => 'inline',
                            'name' => $name,
                            'body' => $resource['body'],
                            'bottom' => $resource['bottom']
                        ];
                    }
                    $this->rules['loaded'][$name] = true;
                }
            }
        }

        $this->isBuilt = true;
    }
}
