<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Helper;

/**
 * WeCom (企业微信 / Enterprise WeChat) application messaging.
 *
 * Uses the standard WeCom API for sending text / markdown / template-card
 * messages to users by userid.
 */
final class WeComChannelHelper extends AbstractChannelHelper
{
    public const API_BASE = 'https://qyapi.weixin.qq.com/cgi-bin';

    public function getName(): string
    {
        return 'wecom';
    }

    public function getDisplayName(): string
    {
        return 'WeCom';
    }

    public function supportsFreeformOutsideWindow(): bool
    {
        return true;
    }

    public function getWindowHours(): int
    {
        return 0;
    }

    public function supportsTemplates(): bool
    {
        return false; // uses message types instead of approved templates
    }

    public function supportsInbound(): bool
    {
        return true; // via callback URL
    }

    public function supportsMedia(): bool
    {
        return true;
    }

    public function sendText(array $config, string $to, string $body, array $options = []): array
    {
        $corpid     = $config['corpid'] ?? '';
        $corpsecret = $config['corpsecret'] ?? '';
        $agentid    = $config['agentid'] ?? '';

        if ('' === $corpid || '' === $corpsecret || '' === $agentid) {
            return ['success' => false, 'error' => 'Missing corpid, corpsecret or agentid'];
        }

        $tokenResult = $this->getAccessToken($corpid, $corpsecret);
        if (!$tokenResult['success']) {
            return $tokenResult;
        }
        $accessToken = $tokenResult['access_token'];

        $msgType = $options['msgtype'] ?? 'text';
        $payload = [
            'touser'  => $to, // userid, or @all
            'msgtype' => $msgType,
            'agentid' => (int) $agentid,
            'safe'    => (int) ($options['safe'] ?? 0),
        ];

        if ('markdown' === $msgType) {
            $payload['markdown'] = ['content' => $body];
        } else {
            $payload['text'] = ['content' => $body];
        }

        $url      = self::API_BASE . '/message/send?access_token=' . rawurlencode($accessToken);
        $response = $this->httpPost($url, $payload);

        return $this->mapResponse($response);
    }

    public function parseInbound(array $payload, array $config = []): array
    {
        // WeCom callback after XML decryption (caller is expected to decrypt first)
        $msgType = $payload['MsgType'] ?? $payload['msgtype'] ?? '';
        $from    = $payload['FromUserName'] ?? $payload['from'] ?? '';
        $content = $payload['Content'] ?? $payload['content'] ?? '';
        $msgId   = isset($payload['MsgId']) ? (string) $payload['MsgId'] : (string) ($payload['msgid'] ?? '');

        if ('' === $from) {
            return ['valid' => false];
        }

        return [
            'valid'              => true,
            'contact_identifier' => $from,
            'body'               => is_string($content) ? $content : '',
            'message_id'         => $msgId,
            'media'              => [],
            'raw'                => $payload,
            'msg_type'           => $msgType,
        ];
    }

    /**
     * @return array{success: bool, access_token?: string, error?: string}
     */
    private function getAccessToken(string $corpid, string $corpsecret): array
    {
        $url = self::API_BASE . '/gettoken?corpid=' . rawurlencode($corpid)
            . '&corpsecret=' . rawurlencode($corpsecret);

        $ch = curl_init($url);
        if (false === $ch) {
            return ['success' => false, 'error' => 'curl_init failed'];
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        if (false === $body) {
            return ['success' => false, 'error' => 'Failed to obtain access_token'];
        }

        $json = json_decode($body, true);
        if (!is_array($json) || empty($json['access_token'])) {
            return [
                'success' => false,
                'error'   => $json['errmsg'] ?? 'Invalid token response',
            ];
        }

        return [
            'success'      => true,
            'access_token' => $json['access_token'],
        ];
    }

    /**
     * @param array{status: int, body: string, json?: array|null} $response
     */
    private function mapResponse(array $response): array
    {
        $json = $response['json'] ?? null;
        if (!is_array($json)) {
            return [
                'success' => false,
                'error'   => 'Invalid response: ' . ($response['body'] ?? 'empty'),
            ];
        }

        $errcode = (int) ($json['errcode'] ?? -1);
        if (0 !== $errcode) {
            return [
                'success' => false,
                'error'   => $json['errmsg'] ?? ('errcode ' . $errcode),
                'raw'     => $json,
            ];
        }

        return [
            'success'    => true,
            'message_id' => isset($json['msgid']) ? (string) $json['msgid'] : null,
            'raw'        => $json,
        ];
    }
}
