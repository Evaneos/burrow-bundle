<?php

namespace Evaneos\BurrowBundle\DependencyInjection;

use Burrow\CLI\BindCommand;
use Burrow\CLI\DeclareExchangeCommand;
use Burrow\CLI\DeclareQueueCommand;
use Burrow\CLI\DeleteExchangeCommand;
use Burrow\CLI\DeleteQueueCommand;
use Burrow\Driver;
use Burrow\Driver\DriverFactory;
use Burrow\Publisher\AsyncPublisher;
use Evaneos\BurrowBundle\PublisherFactory;
use Evaneos\BurrowBundle\WorkerFactory;
use Evaneos\Daemon\CLI\DaemonWorkerCommand;
use Evaneos\Daemon\Worker;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

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
            $definition = new Definition(AsyncPublisher::class, [
                new Reference(sprintf('evaneos_burrow.driver.%s', $value['driver'])),
                $value['exchange'],
                $value['serializingStrategy']
            ]);
            $definition->setFactory([PublisherFactory::class, 'buildAsync']);
            $container->setDefinition(sprintf('evaneos_burrow.publisher.%s', $name), $definition);
        }

        foreach ($config['workers'] as $name => $value) {
            $definition = new Definition(Worker::class, [
                new Reference(sprintf('evaneos_burrow.driver.%s', $value['driver'])),
                new Reference($value['consumer']),
                $value['queue'],
                $value['requeue_on_failure'],
                new Reference('logger'),
            ]);
            $definition->setFactory([WorkerFactory::class, 'build']);
            $container->setDefinition(sprintf('evaneos_burrow.worker.%s', $name), $definition);

            $container
                ->register(sprintf('evaneos_burrow.command.consume_%s', $name), DaemonWorkerCommand::class)
                ->addArgument($definition)
                ->addArgument(sprintf('burrow:consume:%s', $name))
                ->addTag('console.command');
        }

        $this->loadBurrowCommands($container);
    }

    private function loadBurrowCommands(ContainerBuilder $container)
    {
        $container
            ->register('evaneos_burrow.command.declare_queue', DeclareQueueCommand::class)
            ->addArgument(new Reference('evaneos_burrow.default_driver'))
            ->addMethodCall('setName', ['burrow:command:declare_queue'])
            ->addTag('console.command');
        $container
            ->register('evaneos_burrow.command.delete_queue', DeleteQueueCommand::class)
            ->addArgument(new Reference('evaneos_burrow.default_driver'))
            ->addMethodCall('setName', ['burrow:command:delete_queue'])
            ->addTag('console.command');
        $container
            ->register('evaneos_burrow.command.declare_exchange', DeclareExchangeCommand::class)
            ->addArgument(new Reference('evaneos_burrow.default_driver'))
            ->addMethodCall('setName', ['burrow:command:declare_exchange'])
            ->addTag('console.command');
        $container
            ->register('evaneos_burrow.command.delete_exchange', DeleteExchangeCommand::class)
            ->addArgument(new Reference('evaneos_burrow.default_driver'))
            ->addMethodCall('setName', ['burrow:command:delete_exchange'])
            ->addTag('console.command');
        $container
            ->register('evaneos_burrow.command.bind', BindCommand::class)
            ->addArgument(new Reference('evaneos_burrow.default_driver'))
            ->addMethodCall('setName', ['burrow:command:bind'])
            ->addTag('console.command');
    }
}
