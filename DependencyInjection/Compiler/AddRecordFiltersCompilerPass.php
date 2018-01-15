<?php
declare(strict_types=1);

namespace Xymanek\SentryBundle\DependencyInjection\Compiler;

use OutOfRangeException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

class AddRecordFiltersCompilerPass implements CompilerPassInterface
{
    const TAG = 'xymanek_sentry.record_filter';

    public static function decoratorServiceId (string $handler)
    {
        return 'xymanek_sentry.decorated_handler.' . $handler;
    }

    public function process (ContainerBuilder $container)
    {
        try {
            $handlersToDecorate = $container->getParameter('xymanek_sentry.handlers_to_decorate');
        } catch (InvalidArgumentException $e) {
            $handlersToDecorate = [];
        }

        $taggedServices = $container->findTaggedServiceIds(self::TAG, true);

        /** @var \Symfony\Component\DependencyInjection\Definition[] $handlerDefinitions */
        $handlerDefinitions = [];

        foreach ($handlersToDecorate as $handler) {
            $serviceId = self::decoratorServiceId($handler);

            if ($container->has($serviceId)) {
                $handlerDefinitions[$handler] = $container->findDefinition($serviceId);
            }
        }

        // In case something was removed
        $handlersToDecorate = array_keys($handlerDefinitions);

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                if (isset($attributes['handler'])) {
                    $handler = (string) $attributes['handler'];

                    if (!isset($handlerDefinitions[$handler])) {
                        throw new OutOfRangeException(sprintf(
                            'Service %s was tagged as record filter for handler %s which does not exist or is not decorated',
                            $id, $handler
                        ));
                    }

                    $attachToHandlers = [$handler];
                } else {
                    $attachToHandlers = $handlersToDecorate;
                }

                foreach ($attachToHandlers as $handler) {
                    $handlerDefinitions[$handler]->addMethodCall('addFilter', [new Reference($id)]);
                }
            }
        }
    }
}
