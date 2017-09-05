<?php
namespace Xymanek\SentryBundle\ContextProvider;

interface UserContextProviderInterface
{
    public function getUserData (): array;
}