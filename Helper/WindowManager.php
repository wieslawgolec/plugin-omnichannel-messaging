<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Helper;

/**
 * Tracks and evaluates customer-care / session windows per contact + channel.
 *
 * In a full Mautic install this would persist to a table; here we keep a pure
 * logic helper that can be unit-tested and later wired to Doctrine.
 */
final class WindowManager
{
    /**
     * Decide whether a free-form message is currently allowed.
     *
     * @param \DateTimeInterface|null $lastInbound When the contact last messaged us
     * @param int                     $windowHours Channel window length (0 = always allowed)
     */
    public function isFreeformAllowed(?\DateTimeInterface $lastInbound, int $windowHours, ?\DateTimeInterface $now = null): bool
    {
        if ($windowHours <= 0) {
            return true; // no window restriction
        }

        if (null === $lastInbound) {
            return false;
        }

        $now ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $expiresAt = \DateTimeImmutable::createFromInterface($lastInbound)
            ->modify('+' . $windowHours . ' hours');

        return $now <= $expiresAt;
    }

    /**
     * Seconds remaining in the window (0 if closed / none).
     */
    public function remainingSeconds(?\DateTimeInterface $lastInbound, int $windowHours, ?\DateTimeInterface $now = null): int
    {
        if ($windowHours <= 0 || null === $lastInbound) {
            return 0;
        }

        $now ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $expiresAt = \DateTimeImmutable::createFromInterface($lastInbound)
            ->modify('+' . $windowHours . ' hours');

        $diff = $expiresAt->getTimestamp() - $now->getTimestamp();

        return max(0, $diff);
    }

    /**
     * Whether a template must be used instead of free-form text.
     */
    public function mustUseTemplate(?\DateTimeInterface $lastInbound, ChannelInterface $channel, ?\DateTimeInterface $now = null): bool
    {
        if (!$channel->supportsTemplates()) {
            return false;
        }

        if ($channel->supportsFreeformOutsideWindow()) {
            return false;
        }

        return !$this->isFreeformAllowed($lastInbound, $channel->getWindowHours(), $now);
    }
}
