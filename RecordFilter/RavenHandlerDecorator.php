<?php
declare(strict_types=1);

namespace Xymanek\SentryBundle\RecordFilter;

use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\RavenHandler;

final class RavenHandlerDecorator extends RavenHandler
{
    /**
     * @var RavenHandler
     */
    private $handler;

    /**
     * @var SentryRecordFilterInterface[]
     */
    private $filters = [];

    /**
     * @inheritDoc
     */
    public function __construct (RavenHandler $handler)
    {
        $this->handler = $handler;
    }

    private function isReported (array $record) : bool
    {
        foreach ($this->filters as $filter) {
            if (!$filter->isReported($record)) {
                return false;
            }
        }

        return true;
    }
    
    /**
     * @inheritDoc
     */
    public function handle (array $record)
    {
        if (!$this->isReported($record)) {
            return false;
        }

        return $this->handler->handle($record);
    }

    /**
     * @inheritDoc
     */
    public function handleBatch (array $records)
    {
        $records = array_filter($records, [$this, 'isReported']);

        $this->handler->handleBatch($records);
    }

    public function addFilter (SentryRecordFilterInterface $filterer)
    {
        $this->filters[] = $filterer;
    }

    /*
     * Proxy methods
     */

    /**
     * @inheritDoc
     */
    public function isHandling (array $record)
    {
        return $this->handler->isHandling($record);
    }

    /**
     * @inheritDoc
     */
    public function close ()
    {
        $this->handler->close();
    }

    /**
     * @inheritDoc
     */
    public function pushProcessor ($callback)
    {
        return $this->handler->pushProcessor($callback);
    }

    /**
     * @inheritDoc
     */
    public function popProcessor ()
    {
        return $this->handler->popProcessor();
    }

    /**
     * @inheritDoc
     */
    public function setFormatter (FormatterInterface $formatter)
    {
        return $this->handler->setFormatter($formatter);
    }

    /**
     * @inheritDoc
     */
    public function getFormatter ()
    {
        return $this->handler->getFormatter();
    }

    /**
     * @inheritDoc
     */
    public function setLevel ($level)
    {
        return $this->handler->setLevel($level);
    }

    /**
     * @inheritDoc
     */
    public function getLevel ()
    {
        return $this->handler->getLevel();
    }

    /**
     * @inheritDoc
     */
    public function setBubble ($bubble)
    {
        return $this->handler->setBubble($bubble);
    }

    /**
     * @inheritDoc
     */
    public function getBubble ()
    {
        return $this->handler->getBubble();
    }

    /**
     * @inheritDoc
     */
    public function setBatchFormatter (FormatterInterface $formatter)
    {
        $this->handler->setBatchFormatter($formatter);
    }

    /**
     * @inheritDoc
     */
    public function getBatchFormatter ()
    {
        return $this->handler->getBatchFormatter();
    }

    /**
     * @inheritDoc
     */
    public function setRelease ($value)
    {
        return $this->handler->setRelease($value);
    }
}