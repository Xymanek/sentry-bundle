<?php
declare(strict_types=1);

namespace Xymanek\SentryBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;
use Xymanek\SentryBundle\DependencyInjection\Compiler\AddRecordFiltersCompilerPass;
use Xymanek\SentryBundle\DependencyInjection\XymanekSentryExtension;
use Xymanek\SentryBundle\RecordFilter\IgnoredExceptionsFilter;
use Xymanek\SentryBundle\RecordFilter\RavenHandlerDecorator;
use Xymanek\SentryBundle\RecordFilter\SentryRecordFilterInterface;

class XymanekSentryExtensionTest extends AbstractExtensionTestCase
{
    /**
     * Return an array of container extensions you need to be registered for each test (usually just the container
     * extension you are testing.
     *
     * @return ExtensionInterface[]
     */
    protected function getContainerExtensions ()
    {
        return [new XymanekSentryExtension()];
    }

    public function testRegisteredForAutoconfiguration ()
    {
        $this->load();

        $expectedDefinition = (new ChildDefinition(''))->addTag(AddRecordFiltersCompilerPass::TAG);
        $registered = $this->container->getAutoconfiguredInstanceof();

        $this->assertArrayHasKey(SentryRecordFilterInterface::class, $registered);
        $this->assertEquals($expectedDefinition, $registered[SentryRecordFilterInterface::class]);
    }

    public function testNoDecoratedHandlersDefault ()
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('xymanek_sentry.handlers_to_decorate', []);
    }

    public function testHandlerDecorated ()
    {
        $this->load([
            'filters' => [
                'sentry' => [
                    'filter_exceptions' => []
                ]
            ]
        ]);

        $serviceId = AddRecordFiltersCompilerPass::decoratorServiceId('sentry');
        $this->assertContainerBuilderNotHasService($serviceId . '.exception_filter');
        $this->assertContainerBuilderHasParameter('xymanek_sentry.handlers_to_decorate', ['sentry']);

        $expected = (new Definition())
            ->setClass(RavenHandlerDecorator::class)
            ->setDecoratedService('monolog.handler.sentry')
            ->setArguments([new Reference($serviceId . '.inner')])
            ->setPublic(false);

        $this->assertEquals($expected, $this->container->getDefinition($serviceId));
    }

    public function testExceptionFilterAdded ()
    {
        $this->load([
            'filters' => [
                'sentry' => [
                    'filter_exceptions' => [
                        'SomeClass'
                    ]
                ]
            ]
        ]);

        $handlerId = AddRecordFiltersCompilerPass::decoratorServiceId('sentry');
        $this->assertContainerBuilderHasParameter('xymanek_sentry.handlers_to_decorate', ['sentry']);
        $this->assertContainerBuilderHasService($handlerId);

        $expected = (new Definition())
            ->setClass(IgnoredExceptionsFilter::class)
            ->setArguments([['SomeClass']])
            ->addTag(AddRecordFiltersCompilerPass::TAG, ['handler' => 'sentry'])
            ->setPublic(false);

        $this->assertEquals($expected, $this->container->getDefinition($handlerId . '.exception_filter'));
    }
}
