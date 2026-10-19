<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

final class MauticOmnichannelMessagingExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Services are primarily declared in Config/config.php (Mautic style).
        // This extension exists so the bundle is properly registered.
    }
}
