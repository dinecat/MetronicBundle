<?php

/**
 * This file is part of the DinecatThemeBundle package.
 * @copyright   2015 UAB Dinecat, http://dinecat.com/
 * @license     http://dinecat.com/licenses/mit MIT License
 * @link        https://github.com/dinecat/ThemeBundle
 */

namespace Dinecat\ThemeBundle\Command;

use Dinecat\ThemeBundle\Config\ThemeLoader;
use YUI\Compressor;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Task for building resources.
 * @package DinecatThemeBundle\Command
 * @author  Mykola Zyk <relo.san.pub@gmail.com>
 */
class ResourceBuilderCommand extends ContainerAwareCommand
{
    /**
     * @var ThemeLoader
     */
    protected $loader;

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var \Dinecat\ThemeBundle\Model\ResourceManager
     */
    protected $manager;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var string
     */
    protected $root;

    /**
     * Resources for registration.
     * @var array {
     *     @var array   $resources  Resources.
     *     @var array   $presets    Presets.
     * }
     */
    protected $rules = ['resources' => [], 'presets' => []];

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('theme:resources:build')
            ->setDescription('Build resources.')
            ->addArgument('theme', InputArgument::OPTIONAL, 'Name of theme.');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $this->root = $container->get('kernel')->getRootDir();
        $themeId = $input->getArgument('theme');
        $output->getFormatter()->setStyle('log', new OutputFormatterStyle('cyan'));
        $output->getFormatter()->setStyle('ok', new OutputFormatterStyle('black', 'green'));
        $this->output = $output;

        $this->loader = new ThemeLoader(new FileLocator($this->root));
        $this->fs = new Filesystem();
        $this->manager = $container->get('dinecat.theme.resource.manager');

        if (file_exists($this->root . '/config/themes.xml')) {
            $this->loader->load('config/themes.xml');
        } else {
            $this->output->writeln('<error>Configuration theme(s) file themes.xml not found.</error>');
            return;
        }

        if ($themeId) {
            $themes[] = $themeId;
            if ($output->getVerbosity() === 2) {
                $output->writeln(sprintf('<log>Theme "%s" selected.</log>', $themeId));
            }
        } else {
            $themes = $this->loader->getThemes();
            if ($output->getVerbosity() === 2) {
                $output->writeln(sprintf(
                    '<log>Theme%s %s found.</log>',
                    count($themes) > 1 ? 's' : '',
                    '"' . implode('", "', $themes) . '"'
                ));
            }
        }

