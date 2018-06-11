<?php

namespace M6Web\Bundle\StatsdPrometheusBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class M6WebStatsdPrometheusBundle extends Bundle
{
    /**
     * trick allowing bypassing the Bundle::getContainerExtension check on getAlias
     * not very clean, to investigate
     *
     * @return object DependencyInjection\M6WebStatsdExtension
     */
    public function getContainerExtension()
    {
        return new DependencyInjection\M6WebStatsdPrometheusExtension();
    }
}
