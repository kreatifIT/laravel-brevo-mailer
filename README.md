# Laravel Brevo Mailer

A Symfony Mailer transport for the [Brevo](https://www.brevo.com/) transactional email API (`POST /v3/smtp/email`), for use with Laravel's `Mail` facade.

## Installation

```bash
composer require kreatif/laravel-brevo-mailer
```

## Configuration

```
BREVO_API_KEY=xxxxx
MAIL_MAILER=brevo
```

In `config/mail.php`, add a mailer entry:

```php
'brevo' => [
    'transport' => 'brevo',
],
```

No other code changes are needed — existing `Mailable`/`Notification` classes keep working unchanged; they just send through Brevo instead of SMTP.

## Bounce/complaint webhook parsing

Brevo reports what happened to a mail *after* it accepted it (bounces, spam complaints, ...) via a separate webhook call to a URL you configure in Brevo. This package includes `Kreatif\BrevoMailer\Webhook\BrevoWebhookEvent` to parse that payload into a typed object, since the wire format (event names, the hyphenated `message-id` field, which events are terminal vs. Brevo-retried) is Brevo API knowledge, not app-specific:

```php
use Kreatif\BrevoMailer\Webhook\BrevoWebhookEvent;

Route::post('brevo-webhook', function (Request $request) {
    foreach (BrevoWebhookEvent::manyFromArray($request->json()->all()) as $event) {
        if (!$event->isValid()) {
            continue;
        }
        // $event->type, $event->email, $event->messageId, $event->reason
        // $event->isHardBounce() / isSoftBounce() / isBlocked() / isInvalidEmail() / isSpamComplaint()
        // $event->isBounce() / $event->isTerminal() (confirmed problem, not a Brevo-retried soft bounce)

        // What to do with it (look up your own send-log record, notify
        // someone, ...) is entirely up to your app.
    }

    return response()->json(['received' => true]);
});
```

This package does **not** provide the route, auth middleware, or webhook registration itself (Brevo lets you set a custom `Authorization` header per webhook via their Create Webhook API/dashboard — use whatever auth scheme fits your app).

## Scope

This package only implements the Brevo transport and the bounce/complaint webhook payload parser — i.e. the parts that are genuinely Brevo API knowledge, reusable regardless of app. Queueing, retry policy, delivery logging, and failure notifications are application concerns and are intentionally left to the consuming app (see `monni_service`'s `app/Mail/Concerns`, `app/Listeners/Mail`, `app/Services/Mail`, `app/Http/Controllers/Mail/BrevoWebhookController`, and `EmailLog` model for that layer).

Swapping to a different provider later (e.g. Azure Communication Services) means adding a sibling transport and changing `MAIL_MAILER`, not touching any Mailable.