        foreach ($themes as $theme) {
            $definition = $this->loader->parseDefinition($theme);

            // prepare theme resources
            if ($definition['type'] === 'metronic') {
                $preparer = $this->getApplication()->find('theme:resources:prepare:metronic');
                $preparer->run(
                    new ArrayInput([
                        'command' => 'theme:resources:prepare:metronic',
                        '--verbose' => $this->output->getVerbosity()
                    ]),
                    $output
                );
            }

            if (!$this->buildStylesheets($definition)) {
                return;
            }
            if (!$this->buildJavascripts($definition)) {
                return;
            }
            if (!$this->copyIcons($definition)) {
                return;
            }
            if (!$this->copyImages($definition)) {
                return;
            }
            if (!$this->buildPresets($definition)) {
                return;
            }

            if (!$this->registerResources()) {
                return;
            }
        }
        $output->writeln(sprintf('<info>Theme%s built.</info> <ok> OK </ok>', count($themes) > 1 ? 's' : ''));
    }

    /**
     * Build stylesheets.
     * @param   array   $definition Theme definition.
     * @return  bool    FALSE on any error, TRUE on success.
     */
    protected function buildStylesheets($definition)
    {
        $resRoot = str_replace('%kernel.root_dir%', $this->root, $definition['root']);
        $cssDir = $resRoot . ($definition['pack'] ? '/' . $definition['pack'] : '') . '/css';
        $this->fs->mkdir($cssDir, 0644);
        $yui = new Compressor(['type' => Compressor::TYPE_CSS]);
        $less = new \lessc;

        foreach ($definition['stylesheets'] as $item) {
            $item['src'] = str_replace('%kernel.root_dir%', $this->root, $item['src']);
            switch ($item['type']) {
                case 'link':
                    $this->rules['resources'][] = [
                        'theme' => $definition['name'],
                        'res' => 'link',
                        'name' => $item['name'],
                        'source' => $item['src'][0],
                        'options' => ['media' => $item['media']]
                    ];
                    break;

                case 'inline':
                case 'file':
                    $body = '';
                    switch ($item['filter']) {
                        case 'less':
                        case 'css':
                            foreach ($item['src'] as $src) {
                                if (is_file($src)) {
                                    $body .= $less->compileFile($src);
                                } else {
                                    $body .= $less->compile(trim(file_get_contents($src)));
                                }
                            }
                            if ($item['compress']) {
                                $body = str_replace('}', "}\n", $yui->compress($body));
                            }
                            break;
                        default:
                            $this->output->writeln(sprintf(
                                '<error>Incorrect filter for "%s" stylesheet.</error>',
                                $item['name']
                            ));
                            return false;
                    }

                    if ($item['type'] === 'file') {
                        $path = $item['path'];
                        $version = null;

                        if ($item['version']) {
                            $version = hash('crc32', $body);
                            $path = str_replace('%version%', $version, $item['path']);
                        }

                        if ($version && $this->manager->compareVersion($definition['name'], $item['name'], $version)) {
                            if ($this->output->getVerbosity() === 2) {
                                $this->output->writeln(sprintf(
                                    '<log>Stylesheet file "%s (%s)" not modified.</log>',
                                    $item['name'],
                                    $path
                                ));
                            }
                        } else {
                            $this->rules['resources'][] = [
                                'theme' => $definition['name'],
                                'res' => 'file',
                                'name' => $item['name'],
                                'path' => str_replace(
                                    '%resource%',
                                    ($definition['pack'] ? $definition['pack'] . '/' : '') . 'css/' . $path,
                                    $definition['pattern']
                                ),
                                'options' => ['media' => $item['media'], 'size' => strlen($body), 'version' => $version]
                            ];
                        }

                        if (!$item['version'] || !$this->fs->exists($cssDir . '/' . $path)) {
                            $this->fs->dumpFile($cssDir . '/' . $path, $body);
                            if ($this->output->getVerbosity() === 2) {
                                $this->output->writeln(sprintf(
                                    '<ok>+</ok> <log>Stylesheet file "%s (%s)" saved.</log>',
                                    $item['name'],
                                    $path
                                ));
                            }
                        } elseif($this->output->getVerbosity() === 2) {
                            $this->output->writeln(sprintf(
                                '<info>=</info> <log>Stylesheet file "%s (%s)" exist.</log>',
                                $item['name'],
                                $path
                            ));
                        }
                    } else {
                        $this->rules['resources'][] = [
                            'theme' => $definition['name'],
                            'res' => 'inline',
                            'name' => $item['name'],
                            'body' => $body,
                            'options' => ['media' => $item['media']]
                        ];
                    }

                    break;

                default:
                    $this->output->writeln(sprintf('<error>Incorrect type for "%s" stylesheet.</error>', $item['name']));
                    return false;
            }
        }
        if ($this->output->getVerbosity()) {
            $this->output->writeln('<log>Styles built.</log> <ok> OK </ok>');
        }
        return true;
    }

    /**
     * Build javascripts.
     * @param   array   $definition Theme definition.
     * @return  bool    FALSE on any error, TRUE on success.
     */
    protected function buildJavascripts($definition)
    {
        $resRoot = str_replace('%kernel.root_dir%', $this->root, $definition['root']);
        $jsDir = $resRoot . ($definition['pack'] ? '/' . $definition['pack'] : '') . '/js';
        $this->fs->mkdir($jsDir, 0644);
        $yui = new Compressor(['type' => Compressor::TYPE_JS]);

        foreach ($definition['javascripts'] as $item) {
            $item['src'] = str_replace('%kernel.root_dir%', $this->root, $item['src']);
            switch ($item['type']) {
                case 'link':
                    $this->rules['resources'][] = [
                        'theme' => $definition['name'],
                        'res' => 'link',
                        'name' => $item['name'],
                        'source' => $item['src'][0],
                        'options' => ['async' => $item['async'], 'bottom' => $item['bottom']]
                    ];
                    break;

                case 'inline':
                case 'file':
                    $body = '';
                    switch ($item['filter']) {
                        case 'js':
                            foreach ($item['src'] as $src) {
                                if ($item['compress']) {
                                    $body .= ($body ? "\n" : '') . $yui->compress(trim(file_get_contents($src)));
                                } else {
                                    $body .= ($body ? "\n" : '') . trim(file_get_contents($src));
                                }
                            }
                            break;
                        case 'coffee':
                            $this->output->writeln(
                                '<error>Filter for compiling coffeescript not implemented.</error>'
                            );
                            return false;
                        default:
                            $this->output->writeln(sprintf(
                                '<error>Incorrect filter for "%s" javascript.</error>',
                                $item['name']
                            ));
                            return false;
                    }

                    if ($item['type'] === 'file') {
                        $path = $item['path'];
                        $version = null;

                        if ($item['version']) {
                            $version = hash('crc32', $body);
                            $path = str_replace('%version%', $version, $item['path']);
                        }

                        if ($version && $this->manager->compareVersion($definition['name'], $item['name'], $version)) {
                            if ($this->output->getVerbosity() === 2) {
                                $this->output->writeln(sprintf(
                                    '<log>Javascript file "%s (%s)" not modified.</log>',
                                    $item['name'],
                                    $path
                                ));
                            }
                        } else {
                            $this->rules['resources'][] = [
                                'theme' => $definition['name'],
                                'res' => 'file',
                                'name' => $item['name'],
                                'path' => str_replace(
                                    '%resource%',
                                    ($definition['pack'] ? $definition['pack'] . '/' : '') . 'js/' . $path,
                                    $definition['pattern']
                                ),
                                'options' => [
                                    'async' => $item['async'],
                                    'bottom' => $item['bottom'],
                                    'size' => strlen($body),
                                    'version' => $version
                                ]
                            ];
                        }

                        if (!$item['version'] || !$this->fs->exists($jsDir . '/' . $path)) {
                            $this->fs->dumpFile($jsDir . '/' . $path, $body);
                            if ($this->output->getVerbosity() === 2) {
                                $this->output->writeln(sprintf(
                                    '<ok>+</ok> <log>Javascript file "%s (%s)" saved.</log>',
                                    $item['name'],
                                    $path
                                ));
                            }
                        } elseif($this->output->getVerbosity() === 2) {
                            $this->output->writeln(sprintf(
                                '<info>=</info> <log>Javascript file "%s (%s)" exist.</log>',
                                $item['name'],
                                $path
                            ));
                        }
                    } else {
                        $this->rules['resources'][] = [
                            'theme' => $definition['name'],
                            'res' => 'inline',
                            'name' => $item['name'],
                            'body' => $body,
                            'options' => ['async' => $item['async'], 'bottom' => $item['bottom']]
                        ];
                    }

                    break;

                default:
                    $this->output->writeln(sprintf('<error>Incorrect type for "%s" javascript.</error>', $item['name']));
                    return false;
            }
        }
        if ($this->output->getVerbosity()) {
            $this->output->writeln('<log>Scripts built.</log> <ok> OK </ok>');
        }
        return true;
    }

    /**
     * Copy icons.
     * @param   array   $definition Theme definition.
     * @return  bool    FALSE on any error, TRUE on success.
     */
    protected function copyIcons($definition)
    {
        $resRoot = str_replace('%kernel.root_dir%', $this->root, $definition['root']);

        foreach ($definition['icons'] as $item) {
            $item['src'] = str_replace('%kernel.root_dir%', $this->root, $item['src']);

            switch ($item['type']) {
                case 'favicon':
                    $this->rules['resources'][] = [
                        'theme' => $definition['name'],
                        'res' => 'file',
                        'name' => $item['name'],
                        'path' => str_replace('%resource%', $item['path'], $definition['pattern']),
                        'options' => ['rel' => 'shortcut icon', 'size' => strlen(trim(file_get_contents($item['src'])))]
                    ];
                    break;

                case 'apple-touch-icon':
                    $this->rules['resources'][] = [
                        'theme' => $definition['name'],
                        'res' => 'file',
                        'name' => $item['name'],
                        'path' => str_replace('%resource%', $item['path'], $definition['pattern']),
                        'options' => [
                            'rel' => 'apple-touch-icon',
                            'size' => strlen(trim(file_get_contents($item['src']))),
                            'sizes' => $item['sizes']
                        ]
                    ];
                    break;

                default:
                    $this->output->writeln(sprintf('<error>Incorrect type for "%s" icon.</error>', $item['name']));
                    return false;
            }

            if (
                $this->fs->exists($resRoot . '/' . $item['path'])
                && trim(file_get_contents($item['src'])) === trim(file_get_contents($resRoot . '/' . $item['path']))
            ) {
                if ($this->output->getVerbosity() === 2) {
                    $this->output->writeln(sprintf(
                        '<info>=</info> <log>Icon file "%s" not modified.</log>',
                        $item['name']
                    ));
                }
            } else {
                $this->fs->copy($item['src'], $resRoot . '/' . $item['path'], true);
                if ($this->output->getVerbosity() === 2) {
                    $this->output->writeln(sprintf('<ok>+</ok> <log>Icon file "%s" copied.</log>', $item['name']));
                }
            }
        }
        if ($this->output->getVerbosity()) {
            $this->output->writeln('<log>Icons copied.</log> <ok> OK </ok>');
        }
        return true;
    }

    /**
     * Copy images.
     * @param   array   $definition Theme definition.
     * @return  bool    FALSE on any error, TRUE on success.
     */
    protected function copyImages($definition)
    {
        $resRoot = str_replace('%kernel.root_dir%', $this->root, $definition['root']);

        foreach ($definition['images'] as $pack) {
            $pack['src'] = str_replace('%kernel.root_dir%', $this->root, $pack['src']);
            $imgDir = $resRoot . ($definition['pack'] ? '/' . $definition['pack'] : '') . '/' . $pack['path'];
            $this->fs->mkdir($imgDir, 0644);

            foreach ($pack['image'] as $item) {
                if (
                    $this->fs->exists($imgDir . '/' . $item)
                    && trim(file_get_contents($pack['src'] . '/' . $item)) === trim(file_get_contents($imgDir . '/' . $item))
                ) {
                    if ($this->output->getVerbosity() === 2) {
                        $this->output->writeln(sprintf('<info>=</info> <log>Image file "%s" not modified.</log>', $item));
                    }
                } else {
                    $this->fs->copy($pack['src'] . '/' . $item, $imgDir . '/' . $item, true);
                    if ($this->output->getVerbosity() === 2) {
                        $this->output->writeln(sprintf('<ok>+</ok> <log>Image file "%s" copied.</log>', $item));
                    }
                }
            }
        }
        if ($this->output->getVerbosity()) {
            $this->output->writeln('<log>Images copied.</log> <ok> OK </ok>');
        }
        return true;
    }

    /**
     * Build presets.
     * @param   array   $definition Theme definition.
     * @return  bool    FALSE on error, TRUE on success.
     */
    protected function buildPresets($definition)
    {
        foreach ($definition['presets'] as $preset) {
            $this->rules['presets'][] = [
                'theme' => $definition['name'],
                'name' => $preset['name'],
                'resources' => $preset['items'],
                'appendable' => $preset['appendable']
            ];
        }
        return true;
    }

    /**
     * Register resources.
     * @return  boolean
     */
    protected function registerResources()
    {
        foreach ($this->rules['resources'] as $item) {
            switch ($item['res']) {
                case 'link':
                    $this->manager->setLink($item['theme'], $item['name'], $item['source'], $item['options']);
                    break;
                case 'inline':
                    $this->manager->setInline($item['theme'], $item['name'], $item['body'], $item['options']);
                    break;
                case 'file':
                    $this->manager->setFile($item['theme'], $item['name'], $item['path'], $item['options']);
                    break;
                default:
                    $this->output->writeln(sprintf('<error>Incorrect type for "%s" resource.</error>', $item['name']));
                    return false;
            }
        }
        if ($this->output->getVerbosity()) {
            $this->output->writeln('<log>Resources registered.</log> <ok> OK </ok>');
        }

        foreach ($this->rules['presets'] as $item) {
            $this->manager->setPreset($item['theme'], $item['name'], $item['resources'], $item['appendable']);
        }
        if ($this->output->getVerbosity()) {
            $this->output->writeln('<log>Presets registered.</log> <ok> OK </ok>');
        }
        return true;
    }
}
