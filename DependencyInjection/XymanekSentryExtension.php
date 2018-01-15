<?php
namespace Xymanek\SentryBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Xymanek\SentryBundle\ContextProvider\ExtraContextProviderInterface;
use Xymanek\SentryBundle\ContextProvider\TagsProviderInterface;
use Xymanek\SentryBundle\ContextProvider\UserContextProviderInterface;
use Xymanek\SentryBundle\DependencyInjection\Compiler\AddRecordFiltersCompilerPass;
use Xymanek\SentryBundle\RecordFilter\IgnoredExceptionsFilter;
use Xymanek\SentryBundle\RecordFilter\RavenHandlerDecorator;
use Xymanek\SentryBundle\RecordFilter\SentryRecordFilterInterface;

class XymanekSentryExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load (array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        // Client
        $container->findDefinition('xymanek_sentry.client')
            ->replaceArgument(0, $config['dsn'])
            ->replaceArgument(1, $config['options']);

        // Context config
        foreach ($config['context_providers'] as $provider => $isEnabled) {
            if (!$isEnabled) {
                $container->removeDefinition('xymanek_sentry.context_provider.' . $provider);
            }
        }

        // Context tags
        $container->registerForAutoconfiguration(UserContextProviderInterface::class)
            ->addTag('xymanek_sentry.context_provider.user');
        $container->registerForAutoconfiguration(ExtraContextProviderInterface::class)
            ->addTag('xymanek_sentry.context_provider.extra');
        $container->registerForAutoconfiguration(TagsProviderInterface::class)
            ->addTag('xymanek_sentry.context_provider.tags');

        // Filters
        $handlersToDecorate = array_keys($config['filters']);
        $container->setParameter('xymanek_sentry.handlers_to_decorate', $handlersToDecorate);

        foreach ($config['filters'] as $handler => $options) {
            $serviceId = AddRecordFiltersCompilerPass::decoratorServiceId($handler);

            $container->register($serviceId, RavenHandlerDecorator::class)
                ->setDecoratedService('monolog.handler.' . $handler)
                ->setArguments([new Reference($serviceId . '.inner')])
                ->setPublic(false);

            if (count($options['filter_exceptions']) > 0) {
                $container->register($serviceId . '.exception_filter', IgnoredExceptionsFilter::class)
                    ->setArguments([$options['filter_exceptions']])
                    ->addTag(AddRecordFiltersCompilerPass::TAG, ['handler' => $handler])
                    ->setPublic(false);
            }
        }

        $container->registerForAutoconfiguration(SentryRecordFilterInterface::class)
            ->addTag(AddRecordFiltersCompilerPass::TAG);
    }
}
