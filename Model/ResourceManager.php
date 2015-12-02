<?php

/**
 * This file is part of the DinecatThemeBundle package.
 * @copyright   2015 UAB Dinecat, http://dinecat.com/
 * @license     http://dinecat.com/licenses/mit MIT License
 * @link        https://github.com/dinecat/ThemeBundle
 */

namespace Dinecat\ThemeBundle\Model;

use Dinecat\ThemeBundle\Model\Repository\ResourceRepositoryInterface;

/**
 * Manager for resources.
 * @package DinecatThemeBundle\Model
 * @author  Mykola Zyk <relo.san.pub@gmail.com>
 */
class ResourceManager
{
    /**
     * @var ResourceRepositoryInterface
     */
    protected $repo;

    /**
     * @var array {
     *     @var string  $default_theme  Default theme name.
     *     @var string  $default_preset Default preset name.
     *     @var array   $host           Array of pairs $themeName => $themeHost.
     * }
     */
    protected $options;

    /**
     * Constructor.
     * @param   ResourceRepositoryInterface $repository
     * @param   array   $options {
     *     @var string  $default_theme  Default theme name.
     *     @var string  $default_preset Default preset name.
     *     @var array   $host           Array of pairs $themeName => $themeHost
     * }
     */
    public function __construct(ResourceRepositoryInterface $repository, array $options)
    {
        $this->options = $options;
        $this->repo = $repository;
    }

    /**
     * Set link to resource.
     * @param   string  $theme      Theme name.
     * @param   string  $name       Resource name.
     * @param   string  $source     Resource URL.
     * @param   array   $options    Options [optional].
     * @return  static
     */
    public function setLink($theme, $name, $source, array $options = [])
    {
        $this->repo->setResource(
            $theme ?: $this->options['default_theme'],
            $name,
            array_merge(
                ['type' => array_slice(explode('.', $name), -1)[0], 'res' => 'link', 'name' => $name, 'source' => $source],
                $options
            )
        );
        return $this;
    }

    /**
     * Set inline resource.
     * @param   string  $theme      Theme name.
     * @param   string  $name       Resource name.
     * @param   string  $body       Resource content.
     * @param   array   $options    Options [optional].
     * @return  static
     */
    public function setInline($theme, $name, $body, array $options = [])
    {
        $this->repo->setResource(
            $theme ?: $this->options['default_theme'],
            $name,
            array_merge(
                ['type' => array_slice(explode('.', $name), -1)[0], 'res' => 'inline', 'name' => $name, 'body' => $body],
                $options
            )
        );
        return $this;
    }

    /**
     * Set file resource.
     * @param   string  $theme      Theme name.
     * @param   string  $name       Resource name.
     * @param   string  $path       Resource destination (path + file).
     * @param   array   $options    Options [optional].
     * @return  static
     */
    public function setFile($theme, $name, $path, array $options = [])
    {
        $this->repo->setResource(
            $theme ?: $this->options['default_theme'],
            $name,
            array_merge(
                ['type' => array_slice(explode('.', $name), -1)[0], 'res' => 'file', 'name' => $name, 'path' => $path],
                $options
            )
        );
        return $this;
    }

    /**
     * Set preset.
     * @param   string  $theme          Theme name.
     * @param   string  $name           Preset name.
     * @param   array   $resources      Associated resource names.
     * @param   bool    $isAppendable   Is preset basic or appendable [optional, default false].
     * @return  static
     */
    public function setPreset($theme, $name, $resources, $isAppendable = false)
    {
        $this->repo->setResource(
            $theme ?: $this->options['default_theme'],
            $name,
            ['type' => 'group', 'name' => $name, 'items' => $resources, 'appendable' => $isAppendable]
        );
        return $this;
    }

    /**
     * Compare resource version.
     * @param   string  $theme      Theme name.
     * @param   string  $name       Resource name.
     * @param   string  $version    Resource version.
     * @return  bool    TRUE if resource exist and same version, FALSE otherwise.
     */
    public function compareVersion($theme, $name, $version)
    {
        if ($res = $this->repo->getResource($theme, $name)) {
            return $res['version'] === $version;
        }
        return false;
    }
}
