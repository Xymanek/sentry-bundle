<?php
namespace Xymanek\SentryBundle\ContextProvider;

use Symfony\Component\HttpFoundation\RequestStack;

class SessionContextProvider implements ExtraContextProviderInterface
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var string
     */
    protected $propertyName;

    public function __construct (RequestStack $requestStack, string $propertyName = 'session_data')
    {
        $this->requestStack = $requestStack;
        $this->propertyName = $propertyName;
    }

    public function getExtraData (): array
    {
        $session = $this->requestStack->getMasterRequest()->getSession();

        if ($session && $session->isStarted()) {
            return [
                $this->propertyName => $session->all(),
            ];
        }

        return [];
    }
}