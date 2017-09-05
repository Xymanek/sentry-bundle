<?php
namespace Xymanek\SentryBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Xymanek\SentryBundle\ContextProvider\ExtraContextProviderInterface;
use Xymanek\SentryBundle\ContextProvider\TagsProviderInterface;
use Xymanek\SentryBundle\ContextProvider\UserContextProviderInterface;

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
    }
}
