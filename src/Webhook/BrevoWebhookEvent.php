<?php

namespace Kreatif\BrevoMailer\Webhook;

/**
 * A single event from a Brevo transactional webhook call
 * (see https://developers.brevo.com/docs/transactional-webhooks), normalized
 * from Brevo's raw payload shape (snake_case event names, hyphenated
 * "message-id" field) into a typed value object with semantic helpers.
 *
 * This class only knows Brevo's wire format — what to *do* with an event
 * (look up a local record, notify someone, ...) is up to the consuming app.
 */
final class BrevoWebhookEvent
{
    public const HARD_BOUNCE = 'hard_bounce';
    public const SOFT_BOUNCE = 'soft_bounce';
    public const BLOCKED = 'blocked';
    public const INVALID_EMAIL = 'invalid_email';
    public const SPAM = 'spam';

    /**
     * Event types that represent a confirmed, non-retryable delivery
     * problem. `soft_bounce` is deliberately excluded: Brevo keeps retrying
     * those internally on its own, so a single soft-bounce event isn't yet
     * a final outcome.
     */
    private const TERMINAL_TYPES = [self::HARD_BOUNCE, self::BLOCKED, self::INVALID_EMAIL, self::SPAM];

    public function __construct(
        public readonly string $type,
        public readonly ?string $email,
        public readonly ?string $messageId,
        public readonly ?string $reason = null,
        public readonly ?int $timestamp = null,
        public readonly array $raw = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['event'] ?? '',
            email: $data['email'] ?? null,
            messageId: $data['message-id'] ?? null,
            reason: $data['reason'] ?? null,
            timestamp: isset($data['ts_epoch']) ? (int) $data['ts_epoch'] : null,
            raw: $data,
        );
    }

    /**
     * Brevo posts one flat event object per webhook call by default, but
     * accepts a `batched` webhook config where it sends an array of events
     * instead — handle both shapes.
     *
     * @return array<int, self>
     */
    public static function manyFromArray(array $payload): array
    {
        $events = array_is_list($payload) ? $payload : [$payload];

        return array_map(self::fromArray(...), $events);
    }

    public function isHardBounce(): bool
    {
        return self::HARD_BOUNCE === $this->type;
    }

    public function isSoftBounce(): bool
    {
        return self::SOFT_BOUNCE === $this->type;
    }

    public function isBlocked(): bool
    {
        return self::BLOCKED === $this->type;
    }

    public function isInvalidEmail(): bool
    {
        return self::INVALID_EMAIL === $this->type;
    }

    public function isSpamComplaint(): bool
    {
        return self::SPAM === $this->type;
    }

    public function isBounce(): bool
    {
        return in_array($this->type, [self::HARD_BOUNCE, self::SOFT_BOUNCE, self::BLOCKED, self::INVALID_EMAIL], true);
    }

    /**
     * Whether this event is a confirmed, final delivery problem (as opposed
     * to e.g. a soft bounce Brevo may still resolve on its own) — useful for
     * deciding whether to alert someone about it.
     */
    public function isTerminal(): bool
    {
        return in_array($this->type, self::TERMINAL_TYPES, true);
    }

    public function isValid(): bool
    {
        return '' !== $this->type && null !== $this->messageId;
    }
}
