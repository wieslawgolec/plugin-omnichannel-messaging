<?php

declare(strict_types=1);

return [
    'name'        => 'Omnichannel Messaging',
    'description' => 'Messaging channels (WhatsApp, Telegram, WeCom, WeChat, Discord) with templates, conversations, session windows and inbound webhooks',
    'version'     => '1.0.0',
    'author'      => 'Wieslaw Golec',

    'routes' => [
        'main' => [
            'mautic_omnichannel_templates_index' => [
                'path'       => '/omnichannel/templates/{page}',
                'controller' => 'MauticPlugin\MauticOmnichannelMessagingBundle\Controller\TemplateController::indexAction',
            ],
            'mautic_omnichannel_conversations_index' => [
                'path'       => '/omnichannel/conversations/{page}',
                'controller' => 'MauticPlugin\MauticOmnichannelMessagingBundle\Controller\ConversationController::indexAction',
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
                'checks'   => [],
            ],
            'mautic.omnichannel.conversations' => [
                'route'    => 'mautic_omnichannel_conversations_index',
                'parent'   => 'mautic.core.channels',
                'access'   => 'omnichannel:conversations:view',
                'priority' => 75,
            ],
        ],
    ],

    'services' => [
        'helpers' => [
            'mautic.omnichannel.channel_registry' => [
                'class' => MauticPlugin\MauticOmnichannelMessagingBundle\Helper\ChannelRegistry::class,
            ],
            'mautic.omnichannel.window_manager' => [
                'class' => MauticPlugin\MauticOmnichannelMessagingBundle\Helper\WindowManager::class,
            ],
            'mautic.omnichannel.message_sender' => [
                'class'     => MauticPlugin\MauticOmnichannelMessagingBundle\Helper\MessageSender::class,
                'arguments' => [
                    'mautic.omnichannel.channel_registry',
                    'mautic.omnichannel.window_manager',
                ],
            ],
        ],
        'events' => [
            'mautic.omnichannel.campaign_subscriber' => [
                'class'     => MauticPlugin\MauticOmnichannelMessagingBundle\EventListener\CampaignSubscriber::class,
                'arguments' => [
                    'mautic.omnichannel.message_sender',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.omnichannel.send' => [
                'class' => MauticPlugin\MauticOmnichannelMessagingBundle\Form\Type\OmnichannelSendType::class,
                'arguments' => [
                    'mautic.omnichannel.channel_registry',
                ],
            ],
        ],
    ],

    'parameters' => [
        // Placeholder for future global settings
    ],
];
