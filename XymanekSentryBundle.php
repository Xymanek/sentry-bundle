<?php
namespace Xymanek\SentryBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Xymanek\SentryBundle\DependencyInjection\Compiler\RegisterContextProvidersPass;

class XymanekSentryBundle extends Bundle
{
    public function build (ContainerBuilder $container)
    {
        $container->addCompilerPass(new RegisterContextProvidersPass());
    }
}
