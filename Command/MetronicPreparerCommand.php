<?php

/**
 * This file is part of the DinecatThemeBundle package.
 * @copyright   2015 UAB Dinecat, http://dinecat.com/
 * @license     http://dinecat.com/licenses/mit MIT License
 * @link        https://github.com/dinecat/ThemeBundle
 */

namespace Dinecat\ThemeBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Task for preparing resources.
 * @package DinecatThemeBundle\Command
 * @author  Mykola Zyk <relo.san.pub@gmail.com>
 */
class MetronicPreparerCommand extends ContainerAwareCommand
{
    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var string
     */
    protected $srcRoot;

    /**
     * @var string
     */
    protected $tmpRoot;

    /**
     * @var string
     */
    protected $outRoot;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('theme:resources:prepare:metronic')
            ->setDescription('Prepate resources for Metronic theme.');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $this->srcRoot = $container->getParameter('theme_src_root');
        $this->tmpRoot = $container->getParameter('theme_tmp_root');
        $this->outRoot = $container->getParameter('theme_out_root');
        $output->getFormatter()->setStyle('log', new OutputFormatterStyle('cyan'));
        $output->getFormatter()->setStyle('ok', new OutputFormatterStyle('black', 'green'));
        $this->output = $output;
        $this->fs = new Filesystem();

        $this->prepareBootstrap();
        $this->prepareUniform();
        $this->prepareDatatables();
        $this->prepareTheme();

