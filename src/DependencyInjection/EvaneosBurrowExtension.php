<?php

namespace Evaneos\BurrowBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Burrow\Driver\DriverFactory;
use Burrow\Driver;
use Burrow\Publisher\AsyncPublisher;
use Symfony\Component\DependencyInjection\Reference;
use Evaneos\Daemon\Worker;
use Symfony\Component\DependencyInjection\Definition;
use Evaneos\BurrowBundle\WorkerFactory;
use Evaneos\Daemon\CLI\DaemonWorkerCommand;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class EvaneosBurrowExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (!isset($config['default_driver'])) {
            $keys = array_keys($config['drivers']);
            $config['default_driver'] = reset($keys);
        }

        $container->setAlias('evaneos_burrow.default_driver', sprintf('evaneos_burrow.driver.%s', $config['default_driver']));

        foreach ($config['drivers'] as $name => $driver) {
            $container
                ->register(sprintf('evaneos_burrow.driver.%s', $name), Driver::class)
                ->setFactory([DriverFactory::class, 'getDriver'])
                ->addArgument($driver)
                ->setPublic(false);
        }

        foreach ($config['publishers'] as $name => $value) {
            $container
                ->register(sprintf('evaneos_burrow.publisher.%s', $name), AsyncPublisher::class)
                ->addArgument(new Reference(sprintf('evaneos_burrow.driver.%s', $value['driver'])))
                ->addArgument($value['exchange']);
        }

        foreach ($config['workers'] as $name => $value) {
            $definition = new Definition(Worker::class, [
                new Reference(sprintf('evaneos_burrow.driver.%s', $value['driver'])),
                new Reference($value['consumer']),
                $value['queue']
            ]);
            $definition->setFactory([WorkerFactory::class, 'build']);
            $container->setDefinition(sprintf('evaneos_burrow.worker.%s', $name), $definition);

            $container
                ->register(sprintf('evaneos_burrow.command.consume_%s', $name), DaemonWorkerCommand::class)
                ->addArgument($definition)
                ->addArgument(sprintf('burrow:consume:%s', $name))
                ->addTag('console.command');
        }
    }
}
