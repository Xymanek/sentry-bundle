<?php
namespace Xymanek\SentryBundle\Tests;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Xymanek\SentryBundle\ContextListener;
use PHPUnit\Framework\TestCase;
use Xymanek\SentryBundle\ContextProvider\ChainContextProvider;

class ContextListenerTest extends TestCase
{
    public function testIgnoringSubRequests ()
    {
        $client = $this->createMock(\Raven_Client::class);
        $provider = $this->createMock(ChainContextProvider::class);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = $this->createMock(Request::class);

        $client->expects(self::never())->method(self::anything());
        $provider->expects(self::never())->method(self::anything());

        $listener = new ContextListener($client, $provider);
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST);

        $listener->onKernelRequest($event);

        self::assertFalse($event->hasResponse());
    }
}
