<?php

namespace M6Web\Bundle\StatsdPrometheusBundle;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class M6WebStatsdPrometheusBundle extends Bundle
{
    /**
     * Returns the bundle's container extension.
     *
     * With our `M6Web` namespace, symfony's `Container::underscore()` gives aliases starting with `m6_web`
     * but we always used `m6web`, thus the consistency check here is removed.
     *
     * @return ExtensionInterface|null The container extension
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $extension = $this->createContainerExtension();

            if (null !== $extension) {
                $this->extension = $extension;
            } else {
                $this->extension = false;
            }
        }

        if ($this->extension) {
            return $this->extension;
        }
    }
}
