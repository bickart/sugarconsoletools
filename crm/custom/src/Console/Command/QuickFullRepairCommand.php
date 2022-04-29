<?php
// Copyright 2016 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.

namespace Sugarcrm\Sugarcrm\custom\Console\Command;

use LanguageManager;
use Psr\SimpleCache\CacheInterface;
use RepairAndClear;
use Sugarcrm\Sugarcrm\Console\CommandRegistry\Mode\InstanceModeInterface;
use Sugarcrm\Sugarcrm\DependencyInjection\Container;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


require_once 'modules/Administration/QuickRepairAndRebuild.php';

/**
 *
 * Simple Quick Repair command example
 *
 */
class QuickFullRepairCommand extends QuickRepairCommand implements InstanceModeInterface
{
    /**
     * {inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('admin:full-qrr')
            ->setDescription('Run Quick Repair and Rebuild, clear and rebuild cache');
    }

    /**
     * {inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<info>Starting Quick Repair...</info>");
        $output->writeln('Clearing cache...');
        $this->clearCache();
        $this->buildAutoloaderCache();
        $this->repair();
        $this->buildAutoloaderCache();
        $this->removeJsAndLanguages();
        $output->writeln('Executing basic instance warm-up...');
        $this->basicWarmUp();
        $output->writeln("<fg=green;options=bold>Complete.</>");
    }

    protected function clearCache()
    {
        $cache = Container::getInstance()->get(CacheInterface::class);
        $cache->clear();
    }

    protected function buildAutoloaderCache()
    {
        \SugarAutoLoader::buildCache();
    }


    protected function basicWarmUp()
    {
        // rebuild some stuff
        self::buildAutoloaderCache();

        // quick load of all beans
        global $beanList, $app_list_strings, $current_language;
        $full_module_list = array_merge($beanList, $app_list_strings['moduleList']);
        foreach ($full_module_list as $module => $label) {
            $bean = \BeanFactory::newBean($module);
            // load language too
            \LanguageManager::createLanguageFile($module, ['default'], true);
            $mod_strings = return_module_language($current_language, $module);
        }

        // load app strings
        $app_list_strings = return_app_list_strings_language($current_language);
        $app_strings = return_application_language($current_language);

        // load api
        $sd = new \ServiceDictionary();
        $sd->buildAllDictionaries();
    }
}

