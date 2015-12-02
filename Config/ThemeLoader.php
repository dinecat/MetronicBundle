<?php

/**
 * This file is part of the DinecatThemeBundle package.
 * @copyright   2015 UAB Dinecat, http://dinecat.com/
 * @license     http://dinecat.com/licenses/mit MIT License
 * @link        https://github.com/dinecat/ThemeBundle
 */

namespace Dinecat\ThemeBundle\Config;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * Loader for theme configuration.
 * @package DinecatThemeBundle\ThemeConfig
 * @author  Mykola Zyk <relo.san.pub@gmail.com>
 */
class ThemeLoader extends FileLoader
{
    /**
     * @var \SimpleXMLElement
     */
    protected $xml;

    /**
     * {@inheritDoc}
     */
    public function load($file, $type = null)
    {
        $path = $this->locator->locate($file);
        $xml = $this->parseFile($path);
        $xml->registerXPathNamespace('themes', 'http://dinecat.com/schema/theme-bundle/themes');
        $this->xml = $xml;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'xml' === pathinfo($resource, PATHINFO_EXTENSION);
    }

    /**
     * Parses a XML file.
     * @param   string  $file   Path to a configuration file.
     * @return  \SimpleXMLElement
     * @throws  InvalidConfigurationException   When loading of XML file returns error.
     */
    protected function parseFile($file)
    {
        try {
            $dom = XmlUtils::loadFile($file, array($this, 'validateSchema'));
        } catch (\InvalidArgumentException $e) {
            throw new InvalidConfigurationException(sprintf('Unable to parse file "%s".', $file), $e->getCode(), $e);
        }
        return simplexml_import_dom($dom);
    }

    /**
     * Validates a documents XML schema.
     * @param   \DOMDocument $dom
     * @return  boolean
     */
    public function validateSchema(\DOMDocument $dom)
    {
        return $dom->schemaValidate(__DIR__ . '/../Resources/xsd/themes-1.0.xsd');
    }

    /**
     * Get names of existing themes.
     * @return  array
     * @throws  InvalidConfigurationException   When loading of XML file returns error.
     */
    public function getThemes()
    {
        if (false === $config = $this->xml->xpath('//themes:theme')) {
            throw new InvalidConfigurationException('Incorrect or missing themes configuration.');
        }

        $themes = [];
        foreach ($config as $theme) {
            $themes[] = XmlUtils::phpize($theme['id']);
        }
        return $themes;
    }

    /**
     * Parse theme definition.
     * @param   string  $themeId    Id of theme.
     * @return  array
     */
    public function parseDefinition($themeId)
    {
        $xml = $this->xml;
        if (false === $xml->xpath('//themes:theme')) {
            throw new InvalidConfigurationException('Incorrect or missing themes configuration.');
        }

        if (!$theme = $xml->xpath(sprintf('//themes:theme[@id="%s"]', $themeId))) {
            throw new InvalidConfigurationException(sprintf('Configuration for theme "%s" not found.', $themeId));
        }

        $config = [
            'name' => $themeId,
            'type' => XmlUtils::phpize($theme[0]['type']),
            'root' => XmlUtils::phpize($theme[0]['root']),
            'pack' => XmlUtils::phpize($theme[0]['pack']),
            'pattern' => XmlUtils::phpize($theme[0]['pattern']),
            'stylesheets' => [],
            'javascripts' => [],
            'icons' => [],
            'images' => [],
            'presets' => []
        ];

        /** @var \SimpleXMLElement[] $theme */
        foreach ($theme[0]->children() as $type => $def) {
            switch ($type) {
                case 'stylesheets':
                    $config['stylesheets'] = $this->parseStylesheets($def);
                    break;
                case 'javascripts':
                    $config['javascripts'] = $this->parseJavascripts($def);
                    break;
                case 'icons':
                    $config['icons'] = $this->parseIcons($def);
                    break;
                case 'images':
                    $config['images'] = $this->parseImages($def);
                    break;
                case 'presets':
                    $config['presets'] = $this->parsePresets($def);
                    break;
                default:
                    break;
            }
        }
        return $config;
    }