        if ($this->output->getVerbosity()) {
            $this->output->writeln('<info>All resources prepared.</info>');
        }
    }

    /**
     * Prepare bootstrap resources.
     */
    protected function prepareBootstrap()
    {
        $srcPath = $this->srcRoot . '/theme/assets/global/plugins/bootstrap/';
        if (!$this->fs->exists($srcPath . 'css/bootstrap.css')) {
            $this->output->writeln('<error>Error with get source file for bootstrap.css.</error>');
            return;
        }
        $css = str_replace(
            '../fonts/bootstrap/glyphicons',
            '../fonts/glyphicons',
            file_get_contents($srcPath . 'css/bootstrap.css')
        );
        $this->fs->mkdir($this->tmpRoot . '/css', 0644);
        try {
            $this->fs->dumpFile($this->tmpRoot . '/css/bootstrap.css', $css);
        } catch (\Exception $e) {
            $this->output->writeln(sprintf('<error>Exception: "%s".</error>', $e->getMessage()));
            $this->output->writeln('<error>Error with saving tmp file for bootstrap.css.</error>');
            return;
        }
        if ($this->output->getVerbosity() === 2) {
            $this->output->writeln('<ok>+</ok> <log>Stylesheet bootstrap.css prepared.</log>');
        }

        if ($this->output->getVerbosity()) {
            $this->output->writeln('<log>Bootstrap plugin prepared.</log> <ok> OK </ok>');
        }
    }

    /**
     * Prepare uniform resources.
     */
    protected function prepareUniform()
    {
        $srcPath = $this->srcRoot . '/theme/assets/global/plugins/uniform/';
        if (!$this->fs->exists($srcPath . 'css/uniform.default.css')) {
            $this->output->writeln('<error>Error with get source file for uniform.default.css.</error>');
            return;
        }
        $css = str_replace(
            '../images/',
            '../images/uf/',
            file_get_contents($srcPath . 'css/uniform.default.css')
        );
        $this->fs->mkdir($this->tmpRoot . '/css', 0644);
        try {
            $this->fs->dumpFile($this->tmpRoot . '/css/uniform.css', $css);
        } catch (\Exception $e) {
            $this->output->writeln(sprintf('<error>Exception: "%s".</error>', $e->getMessage()));
            $this->output->writeln('<error>Error with saving tmp file for uniform.css.</error>');
            return;
        }
        if ($this->output->getVerbosity() === 2) {
            $this->output->writeln('<ok>+</ok> <log>Stylesheet uniform.css prepared.</log>');
        }

        if ($this->output->getVerbosity()) {
            $this->output->writeln('<log>Uniform plugin prepared.</log> <ok> OK </ok>');
        }
    }

    /**
     * Prepare datatables resources.
     */
    protected function prepareDatatables()
    {
        $srcPath = $this->srcRoot . '/theme/assets/global/plugins/datatables/';
        if (!$this->fs->exists($srcPath . 'datatables.css')) {
            $this->output->writeln('<error>Error with get source file for datatables.css.</error>');
            return;
        }
        $css = str_replace(
            'DataTables-1.10.8/images/',
            '../images/dt/',
            file_get_contents($srcPath . 'datatables.css')
        );
        $this->fs->mkdir($this->tmpRoot . '/css', 0644);
        try {
            $this->fs->dumpFile($this->tmpRoot . '/css/datatables.css', $css);
        } catch (\Exception $e) {
            $this->output->writeln(sprintf('<error>Exception: "%s".</error>', $e->getMessage()));
            $this->output->writeln('<error>Error with saving tmp file for datatables.css.</error>');
            return;
        }
        if ($this->output->getVerbosity() === 2) {
            $this->output->writeln('<ok>+</ok> <log>Stylesheet datatables.css prepared.</log>');
        }

        if ($this->output->getVerbosity()) {
            $this->output->writeln('<log>DataTables plugin prepared.</log> <ok> OK </ok>');
        }
    }

    /**
     * Prepare datatables resources.
     */
    protected function prepareTheme()
    {
        // components-md.css
        $srcPath = $this->srcRoot . '/theme/assets/global/';
        if (!$this->fs->exists($srcPath . 'css/components-md.css')) {
            $this->output->writeln('<error>Error with get source file for components-md.css.</error>');
            return;
        }
        $css = str_replace(
            '../img/',
            '../images/bs/',
            file_get_contents($srcPath . 'css/components-md.css')
        );
        $this->fs->mkdir($this->tmpRoot . '/css', 0644);
        try {
            $this->fs->dumpFile($this->tmpRoot . '/css/components-md.css', $css);
        } catch (\Exception $e) {
            $this->output->writeln(sprintf('<error>Exception: "%s".</error>', $e->getMessage()));
            $this->output->writeln('<error>Error with saving tmp file for components-md.css.</error>');
            return;
        }
        if ($this->output->getVerbosity() === 2) {
            $this->output->writeln('<ok>+</ok> <log>Stylesheet components-md.css prepared.</log>');
        }

        // plugins-md.css
        if (!$this->fs->exists($srcPath . 'css/plugins-md.css')) {
            $this->output->writeln('<error>Error with get source file for plugins-md.css.</error>');
            return;
        }
        $css = str_replace(
            ['../img/', '../plugins/datatables/images/'],
            ['../images/bs/', '../images/dt/'],
            file_get_contents($srcPath . 'css/plugins-md.css')
        );
        try {
            $this->fs->dumpFile($this->tmpRoot . '/css/plugins-md.css', $css);
        } catch (\Exception $e) {
            $this->output->writeln(sprintf('<error>Exception: "%s".</error>', $e->getMessage()));
            $this->output->writeln('<error>Error with saving tmp file for plugins-md.css.</error>');
            return;
        }
        if ($this->output->getVerbosity() === 2) {
            $this->output->writeln('<ok>+</ok> <log>Stylesheet plugins-md.css prepared.</log>');
        }

        // app.js
        if (!$this->fs->exists($srcPath . 'scripts/app.js')) {
            $this->output->writeln('<error>Error with get source file for app.js.</error>');
            return;
        }
        $js = str_replace(
            ['../assets/', 'global/img/', 'global/css/', 'App.init();'],
            ['/st/', 'images/bs/', 'css/', ''],
            file_get_contents($srcPath . 'scripts/app.js')
        );
        $this->fs->mkdir($this->tmpRoot . '/js', 0644);
        try {
            $this->fs->dumpFile($this->tmpRoot . '/js/app.js', $js);
        } catch (\Exception $e) {
            $this->output->writeln(sprintf('<error>Exception: "%s".</error>', $e->getMessage()));
            $this->output->writeln('<error>Error with saving tmp file for app.js.</error>');
            return;
        }
        if ($this->output->getVerbosity() === 2) {
            $this->output->writeln('<ok>+</ok> <log>Script app.js prepared.</log>');
        }

        // layout.css
        $srcPath = $this->srcRoot . '/theme/assets/layouts/layout/';
        if (!$this->fs->exists($srcPath . 'css/layout.css')) {
            $this->output->writeln('<error>Error with get source file for layout.css.</error>');
            return;
        }
        $css = str_replace(
            '../img/',
            '../images/bt/',
            file_get_contents($srcPath . 'css/layout.css')
        );
        try {
            $this->fs->dumpFile($this->tmpRoot . '/css/layout.css', $css);
        } catch (\Exception $e) {
            $this->output->writeln(sprintf('<error>Exception: "%s".</error>', $e->getMessage()));
            $this->output->writeln('<error>Error with saving tmp file for layout.css.</error>');
            return;
        }
        if ($this->output->getVerbosity() === 2) {
            $this->output->writeln('<ok>+</ok> <log>Stylesheet layout.css prepared.</log>');
        }

        // color themes
        foreach (['blue', 'darkblue', 'default', 'grey', 'light', 'light2'] as $theme) {
            if (!$this->fs->exists($srcPath . 'css/themes/' . $theme . '.css')) {
                $this->output->writeln(sprintf('<error>Error with get source file for %s.css.</error>', $theme));
                return;
            }
            $css = str_replace(
                '../../img/',
                '../images/bt/',
                file_get_contents($srcPath . 'css/themes/' . $theme . '.css')
            );
            try {
                $this->fs->dumpFile($this->tmpRoot . '/css/theme-' . $theme . '.css', $css);
            } catch (\Exception $e) {
                $this->output->writeln(sprintf('<error>Exception: "%s".</error>', $e->getMessage()));
                $this->output->writeln(sprintf('<error>Error with saving tmp file for theme-%s.css.</error>', $theme));
                return;
            }
            if ($this->output->getVerbosity() === 2) {
                $this->output->writeln(sprintf('<ok>+</ok> <log>Stylesheet theme-%s.css prepared.</log>', $theme));
            }
        }

        if ($this->output->getVerbosity()) {
            $this->output->writeln('<log>Theme resources prepared.</log> <ok> OK </ok>');
        }
    }
}
