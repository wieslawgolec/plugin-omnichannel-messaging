<?php

declare(strict_types=1);

return [
    'name'        => 'Omnichannel Messaging',
    'description' => 'Messaging channels (WhatsApp, Telegram, WeCom, WeChat, Discord) with templates, conversations, session windows, inbound webhooks and campaign actions for Mautic 7.2+',
    'version'     => '1.2.0',
    'author'      => 'Wieslaw Golec',

    'routes' => [
        'main' => [
            'mautic_omnichannel_templates_index' => [
                'path'       => '/omnichannel/templates/{page}',
                'controller' => 'MauticPlugin\MauticOmnichannelMessagingBundle\Controller\TemplateController::indexAction',
                'defaults'   => ['page' => 1],
            ],
            'mautic_omnichannel_templates_action' => [
                'path'       => '/omnichannel/templates/{objectAction}/{objectId}',
                'controller' => 'MauticPlugin\MauticOmnichannelMessagingBundle\Controller\TemplateController::executeAction',
                'defaults'   => ['objectId' => 0],
            ],
            'mautic_omnichannel_conversations_index' => [
                'path'       => '/omnichannel/conversations/{page}',
                'controller' => 'MauticPlugin\MauticOmnichannelMessagingBundle\Controller\ConversationController::indexAction',
                'defaults'   => ['page' => 1],
            ],
            'mautic_omnichannel_conversations_view' => [
                'path'       => '/omnichannel/conversations/view/{objectId}',
                'controller' => 'MauticPlugin\MauticOmnichannelMessagingBundle\Controller\ConversationController::viewAction',
            ],
            'mautic_omnichannel_config_index' => [
                'path'       => '/omnichannel/config',
                'controller' => 'MauticPlugin\MauticOmnichannelMessagingBundle\Controller\ConfigController::indexAction',
            ],
            'mautic_omnichannel_config_edit' => [
                'path'       => '/omnichannel/config/{channel}',
                'controller' => 'MauticPlugin\MauticOmnichannelMessagingBundle\Controller\ConfigController::editAction',
            ],
        ],
        'public' => [
            'mautic_omnichannel_webhook' => [
                'path'       => '/omnichannel/webhook/{channel}',
                'controller' => 'MauticPlugin\MauticOmnichannelMessagingBundle\Controller\WebhookController::handleAction',
                'method'     => ['GET', 'POST'],
            ],
        ],
    ],

    'menu' => [
        'main' => [
            'mautic.omnichannel.templates' => [
                'route'    => 'mautic_omnichannel_templates_index',
                'parent'   => 'mautic.core.channels',
                'access'   => 'omnichannel:templates:view',
                'priority' => 80,
            ],
            'mautic.omnichannel.conversations' => [
                'route'    => 'mautic_omnichannel_conversations_index',
                'parent'   => 'mautic.core.channels',
                'access'   => 'omnichannel:conversations:view',
                'priority' => 75,
            ],
            'mautic.omnichannel.config' => [
                'route'    => 'mautic_omnichannel_config_index',
                'parent'   => 'mautic.core.channels',
                'access'   => 'omnichannel:config:edit',
                'priority' => 70,
            ],
        ],
    ],

    'services' => [
        'helpers' => [
            'mautic.omnichannel.channel_registry' => [
                'class' => \MauticPlugin\MauticOmnichannelMessagingBundle\Helper\ChannelRegistry::class,
            ],
            'mautic.omnichannel.window_manager' => [
                'class' => \MauticPlugin\MauticOmnichannelMessagingBundle\Helper\WindowManager::class,
            ],
            'mautic.omnichannel.message_sender' => [
                'class'     => \MauticPlugin\MauticOmnichannelMessagingBundle\Helper\MessageSender::class,
                'arguments' => [
                    'mautic.omnichannel.channel_registry',
                    'mautic.omnichannel.window_manager',
                ],
            ],
        ],
        'models' => [
            'mautic.omnichannel.model.template' => [
                'class' => \MauticPlugin\MauticOmnichannelMessagingBundle\Model\TemplateModel::class,
            ],
            'mautic.omnichannel.model.conversation' => [
                'class'     => \MauticPlugin\MauticOmnichannelMessagingBundle\Model\ConversationModel::class,
                'arguments' => [
                    'mautic.omnichannel.channel_registry',
                    'mautic.omnichannel.window_manager',
                ],
            ],
        ],
        'events' => [
            'mautic.omnichannel.campaign_subscriber' => [
                'class'     => \MauticPlugin\MauticOmnichannelMessagingBundle\EventListener\CampaignSubscriber::class,
                'arguments' => [
                    'mautic.omnichannel.message_sender',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.omnichannel.send' => [
                'class'     => \MauticPlugin\MauticOmnichannelMessagingBundle\Form\Type\OmnichannelSendType::class,
                'arguments' => ['mautic.omnichannel.channel_registry'],
            ],
            'mautic.form.type.omnichannel.template' => [
                'class'     => \MauticPlugin\MauticOmnichannelMessagingBundle\Form\Type\MessageTemplateType::class,
                'arguments' => ['mautic.omnichannel.channel_registry'],
            ],
            'mautic.form.type.omnichannel.channel_config' => [
                'class' => \MauticPlugin\MauticOmnichannelMessagingBundle\Form\Type\ChannelConfigType::class,
            ],
        ],
        'integrations' => [
            'mautic.integration.omnichannel' => [
                'class' => \MauticPlugin\MauticOmnichannelMessagingBundle\Integration\OmnichannelIntegration::class,
            ],
        ],
    ],


        'permissions' => [
            'mautic.omnichannel.permissions' => [
                'class'     => \MauticPlugin\MauticOmnichannelMessagingBundle\Security\Permissions\OmnichannelPermissions::class,
                'arguments' => ['mautic.helper.core_parameters'],
            ],
        ],

    'parameters' => [],
];
