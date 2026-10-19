<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle;

/**
 * Events for the Omnichannel Messaging plugin.
 */
final class OmnichannelEvents
{
    /**
     * Fired when an outbound message is about to be sent.
     * Listeners can modify payload or cancel.
     */
    public const MESSAGE_PRE_SEND = 'mautic.omnichannel.message_pre_send';

    /**
     * Fired after an outbound message was successfully sent.
     */
    public const MESSAGE_POST_SEND = 'mautic.omnichannel.message_post_send';

    /**
     * Fired when an inbound message/webhook is received.
     */
    public const MESSAGE_INBOUND = 'mautic.omnichannel.message_inbound';

    /**
     * Fired when a template is submitted / status changes.
     */
    public const TEMPLATE_STATUS_CHANGED = 'mautic.omnichannel.template_status_changed';

    /**
     * Fired when conversation window state changes (opened / closed).
     */
    public const WINDOW_STATE_CHANGED = 'mautic.omnichannel.window_state_changed';
}
