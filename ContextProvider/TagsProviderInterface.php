<?php
namespace Xymanek\SentryBundle\ContextProvider;

interface TagsProviderInterface
{
    public function getSentryTags (): array; // FixMe: better naming
}