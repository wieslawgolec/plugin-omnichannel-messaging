# Mautic Omnichannel Messaging Bundle

Messaging channels for **Mautic 7.2+**.

Turns WhatsApp, Telegram, WeCom, WeChat Official Account and Discord into proper messaging channels with:

- Session / customer-care window handling
- Automatic free-form ↔ template fallback
- Inbound webhook parsing
- Campaign action (“Send Omnichannel Message”)
- Template CRUD + conversation timeline
- Per-channel credential configuration
- Extensible channel registry

|  |  |
| --- | --- |
| **Version** | 1.2.0 |
| **PHP** | 8.2+ (CI: 8.2, 8.5, 8.6) |
| **Mautic** | 7.2+ |
| **License** | GPL-3.0-or-later |

---

## Table of contents

1. [Capability matrix](#capability-matrix)
2. [Install in Mautic](#install-in-mautic)
3. [Architecture](#architecture)
4. [Entities & models](#entities--models)
5. [UI](#ui)
6. [Channel configuration](#channel-configuration)
7. [Campaign action](#campaign-action)
8. [Session windows](#session-windows)
9. [Inbound webhooks](#inbound-webhooks)
10. [Development & tests](#development--tests)
11. [Support the project](#support-the-project)

---

## Capability matrix

| Channel | Free-form | Templates | Window | Inbound | Media | Campaign | Config form |
| --- | --- | --- | --- | --- | --- | --- | --- |
| **WhatsApp** (Cloud API) | ✓ (inside window) | ✓ | 24 h | ✓ | ✓ | ✓ | ✓ |
| **Telegram** | ✓ always | — | none | ✓ | ✓ | ✓ | ✓ |
| **WeCom** (企业微信) | ✓ always | — | none | ✓ | ✓ | ✓ | ✓ |
| **WeChat OA** | ✓ (inside window) | ✓ | 48 h | ✓ | ✓ | ✓ | ✓ |
| **Discord** | ✓ always | — | none | — | ✓ | ✓ | ✓ |

Complementary to [plugin-social](https://github.com/wieslawgolec/plugin-social): Social = broadcast / publishing; Omnichannel = conversational messaging.

---

## Install in Mautic

### Requirements

* Mautic **7.2+**
* PHP **8.2+** (8.5 / 8.6 supported)
* `curl` extension recommended

### Clone

```bash
cd /path/to/mautic
git clone https://github.com/wieslawgolec/plugin-omnichannel-messaging.git plugins/MauticOmnichannelMessagingBundle
php bin/console cache:clear
php bin/console mautic:plugins:reload
```

Directory name **must** be `MauticOmnichannelMessagingBundle`.

Then **Settings → Plugins → Omnichannel Messaging** → publish, and configure each channel under **Channels → Omnichannel Config**.

---

## Architecture

```
plugins/MauticOmnichannelMessagingBundle/
├── Entity/
│   ├── MessageTemplate.php      # Approved / draft templates
│   ├── Conversation.php         # Thread + session window state
│   └── MessageLog.php           # Inbound / outbound message history
├── Model/
│   ├── TemplateModel.php
│   └── ConversationModel.php    # findOrCreate, recordInbound/Outbound
├── Helper/                      # Channel drivers + WindowManager + MessageSender
├── Integration/
│   └── OmnichannelIntegration.php  # Per-channel credentials
├── Controller/
│   ├── TemplateController.php      # Full CRUD UI
│   ├── ConversationController.php  # List + timeline
│   ├── ConfigController.php        # Credential forms
│   └── WebhookController.php
├── Form/Type/
│   ├── OmnichannelSendType.php
│   ├── MessageTemplateType.php
│   └── ChannelConfigType.php
└── Tests/Unit/                  # 73 PHPUnit tests
```

---

## Entities & models

### MessageTemplate
- name, channel, language, category, status (`draft|pending|approved|rejected|disabled`)
- body, parameters, externalId, isPublished
- `canBeSent()` = approved + published

### Conversation
- leadId, channel, contactIdentifier, status
- lastInboundAt / lastOutboundAt / windowExpiresAt
- `openWindow($hours)` refreshes the session
- `isWindowOpen()`

### MessageLog
- direction (`inbound|outbound`), status, body, externalMessageId
- usedTemplate / templateName, media, errorMessage
- `markSent()` / `markFailed()`

Entities use Mautic `ClassMetadataBuilder` + `CommonRepository`. Models accept repository injection for production; unit tests use an in-memory fallback so CI stays kernel-free.

---

## UI

| Route | Description |
| --- | --- |
| `/s/omnichannel/templates` | Template list + New / Edit / View / Delete |
| `/s/omnichannel/conversations` | Conversation list |
| `/s/omnichannel/conversations/view/{id}` | Timeline (inbound blue, outbound green) |
| `/s/omnichannel/config` | Channel list |
| `/s/omnichannel/config/{channel}` | Credential form for one channel |

Menu entries appear under **Channels**.

---

## Channel configuration

`OmnichannelIntegration` stores credentials per channel:

| Channel | Key fields |
| --- | --- |
| WhatsApp | phone_number_id, access_token, verify_token, waba_id |
| Telegram | bot_token, bot_username |
| WeCom | corpid, corpsecret, agentid |
| WeChat | appid, appsecret, token |
| Discord | webhook_url, bot_token, default_channel_id |

`getCredentialsForChannel()` strips the `enabled` flag and is what `MessageSender` consumes.

---

## Campaign action

**Action key:** `omnichannel.send`

| Field | Purpose |
| --- | --- |
| channel | whatsapp / telegram / wecom / wechat / discord |
| message | Free-form body (tokens supported) |
| template_name | Used when the session window is closed |
| language | Template language (default `en_US`) |
| to_override | Optional recipient override |

`MessageSender` decides at runtime: window open → text; closed + templates supported → template; otherwise error.

---

## Session windows

| Channel | Window | Outside window |
| --- | --- | --- |
| WhatsApp | 24 h after last inbound | Templates only |
| WeChat OA | 48 h after last inbound | Templates only |
| Telegram / WeCom / Discord | none | Free-form always |

---

## Inbound webhooks

```
POST/GET /omnichannel/webhook/{channel}
```

Supported: `whatsapp`, `telegram`, `wecom`, `wechat`.

WhatsApp answers the Meta verification challenge. Parsed payload yields `contact_identifier`, `body`, `message_id`, optional `media`. The webhook controller is ready to call `ConversationModel::recordInbound()` once wired to Doctrine + contact resolution.

---

## Development & tests

```bash
composer update --ignore-platform-reqs
vendor/bin/phpunit --configuration phpunit.xml.dist
# or
php phpunit-11.5.phar --configuration phpunit.xml.dist
```

CI runs on every push against **PHP 8.2 / 8.5 / 8.6**.

```
OK (73 tests, 239 assertions)
```

---

## Support the project

If this plugin saves you time:

* **GitHub Sponsors:** [github.com/sponsors/wieslawgolec](https://github.com/sponsors/wieslawgolec)
* **Buy Me a Coffee:** [buymeacoffee.com/wieslawgolec](https://buymeacoffee.com/wieslawgolec)

## License

GPL-3.0-or-later (same family as Mautic).

