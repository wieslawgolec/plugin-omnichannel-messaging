# Mautic Omnichannel Messaging Bundle

Messaging channels for **Mautic 7.2+**.

Turns WhatsApp, Telegram, WeCom, WeChat Official Account and Discord into proper messaging channels with:

- Session / customer-care window handling
- Automatic free-form ↔ template fallback
- Inbound webhook parsing
- Campaign action (“Send Omnichannel Message”)
- Extensible channel registry

|  |  |
| --- | --- |
| **Version** | 1.0.0 |
| **PHP** | 8.2+ (CI: 8.2, 8.5, 8.6) |
| **Mautic** | 7.2+ |
| **License** | GPL-3.0-or-later |

---

## Table of contents

1. [Capability matrix](#capability-matrix)
2. [Install in Mautic](#install-in-mautic)
3. [Architecture](#architecture)
4. [Campaign action](#campaign-action)
5. [Session windows](#session-windows)
6. [Inbound webhooks](#inbound-webhooks)
7. [Development & tests](#development--tests)
8. [Support the project](#support-the-project)

---

## Capability matrix

| Channel | Free-form | Templates | Window | Inbound | Media | Campaign |
| --- | --- | --- | --- | --- | --- | --- |
| **WhatsApp** (Cloud API) | ✓ (inside window) | ✓ | 24 h | ✓ | ✓ | ✓ |
| **Telegram** | ✓ always | — | none | ✓ | ✓ | ✓ |
| **WeCom** (企业微信) | ✓ always | — | none | ✓ | ✓ | ✓ |
| **WeChat OA** | ✓ (inside window) | ✓ | 48 h | ✓ | ✓ | ✓ |
| **Discord** | ✓ always | — | none | — | ✓ | ✓ |

This plugin is complementary to [plugin-social](https://github.com/wieslawgolec/plugin-social).  
Social focuses on **broadcast / social publishing**.  
Omnichannel Messaging focuses on **conversational messaging** (windows, templates, inbound, conversation timeline).

---

## Install in Mautic

### Requirements

* Mautic **7.2+**
* PHP **8.2+** (8.5 / 8.6 supported)
* `curl` extension recommended for live API calls

### Option A — Clone into `plugins/`

```bash
cd /path/to/mautic
git clone https://github.com/wieslawgolec/plugin-omnichannel-messaging.git plugins/MauticOmnichannelMessagingBundle
```

The directory name **must** be `MauticOmnichannelMessagingBundle`.

### Enable

```bash
php bin/console cache:clear
php bin/console mautic:plugins:reload
```

Then open **Settings → Plugins** and publish **Omnichannel Messaging**.

---

## Architecture

```
plugins/MauticOmnichannelMessagingBundle/
├── Helper/
│   ├── ChannelInterface.php          # Contract every channel implements
│   ├── AbstractChannelHelper.php     # Shared HTTP + phone helpers
│   ├── WhatsAppChannelHelper.php
│   ├── TelegramChannelHelper.php
│   ├── WeComChannelHelper.php
│   ├── WeChatChannelHelper.php
│   ├── DiscordChannelHelper.php
│   ├── ChannelRegistry.php           # Discovers all channels
│   ├── WindowManager.php             # 24 h / 48 h session logic
│   └── MessageSender.php             # High-level facade (window → text|template)
├── EventListener/CampaignSubscriber.php
├── Form/Type/OmnichannelSendType.php
├── Controller/ (Webhook, Template, Conversation stubs)
└── Tests/Unit/                       # 44 PHPUnit tests
```

### Adding a new channel

1. Implement `ChannelInterface` (or extend `AbstractChannelHelper`)
2. Register it in `ChannelRegistry::__construct()`
3. Add unit tests

---

## Campaign action

**Action key:** `omnichannel.send`

| Field | Purpose |
| --- | --- |
| `channel` | `whatsapp` / `telegram` / `wecom` / `wechat` / `discord` |
| `message` | Free-form body (tokens supported) |
| `template_name` | Used automatically when the session window is closed |
| `language` | Template language (default `en_US`) |
| `to_override` | Optional override of the contact identifier |

`MessageSender` decides at runtime:

* Window open → free-form text
* Window closed + channel supports templates → template (requires `template_name`)
* Window closed + no template support → error

---

## Session windows

| Channel | Window | Behaviour outside window |
| --- | --- | --- |
| WhatsApp | 24 h after last inbound | Only approved templates |
| WeChat OA | 48 h after last inbound | Only approved templates |
| Telegram / WeCom / Discord | none | Free-form always allowed |

`WindowManager` is pure logic and fully unit-tested.

---

## Inbound webhooks

Public route:

```
POST/GET /omnichannel/webhook/{channel}
```

Supported: `whatsapp`, `telegram`, `wecom`, `wechat`.

WhatsApp also answers the Meta verification challenge (`hub.challenge`).

Parsed payload yields:

* `contact_identifier` (phone / chat_id / userid / openid)
* `body`
* `message_id`
* optional `media`

In a full installation the controller would resolve the Contact, open the session window, persist the message and dispatch `OmnichannelEvents::MESSAGE_INBOUND`.

---

## Development & tests

```bash
composer update --ignore-platform-reqs   # or use the PHPUnit 11.5 phar
vendor/bin/phpunit --configuration phpunit.xml.dist
# or
php phpunit-11.5.phar --configuration phpunit.xml.dist
```

CI runs on every push against **PHP 8.2 / 8.5 / 8.6**.

```
OK (44 tests, 131 assertions)
```

---

## Support the project

If this plugin saves you time, you can support development:

* **GitHub Sponsors:** [github.com/sponsors/wieslawgolec](https://github.com/sponsors/wieslawgolec)
* **Buy Me a Coffee:** [buymeacoffee.com/wieslawgolec](https://buymeacoffee.com/wieslawgolec)

## License

GPL-3.0-or-later (same family as Mautic).
