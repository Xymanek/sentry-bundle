<?php
namespace Xymanek\SentryBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterContextProvidersPass implements CompilerPassInterface
{
    public function process (ContainerBuilder $container)
    {
        if (!$container->has('xymanek_sentry.context_provider.chain')) {
            return;
        }

        $chainDefinition = $container->findDefinition('xymanek_sentry.context_provider.chain');

        /*
         * User context
         */
        $taggedServices = $container->findTaggedServiceIds('xymanek_sentry.context_provider.user');

        foreach ($taggedServices as $id => $tags) {
            $chainDefinition->addMethodCall('addUserContextProvider', [new Reference($id)]);
        }

        /*
         * Extra context
         */
        $taggedServices = $container->findTaggedServiceIds('xymanek_sentry.context_provider.extra');

        foreach ($taggedServices as $id => $tags) {
            $chainDefinition->addMethodCall('addExtraContextProvider', [new Reference($id)]);
        }

        /*
         * User context
         */
        $taggedServices = $container->findTaggedServiceIds('xymanek_sentry.context_provider.tags');

        foreach ($taggedServices as $id => $tags) {
            $chainDefinition->addMethodCall('addTagsProvider', [new Reference($id)]);
        }
    }
}