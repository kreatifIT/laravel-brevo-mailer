<?php

use Kreatif\BrevoMailer\Webhook\BrevoWebhookEvent;

it('parses a single hard_bounce event and classifies it as terminal', function () {
    $event = BrevoWebhookEvent::fromArray([
        'event' => 'hard_bounce',
        'email' => 'example@domain.com',
        'message-id' => '201798300811.5787683@relay.domain.com',
        'reason' => 'server is down',
        'ts_epoch' => 1604933653,
    ]);

    expect($event->isHardBounce())->toBeTrue()
        ->and($event->isBounce())->toBeTrue()
        ->and($event->isTerminal())->toBeTrue()
        ->and($event->isValid())->toBeTrue()
        ->and($event->email)->toBe('example@domain.com')
        ->and($event->messageId)->toBe('201798300811.5787683@relay.domain.com')
        ->and($event->reason)->toBe('server is down');
});

it('classifies soft_bounce as a bounce but not terminal', function () {
    $event = BrevoWebhookEvent::fromArray([
        'event' => 'soft_bounce',
        'email' => 'example@domain.com',
        'message-id' => 'abc@relay.domain.com',
    ]);

    expect($event->isSoftBounce())->toBeTrue()
        ->and($event->isBounce())->toBeTrue()
        ->and($event->isTerminal())->toBeFalse();
});

it('classifies spam as terminal but not a bounce', function () {
    $event = BrevoWebhookEvent::fromArray([
        'event' => 'spam',
        'email' => 'example@domain.com',
        'message-id' => 'abc@relay.domain.com',
    ]);

    expect($event->isSpamComplaint())->toBeTrue()
        ->and($event->isBounce())->toBeFalse()
        ->and($event->isTerminal())->toBeTrue();
});

it('parses a single flat event object into one event', function () {
    $events = BrevoWebhookEvent::manyFromArray([
        'event' => 'blocked',
        'email' => 'example@domain.com',
        'message-id' => 'abc@relay.domain.com',
    ]);

    expect($events)->toHaveCount(1)
        ->and($events[0]->isBlocked())->toBeTrue();
});

it('parses a batched array payload into multiple events', function () {
    $events = BrevoWebhookEvent::manyFromArray([
        ['event' => 'hard_bounce', 'email' => 'a@example.com', 'message-id' => 'a@relay'],
        ['event' => 'spam', 'email' => 'b@example.com', 'message-id' => 'b@relay'],
    ]);

    expect($events)->toHaveCount(2)
        ->and($events[0]->isHardBounce())->toBeTrue()
        ->and($events[1]->isSpamComplaint())->toBeTrue();
});

it('is invalid without an event type or message id', function () {
    expect(BrevoWebhookEvent::fromArray(['email' => 'x@example.com'])->isValid())->toBeFalse();
});
