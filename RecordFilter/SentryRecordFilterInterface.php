<?php
declare(strict_types=1);

namespace Xymanek\SentryBundle\RecordFilter;

interface SentryRecordFilterInterface
{
    public function isReported (array $record): bool;
}