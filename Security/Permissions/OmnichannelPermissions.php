<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Role permissions for Omnichannel Messaging.
 *
 * Permission base: omnichannel
 * Examples:
 *   omnichannel:templates:view
 *   omnichannel:templates:create
 *   omnichannel:templates:edit
 *   omnichannel:templates:delete
 *   omnichannel:conversations:view
 *   omnichannel:conversations:edit
 *   omnichannel:config:edit
 *   omnichannel:messages:send
 */
final class OmnichannelPermissions extends AbstractPermissions
{
    /**
     * @param mixed[] $params
     */
    public function __construct(array $params)
    {
        parent::__construct($params);
        $this->addStandardPermissions('templates');
        $this->addStandardPermissions('conversations');
        $this->addStandardPermissions('config');
        $this->addStandardPermissions('messages');
    }

    public function getName(): string
    {
        return 'omnichannel';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields('omnichannel', 'templates', $builder, $data);
        $this->addStandardFormFields('omnichannel', 'conversations', $builder, $data);
        $this->addStandardFormFields('omnichannel', 'config', $builder, $data);
        $this->addStandardFormFields('omnichannel', 'messages', $builder, $data);
    }
}
