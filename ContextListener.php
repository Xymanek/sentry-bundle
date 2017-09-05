<?php
namespace Xymanek\SentryBundle;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Xymanek\SentryBundle\ContextProvider\ChainContextProvider;

class ContextListener
{
    /**
     * @var \Raven_Client
     */
    protected $ravenClient;

    /**
     * @var ChainContextProvider
     */
    protected $contextProvider;

    public function __construct (\Raven_Client $ravenClient, ChainContextProvider $contextProvider)
    {
        $this->ravenClient = $ravenClient;
        $this->contextProvider = $contextProvider;
    }

    public function onKernelRequest (GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $userData = $this->contextProvider->getUserData();
        $this->ravenClient->user_context($userData === [] ? null : $userData);

        $this->ravenClient->extra_context($this->contextProvider->getExtraData());

        $this->ravenClient->tags_context($this->contextProvider->getSentryTags());
    }
}