    /**
     * Parse stylesheets definitions.
     * @param   \SimpleXMLElement $definition
     * @return  array
     */
    protected function parseStylesheets(\SimpleXMLElement $definition)
    {
        $out = [];
        /** @var \SimpleXMLElement $item */
        foreach ($definition->children() as $item) {
            /** @noinspection PhpUndefinedFieldInspection */
            $out[XmlUtils::phpize($item['id'])] = [
                'id' => XmlUtils::phpize($item['id']),
                'name' => XmlUtils::phpize($item['name']),
                'filter' => XmlUtils::phpize($item['filter']),
                'path' => XmlUtils::phpize($item['path']),
                'type' => XmlUtils::phpize($item['type']),
                'src' => array_map(function($e){return XmlUtils::phpize($e);}, (array)$item->src),
                'compress' => (bool)XmlUtils::phpize($item['compress']),
                'media' => XmlUtils::phpize($item['media']),
                'version' => (bool)XmlUtils::phpize($item['version'])
            ];
        }
        return $out;
    }

    /**
     * Parse javascripts definitions.
     * @param   \SimpleXMLElement $definition
     * @return  array
     */
    protected function parseJavascripts(\SimpleXMLElement $definition)
    {
        $out = [];
        /** @var \SimpleXMLElement $item */
        foreach ($definition->children() as $item) {
            /** @noinspection PhpUndefinedFieldInspection */
            $out[XmlUtils::phpize($item['id'])] = [
                'id' => XmlUtils::phpize($item['id']),
                'name' => XmlUtils::phpize($item['name']),
                'filter' => XmlUtils::phpize($item['filter']),
                'path' => XmlUtils::phpize($item['path']),
                'type' => XmlUtils::phpize($item['type']),
                'src' => array_map(function($e){return XmlUtils::phpize($e);}, (array)$item->src),
                'compress' => (bool)XmlUtils::phpize($item['compress']),
                'bottom' => $item['bottom'] ? XmlUtils::phpize($item['bottom']) : true,
                'async' => (bool)XmlUtils::phpize($item['async']),
                'version' => (bool)XmlUtils::phpize($item['version'])
            ];
        }
        return $out;
    }

    /**
     * Parse icons definitions.
     * @param   \SimpleXMLElement $definition
     * @return  array
     */
    protected function parseIcons(\SimpleXMLElement $definition)
    {
        $out = [];
        /** @var \SimpleXMLElement $item */
        foreach ($definition->children() as $item) {
            $out[XmlUtils::phpize($item['id'])] = [
                'id' => XmlUtils::phpize($item['id']),
                'name' => XmlUtils::phpize($item['name']),
                'type' => XmlUtils::phpize($item['type']),
                'sizes' => XmlUtils::phpize($item['sizes']),
                'path' => XmlUtils::phpize($item['path']),
                'src' => XmlUtils::phpize($item['src']),
                'version' => (bool)XmlUtils::phpize($item['version'])
            ];
        }
        return $out;
    }

    /**
     * Parse images definitions.
     * @param   \SimpleXMLElement $definition
     * @return  array
     */
    protected function parseImages(\SimpleXMLElement $definition)
    {
        $out = [];
        /** @var \SimpleXMLElement $item */
        foreach ($definition->children() as $item) {
            /** @noinspection PhpUndefinedFieldInspection */
            $out[XmlUtils::phpize($item['name'])] = [
                'name' => XmlUtils::phpize($item['name']),
                'src' => XmlUtils::phpize($item['src']),
                'path' => XmlUtils::phpize($item['path']),
                'image' => array_map(function($e){return XmlUtils::phpize($e);}, (array)$item->image)
            ];
        }
        return $out;
    }

    /**
     * Parse presets definitions.
     * @param   \SimpleXMLElement $definition
     * @return  array
     */
    protected function parsePresets(\SimpleXMLElement $definition)
    {
        $out = [];
        /** @var \SimpleXMLElement $item */
        foreach ($definition->children() as $item) {
            /** @noinspection PhpUndefinedFieldInspection */
            $out[XmlUtils::phpize($item['name'])] = [
                'name' => XmlUtils::phpize($item['name']),
                'appendable' => XmlUtils::phpize($item['appendable']),
                'items' => array_map(function($e){return XmlUtils::phpize($e);}, (array)$item->resource)
            ];
        }
        return $out;
    }
}
