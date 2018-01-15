<?php
declare(strict_types=1);

namespace Xymanek\SentryBundle\Tests\RecordFilterer;

use Cake\Chronos\MutableDateTime;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\RavenHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Xymanek\SentryBundle\RecordFilter\RavenHandlerDecorator;
use Xymanek\SentryBundle\RecordFilter\SentryRecordFilterInterface;

class RavenHandlerDecoratorTest extends TestCase
{
    public function testNoFilters ()
    {
        $record = [
            'message' => 'Oops!',
            'context' => ['exception' => new RuntimeException()],
            'level' => Logger::CRITICAL,
            'level_name' => 'CRITICAL',
            'channel' => 'app',
            'datetime' => MutableDateTime::now(),
            'extra' => [],
        ];

        $handler = $this->createMock(RavenHandler::class);
        $handler->expects(self::once())
            ->method('handle')
            ->with($record)
            ->willReturn(false);

        $decorator = new RavenHandlerDecorator($handler);

        // Result from handler is passed
        self::assertFalse($decorator->handle($record));
    }

    public function testAllDeny ()
    {
        $record = [
            'message' => 'Oops!',
            'context' => ['exception' => new RuntimeException()],
            'level' => Logger::CRITICAL,
            'level_name' => 'CRITICAL',
            'channel' => 'app',
            'datetime' => MutableDateTime::now(),
            'extra' => [],
        ];

        $handler = $this->createMock(RavenHandler::class);
        $handler->expects(self::never())->method('handle');

        $decorator = new RavenHandlerDecorator($handler);

        $decorator->addFilter($this->createMockFilter(true, $record, false));
        $decorator->addFilter($this->createMockFilter(false));

        self::assertFalse($decorator->handle($record));
    }

    public function testLastDeny ()
    {
        $record = [
            'message' => 'Oops!',
            'context' => ['exception' => new RuntimeException()],
            'level' => Logger::CRITICAL,
            'level_name' => 'CRITICAL',
            'channel' => 'app',
            'datetime' => MutableDateTime::now(),
            'extra' => [],
        ];

        $handler = $this->createMock(RavenHandler::class);
        $handler->expects(self::never())->method('handle');

        $decorator = new RavenHandlerDecorator($handler);

        $decorator->addFilter($this->createMockFilter(true, $record, true));
        $decorator->addFilter($this->createMockFilter(true, $record, false));

        self::assertFalse($decorator->handle($record));
    }

    public function testNoDeny ()
    {
        $record = [
            'message' => 'Oops!',
            'context' => ['exception' => new RuntimeException()],
            'level' => Logger::CRITICAL,
            'level_name' => 'CRITICAL',
            'channel' => 'app',
            'datetime' => MutableDateTime::now(),
            'extra' => [],
        ];

        $handler = $this->createMock(RavenHandler::class);
        $handler->expects(self::once())
            ->method('handle')
            ->with($record)
            ->willReturn(false);

        $decorator = new RavenHandlerDecorator($handler);

        $decorator->addFilter($this->createMockFilter(true, $record, true));
        $decorator->addFilter($this->createMockFilter(true, $record, true));

        self::assertFalse($decorator->handle($record));
    }

    private function createMockFilter (bool $shouldBeInvoked, array $record = null, bool $result = null)
    {
        $filter = $this->createMock(SentryRecordFilterInterface::class);

        if ($shouldBeInvoked) {
            $filter->expects(self::once())
                ->method('isReported')
                ->with($record)
                ->willReturn($result);
        } else {
            $filter->expects(self::never())->method('isReported');
        }

        return $filter;
    }

    public function testHandleBatch ()
    {
        $record1 = [
            'message' => 'Oops!',
            'context' => ['exception' => new RuntimeException()],
            'level' => Logger::CRITICAL,
            'level_name' => 'CRITICAL',
            'channel' => 'app',
            'datetime' => MutableDateTime::now(),
            'extra' => [],
        ];
        $record2 = [
            'message' => 'Hi!',
            'context' => ['foo' => 'bar'],
            'level' => Logger::INFO,
            'level_name' => 'INFO',
            'channel' => 'app',
            'datetime' => MutableDateTime::now(),
            'extra' => [],
        ];

        $handler = $this->createMock(RavenHandler::class);
        $handler
            ->expects(self::once())
            ->method('handleBatch')
            ->with([1 => $record2]);

        $filter = $this->createMock(SentryRecordFilterInterface::class);
        $decorator = new RavenHandlerDecorator($handler);
        $decorator->addFilter($filter);

        $filter->expects(self::exactly(2))
            ->method('isReported')
            ->withConsecutive([$record1], [$record2])
            ->willReturn(false, true);

        $decorator->handleBatch([$record1, $record2]);
    }

    /**
     * @dataProvider proxyMethodProvider
     *
     * @param string $method
     * @param null   $return
     * @param array  $arguments
     */
    public function testProxyMethods (string $method, $return = null, array $arguments = [])
    {
        $handler = $this->createMock(RavenHandler::class);

        if ($return instanceof \stdClass) {
            $return = $handler;
        }

        $handler->expects(self::once())
            ->method($method)
            ->with(...$arguments)
            ->willReturn($return);

        self::assertSame($return, $handler->{$method}(...$arguments));
    }

    public function proxyMethodProvider ()
    {
        $record = [
            'message' => 'Oops!',
            'context' => ['exception' => new RuntimeException()],
            'level' => Logger::CRITICAL,
            'level_name' => 'CRITICAL',
            'channel' => 'app',
            'datetime' => MutableDateTime::now(),
            'extra' => [],
        ];

        $processor = function (array $record) {
            return $record;
        };

        return [
            ['isHandling', true, [$record]],
            ['close'],
            ['pushProcessor', new \stdClass(), [$processor]],
            ['popProcessor', $processor],
            ['setFormatter', new \stdClass(), [$this->createMock(FormatterInterface::class)]],
            ['getFormatter', $this->createMock(FormatterInterface::class)],
            ['setLevel', new \stdClass(), [Logger::WARNING]],
            ['getLevel', Logger::WARNING],
            ['setBubble', new \stdClass(), [true]],
            ['getBubble', true],
            ['setBatchFormatter', null, [$this->createMock(FormatterInterface::class)]],
            ['getBatchFormatter', $this->createMock(FormatterInterface::class)],
            ['setRelease', new \stdClass(), ['1.1.1']],
        ];
    }
}
