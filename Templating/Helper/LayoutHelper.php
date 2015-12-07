<?php

/**
 * This file is part of the DinecatThemeBundle package.
 * @copyright   2015 UAB Dinecat, http://dinecat.com/
 * @license     http://dinecat.com/licenses/mit MIT License
 * @link        https://github.com/dinecat/ThemeBundle
 */

namespace Dinecat\ThemeBundle\Templating\Helper;

use Symfony\Component\Templating\Helper\Helper;

/**
 * Document layout helper.
 * @package DinecatThemeBundle\Templating
 * @author  Mykola Zyk <relo.san.pub@gmail.com>
 */
class LayoutHelper extends Helper
{
    /**
     * @var array
     */
    protected $options = [
        'title' => ['format' => ['action', 'section', 'suffix'], 'del' => ' « ']
    ];

    /**
     * @var array
     */
    protected $parts = [];

    /**
     * Build title.
     * @return  string  Title.
     */
    public function buildTitle()
    {
        $title = [];
        foreach ($this->options['title']['format'] as $part) {
            if (!empty($this->options['title']['parts'][$part])) {
                $title[] = $this->options['title']['parts'][$part];
            }
        }
        return implode($this->options['title']['del'], $title);
    }

    /**
     * Add title (or part of title).
     * @param   string  $title  Page title (or part of).
     * @param   string  $part   Title part [optional, default "action"].
     */
    public function addTitlePart($title, $part = 'action')
    {
        $this->options['title']['parts'][$part] = $title;
    }

    /**
     * Prints tag attributes.
     * @param   string  $tag    Tag name (one of static::TAG_* constant).
     * @return  string  Tag attributes.
     */
    public function buildTagAttributes($tag)
    {
        if (!empty($this->options['tags'][$tag]['attrs'])) {
            return $this->compileAttributes($this->options['tags'][$tag]['attrs']);
        }
        return '';
    }

    /**
     * Add class to tag/element.
     * @param   string          $tag    Tag/element name.
     * @param   string|string[] $class  Class name (or array of class names).
     */
    public function addClass($tag, $class)
    {
        if (is_array($class)) {
            if (!empty($this->options['tags'][$tag]['attrs']['class'])) {
                $this->options['tags'][$tag]['attrs']['class'] = array_merge(
                    $this->options['tags'][$tag]['attrs']['class'],
                    $class
                );
            } else {
                $this->options['tags'][$tag]['attrs']['class'] = $class;
            }
        } else {
            $this->options['tags'][$tag]['attrs']['class'][] = $class;
        }
    }

    /**
     * Returns the canonical name of this helper.
     * @return  string
     */
    public function getName()
    {
        return 'layout';
    }

    /**
     * Compile html attributes on string
     * @param   array   $attributes Html attributes in format "name => content/items" [optional].
     * @return  string
     */
    protected function compileAttributes(array $attributes = [])
    {
        if (!$attributes) {
            return '';
        }

        return ' ' . implode(' ', array_map(
            function ($attr, $content) {
                if (is_array($content)) {
                    return $attr . '="' . str_replace('"', '&quote;', implode(' ', $content)) . '"';
                } elseif ($content) {
                    return $attr . '="' . str_replace('"', '&quote;', $content) . '"';
                }
                return '';
            },
            array_keys($attributes),
            $attributes
        ));
    }
}
