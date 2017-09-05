<?php
namespace Xymanek\SentryBundle\ContextProvider;

interface ExtraContextProviderInterface
{
    public function getExtraData (): array;
}