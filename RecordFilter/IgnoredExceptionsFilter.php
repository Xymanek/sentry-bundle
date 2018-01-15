<?php
declare(strict_types=1);

namespace Xymanek\SentryBundle\RecordFilter;

class IgnoredExceptionsFilter implements SentryRecordFilterInterface
{
    /**
     * @var array
     */
    private $ignoredExceptions;

    public function __construct (array $ignoredExceptions)
    {
        $this->ignoredExceptions = $ignoredExceptions;
    }

    public function isReported (array $record): bool
    {
        if (isset($record['context']['exception'])) {
            $exception = $record['context']['exception'];

            foreach ($this->ignoredExceptions as $ignoredException) {
                if ($exception instanceof $ignoredException) {
                    return false;
                }
            }
        }

        return true;
    }
}