<?php
namespace Xymanek\SentryBundle\ContextProvider;

use Symfony\Component\HttpFoundation\RequestStack;

class ClientIpContextProvider implements UserContextProviderInterface
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var string
     */
    protected $propertyName;

    public function __construct (RequestStack $requestStack, string $propertyName = 'ip_address')
    {
        $this->requestStack = $requestStack;
        $this->propertyName = $propertyName;
    }

    public function getUserData (): array
    {
        return [
            $this->propertyName => $this->requestStack->getMasterRequest()->getClientIp(),
        ];
    }
}