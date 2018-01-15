<?php
declare(strict_types=1);

namespace Xymanek\SentryBundle\Tests\RecordFilterer;

use Cake\Chronos\MutableDateTime;
use LogicException;
use Monolog\Logger;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;
use Xymanek\SentryBundle\RecordFilter\IgnoredExceptionsFilter;

class IgnoredExceptionsFilterTest extends TestCase
{
    public function testNoExceptionInRecord ()
    {
        $filter = new IgnoredExceptionsFilter([Throwable::class]);
        $record = [
            'message' => 'Hi!',
            'context' => ['foo' => 'bar'],
            'level' => Logger::INFO,
            'level_name' => 'INFO',
            'channel' => 'app',
            'datetime' => MutableDateTime::now(),
            'extra' => [],
        ];

        self::assertTrue($filter->isReported($record));
    }

    public function testNonFilteredException ()
    {
        $filter = new IgnoredExceptionsFilter([LogicException::class]);
        $record = [
            'message' => 'Oops!',
            'context' => ['exception' => new RuntimeException()],
            'level' => Logger::CRITICAL,
            'level_name' => 'CRITICAL',
            'channel' => 'app',
            'datetime' => MutableDateTime::now(),
            'extra' => [],
        ];

        self::assertTrue($filter->isReported($record));
    }

    public function testFilteredException ()
    {
        $filter = new IgnoredExceptionsFilter([RuntimeException::class]);
        $record = [
            'message' => 'Oops!',
            'context' => ['exception' => new RuntimeException()],
            'level' => Logger::CRITICAL,
            'level_name' => 'CRITICAL',
            'channel' => 'app',
            'datetime' => MutableDateTime::now(),
            'extra' => [],
        ];

        self::assertFalse($filter->isReported($record));
    }

    public function testFilteredExceptionSubClass ()
    {
        $filter = new IgnoredExceptionsFilter([RuntimeException::class]);
        $record = [
            'message' => 'Oops!',
            'context' => ['exception' => new OutOfBoundsException()],
            'level' => Logger::CRITICAL,
            'level_name' => 'CRITICAL',
            'channel' => 'app',
            'datetime' => MutableDateTime::now(),
            'extra' => [],
        ];

        self::assertFalse($filter->isReported($record));
    }
}
