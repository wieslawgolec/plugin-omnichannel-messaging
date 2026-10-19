<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Helper;

/**
 * WeChat Official Account (公众号) customer service / template messaging.
 *
 * - Free-form CS messages only inside 48h window after user interaction
 * - Template messages available outside the window (approved templates)
 */
final class WeChatChannelHelper extends AbstractChannelHelper
{
    public const API_BASE = 'https://api.weixin.qq.com/cgi-bin';

    public function getName(): string
    {
        return 'wechat';
    }

    public function getDisplayName(): string
    {
        return 'WeChat OA';
    }

    public function supportsFreeformOutsideWindow(): bool
    {
        return false;
    }

    public function getWindowHours(): int
    {
        return 48;
    }

    public function supportsTemplates(): bool
    {
        return true;
    }

    public function supportsInbound(): bool
    {
        return true;
    }

    public function supportsMedia(): bool
    {
        return true;
    }

    public function sendText(array $config, string $to, string $body, array $options = []): array
    {
        $accessToken = $config['access_token'] ?? '';
        if ('' === $accessToken) {
            // try to obtain one
            $tokenResult = $this->getAccessToken($config);
            if (!$tokenResult['success']) {
                return $tokenResult;
            }
            $accessToken = $tokenResult['access_token'];
        }

        $payload = [
            'touser'  => $to, // openid
            'msgtype' => 'text',
            'text'    => ['content' => $body],
        ];

        $url      = self::API_BASE . '/message/custom/send?access_token=' . rawurlencode($accessToken);
        $response = $this->httpPost($url, $payload);

        return $this->mapResponse($response);
    }

    public function sendTemplate(array $config, string $to, string $templateName, string $language, array $parameters = [], array $options = []): array
    {
        $accessToken = $config['access_token'] ?? '';
        if ('' === $accessToken) {
            $tokenResult = $this->getAccessToken($config);
            if (!$tokenResult['success']) {
                return $tokenResult;
            }
            $accessToken = $tokenResult['access_token'];
        }

        // WeChat template data format is key => ['value' => ..., 'color' => ...]
        $data = [];
        foreach ($parameters as $key => $value) {
            $data[is_string($key) ? $key : 'keyword' . ((int) $key + 1)] = [
                'value' => (string) $value,
            ];
        }

        $payload = [
            'touser'      => $to,
            'template_id' => $templateName,
            'data'        => $data,
        ];

        if (!empty($options['url'])) {
            $payload['url'] = $options['url'];
        }
        if (!empty($options['miniprogram'])) {
            $payload['miniprogram'] = $options['miniprogram'];
        }

        $url      = self::API_BASE . '/message/template/send?access_token=' . rawurlencode($accessToken);
        $response = $this->httpPost($url, $payload);

        return $this->mapResponse($response);
    }

    public function parseInbound(array $payload, array $config = []): array
    {
        // After XML → array conversion by the webhook controller
        $msgType = $payload['MsgType'] ?? '';
        $from    = $payload['FromUserName'] ?? '';
        $content = $payload['Content'] ?? '';
        $msgId   = isset($payload['MsgId']) ? (string) $payload['MsgId'] : '';

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
    private function getAccessToken(array $config): array
    {
        $appid  = $config['appid'] ?? '';
        $secret = $config['appsecret'] ?? '';
        if ('' === $appid || '' === $secret) {
            return ['success' => false, 'error' => 'Missing appid or appsecret'];
        }

        $url = self::API_BASE . '/token?grant_type=client_credential'
            . '&appid=' . rawurlencode($appid)
            . '&secret=' . rawurlencode($secret);

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

        $errcode = (int) ($json['errcode'] ?? 0);
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
