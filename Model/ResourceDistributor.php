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
 * Resources distributor.
 * @package DinecatThemeBundle\Model
 * @author  Mykola Zyk <relo.san.pub@gmail.com>
 */
class ResourceDistributor
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
     * Get host for theme resources.
     * @param   string  $theme  Theme name [optional].
     * @return  string
     */
    public function getHost($theme = null)
    {
        $theme = $theme ?: $this->options['default_theme'];
        return array_key_exists($theme, $this->options['host']) ? $this->options['host'][$theme] : '/';
    }

    /**
     * Get resource.
     * @param   string  $name   Resource name.
     * @param   string  $theme  Theme name [optional].
     * @return  array|null
     */
    public function getResource($name, $theme = null)
    {
        return $this->repo->getResource($theme ?: $this->options['default_theme'], $name);
    }

    /**
     * Get preset.
     * @param   string  $name   Preset name [optional].
     * @param   string  $theme  Theme name [optional].
     * @return  array|null
     */
    public function getPreset($name = null, $theme = null)
    {
        return $this->repo->getResource(
            $theme ?: $this->options['default_theme'],
            $name ?: $this->options['default_preset']
        );
    }
}
