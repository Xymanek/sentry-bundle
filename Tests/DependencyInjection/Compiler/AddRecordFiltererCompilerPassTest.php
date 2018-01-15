<?php
declare(strict_types=1);

namespace Xymanek\SentryBundle\Tests\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Monolog\Handler\RavenHandler;
use OutOfRangeException;
use Raven_Client;
use Symfony\Component\DependencyInjection\Compiler\RemoveUnusedDefinitionsPass;
use Symfony\Component\DependencyInjection\Compiler\RepeatedPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Xymanek\SentryBundle\DependencyInjection\Compiler\AddRecordFiltersCompilerPass;
use Xymanek\SentryBundle\RecordFilter\RavenHandlerDecorator;
use Xymanek\SentryBundle\RecordFilter\SentryRecordFilterInterface;

class AddRecordFiltererCompilerPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass (ContainerBuilder $container)
    {
        $container->addCompilerPass(new AddRecordFiltersCompilerPass());
    }

    public function testDecorationServiceId ()
    {
        self::assertEquals(
            'xymanek_sentry.decorated_handler.sentry',
            AddRecordFiltersCompilerPass::decoratorServiceId('sentry')
        );
    }

    public function testNothingDecoratedNothingTagged ()
    {
        $this->setParameter('xymanek_sentry.handlers_to_decorate', []);

        $this->registerService('raven_client', Raven_Client::class);
        $this->registerService('monolog.handler.sentry', RavenHandler::class)
            ->setArguments([new Reference('raven_client')])
            ->setPublic(true);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'monolog.handler.sentry', 0, new Reference('raven_client')
        );
    }

    public function testNothingDecoratedAndNonSpecificTag ()
    {
        $this->setParameter('xymanek_sentry.handlers_to_decorate', []);
        $this->container->getCompilerPassConfig()->setRemovingPasses([
            new RepeatedPass([new RemoveUnusedDefinitionsPass()])
        ]);

        $this->registerService('raven_client', Raven_Client::class);
        $this->registerService('monolog.handler.sentry', RavenHandler::class)
            ->setArguments([new Reference('raven_client')])
            ->setPublic(true);
        $this->registerService('record_filterer', NotUsableSentryRecordFilter::class)
            ->addTag('xymanek_sentry.record_filter')
            ->setPublic(false);

        $this->compile();

        // Cleared as useless private service
        $this->assertContainerBuilderNotHasService('record_filterer');

        // Handler not changed at all
        $expected = (new Definition(RavenHandler::class, [new Reference('raven_client')]))->setPublic(true);
        self::assertEquals($expected, $this->container->getDefinition('monolog.handler.sentry'));
    }

    public function testDecoratorRemovedAndNonSpecificTag ()
    {
        $this->setParameter('xymanek_sentry.handlers_to_decorate', ['sentry']);
        $this->container->getCompilerPassConfig()->setRemovingPasses([
            new RepeatedPass([new RemoveUnusedDefinitionsPass()])
        ]);

        $this->registerService('raven_client', Raven_Client::class);
        $this->registerService('monolog.handler.sentry', RavenHandler::class)
            ->setArguments([new Reference('raven_client')])
            ->setPublic(true);
        $this->registerService('record_filterer', NotUsableSentryRecordFilter::class)
            ->addTag('xymanek_sentry.record_filter')
            ->setPublic(false);

        $this->compile();

        // Cleared as useless private service
        $this->assertContainerBuilderNotHasService('record_filterer');

        // Handler not changed at all
        $expected = (new Definition(RavenHandler::class, [new Reference('raven_client')]))->setPublic(true);
        self::assertEquals($expected, $this->container->getDefinition('monolog.handler.sentry'));
    }

    public function testNothingDecoratedAndSpecificTag ()
    {
        $this->expectException(OutOfRangeException::class);
        $this->expectExceptionMessage(sprintf(
            'Service %s was tagged as record filter for handler %s which does not exist or is not decorated',
            'record_filterer', 'sentry'
        ));

        $this->setParameter('xymanek_sentry.handlers_to_decorate', []);

        $this->registerService('raven_client', Raven_Client::class);
        $this->registerService('monolog.handler.sentry', RavenHandler::class)
            ->setArguments([new Reference('raven_client')])
            ->setPublic(true);
        $this->registerService('record_filterer', NotUsableSentryRecordFilter::class)
            ->addTag('xymanek_sentry.record_filter', ['handler' => 'sentry'])
            ->setPublic(false);

        $this->compile();
    }

    public function testDecoratorRemovedAndSpecificTag ()
    {
        $this->expectException(OutOfRangeException::class);
        $this->expectExceptionMessage(sprintf(
            'Service %s was tagged as record filter for handler %s which does not exist or is not decorated',
            'record_filterer', 'sentry'
        ));

        $this->setParameter('xymanek_sentry.handlers_to_decorate', ['sentry']);

        $this->registerService('raven_client', Raven_Client::class);
        $this->registerService('monolog.handler.sentry', RavenHandler::class)
            ->setArguments([new Reference('raven_client')]);
        $this->registerService('record_filterer', NotUsableSentryRecordFilter::class)
            ->addTag('xymanek_sentry.record_filter', ['handler' => 'sentry'])
            ->setPublic(false);

        $this->compile();
    }

    public function testOneHandlerDecoratedAndNonSpecificTag ()
    {
        $this->setParameter('xymanek_sentry.handlers_to_decorate', ['sentry']);

        $this->registerService('raven_client', Raven_Client::class);
        $this->registerService('monolog.handler.sentry', RavenHandler::class)
            ->setArguments([new Reference('raven_client')]);
        $this->registerService('xymanek_sentry.decorated_handler.sentry', RavenHandlerDecorator::class)
            ->setDecoratedService('monolog.handler.sentry')
            ->setArguments([new Reference('xymanek_sentry.decorated_handler.sentry.inner')])
            ->setPublic(false);
        $this->registerService('record_filterer', NotUsableSentryRecordFilter::class)
            ->addTag('xymanek_sentry.record_filter')
            ->setPublic(false);

        $this->compile();
        $calls = $this->container->getDefinition('xymanek_sentry.decorated_handler.sentry')->getMethodCalls();

        self::assertCount(1, $calls);
        self::assertEquals('addFilter', $calls[0][0]);

        $this->assertEquals(new Reference('record_filterer'), $calls[0][1][0]);
    }

    public function testOneHandlerDecoratedAndSpecificTag ()
    {
        $this->setParameter('xymanek_sentry.handlers_to_decorate', ['sentry']);

        $this->registerService('raven_client', Raven_Client::class);
        $this->registerService('monolog.handler.sentry', RavenHandler::class)
            ->setArguments([new Reference('raven_client')]);
        $this->registerService('xymanek_sentry.decorated_handler.sentry', RavenHandlerDecorator::class)
            ->setDecoratedService('monolog.handler.sentry')
            ->setArguments([new Reference('xymanek_sentry.decorated_handler.sentry.inner')])
            ->setPublic(false);
        $this->registerService('record_filterer', NotUsableSentryRecordFilter::class)
            ->addTag('xymanek_sentry.record_filter', ['handler' => 'sentry'])
            ->setPublic(false);

        $this->compile();
        $calls = $this->container->getDefinition('xymanek_sentry.decorated_handler.sentry')->getMethodCalls();

        self::assertCount(1, $calls);
        self::assertEquals('addFilter', $calls[0][0]);

        $this->assertEquals(new Reference('record_filterer'), $calls[0][1][0]);
    }

    public function testTwoHandlersDecoratedAndNonSpecificTag ()
    {
        $this->setParameter('xymanek_sentry.handlers_to_decorate', ['sentry1', 'sentry2']);

        $this->registerService('raven_client', Raven_Client::class);

        $this->registerService('monolog.handler.sentry1', RavenHandler::class)
            ->setArguments([new Reference('raven_client')]);
        $this->registerService('monolog.handler.sentry2', RavenHandler::class)
            ->setArguments([new Reference('raven_client')]);

        $this->registerService('xymanek_sentry.decorated_handler.sentry1', RavenHandlerDecorator::class)
            ->setDecoratedService('monolog.handler.sentry1')
            ->setArguments([new Reference('xymanek_sentry.decorated_handler.sentry1.inner')])
            ->setPublic(false);
        $this->registerService('xymanek_sentry.decorated_handler.sentry2', RavenHandlerDecorator::class)
            ->setDecoratedService('monolog.handler.sentry2')
            ->setArguments([new Reference('xymanek_sentry.decorated_handler.sentry2.inner')])
            ->setPublic(false);

        $this->registerService('record_filterer', NotUsableSentryRecordFilter::class)
            ->addTag('xymanek_sentry.record_filter')
            ->setPublic(false);

        $this->compile();

        $checkDefinition = function (Definition $definition) {
            $calls = $definition->getMethodCalls();
            self::assertCount(1, $calls);
            self::assertEquals('addFilter', $calls[0][0]);

            /** @var Reference $reference */
            $reference = $calls[0][1][0];
            self::assertEquals('record_filterer', (string) $reference);
        };

        $checkDefinition($this->container->getDefinition('xymanek_sentry.decorated_handler.sentry1'));
        $checkDefinition($this->container->getDefinition('xymanek_sentry.decorated_handler.sentry2'));
    }

    public function testTwoHandlersDecoratedAndSpecificTag ()
    {
        $this->setParameter('xymanek_sentry.handlers_to_decorate', ['sentry1', 'sentry2']);

        $this->registerService('raven_client', Raven_Client::class);

        $this->registerService('monolog.handler.sentry1', RavenHandler::class)
            ->setArguments([new Reference('raven_client')]);
        $this->registerService('monolog.handler.sentry2', RavenHandler::class)
            ->setArguments([new Reference('raven_client')]);

        $this->registerService('xymanek_sentry.decorated_handler.sentry1', RavenHandlerDecorator::class)
            ->setDecoratedService('monolog.handler.sentry1')
            ->setArguments([new Reference('xymanek_sentry.decorated_handler.sentry1.inner')])
            ->setPublic(false);
        $this->registerService('xymanek_sentry.decorated_handler.sentry2', RavenHandlerDecorator::class)
            ->setDecoratedService('monolog.handler.sentry2')
            ->setArguments([new Reference('xymanek_sentry.decorated_handler.sentry2.inner')])
            ->setPublic(false);

        $this->registerService('record_filterer', NotUsableSentryRecordFilter::class)
            ->addTag('xymanek_sentry.record_filter', ['handler' => 'sentry1'])
            ->setPublic(false);

        $this->compile();

        // Ignored handler
        self::assertCount(
            0,
            $this->container->getDefinition('xymanek_sentry.decorated_handler.sentry2')->getMethodCalls()
        );

        // Used handler
        $calls = $this->container->getDefinition('xymanek_sentry.decorated_handler.sentry1')->getMethodCalls();

        self::assertCount(1, $calls);
        self::assertEquals('addFilter', $calls[0][0]);

        $this->assertEquals(new Reference('record_filterer'), $calls[0][1][0]);
    }
}

class NotUsableSentryRecordFilter implements SentryRecordFilterInterface
{
    public function isReported (array $record): bool
    {
        throw new \LogicException('This should not be called');
    }
}