<?php

namespace Kreatif\BrevoMailer\Brevo;

use Kreatif\BrevoMailer\Exceptions\BrevoApiException;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class BrevoTransport extends AbstractTransport
{
    public function __construct(private readonly BrevoApiClient $client)
    {
        parent::__construct();
    }

    public function __toString(): string
    {
        return 'brevo+api';
    }

    protected function doSend(SentMessage $message): void
    {
        $email = $message->getOriginalMessage();

        if (!$email instanceof Email) {
            throw new TransportException('BrevoTransport only supports Symfony\Component\Mime\Email messages.');
        }

        try {
            $response = $this->client->sendTransactionalEmail($this->buildPayload($email));
        } catch (BrevoApiException $e) {
            throw new TransportException($e->getMessage(), 0, $e);
        }

        if (isset($response['messageId'])) {
            $message->setMessageId($response['messageId']);
        }
    }

    private function buildPayload(Email $email): array
    {
        $from = $email->getFrom()[0] ?? null;

        $payload = [
            'sender' => $from ? $this->addressToArray($from) : null,
            'to' => $this->addressesToArray($email->getTo()),
            'subject' => (string) $email->getSubject(),
        ];

        if ($cc = $this->addressesToArray($email->getCc())) {
            $payload['cc'] = $cc;
        }
        if ($bcc = $this->addressesToArray($email->getBcc())) {
            $payload['bcc'] = $bcc;
        }
        if ($replyTo = $email->getReplyTo()[0] ?? null) {
            $payload['replyTo'] = $this->addressToArray($replyTo);
        }
        if (null !== $email->getHtmlBody()) {
            $payload['htmlContent'] = (string) $email->getHtmlBody();
        }
        if (null !== $email->getTextBody()) {
            $payload['textContent'] = (string) $email->getTextBody();
        }
        if ($attachments = $this->attachmentsToArray($email)) {
            $payload['attachment'] = $attachments;
        }

        return array_filter($payload, static fn ($value) => null !== $value);
    }

    private function addressesToArray(array $addresses): array
    {
        return array_map($this->addressToArray(...), $addresses);
    }

    private function addressToArray(Address $address): array
    {
        return array_filter([
            'email' => $address->getAddress(),
            'name' => $address->getName() ?: null,
        ]);
    }

    private function attachmentsToArray(Email $email): array
    {
        return array_map(static function (DataPart $part) {
            return [
                'name' => $part->getFilename() ?? 'attachment',
                'content' => base64_encode($part->getBody()),
            ];
        }, $email->getAttachments());
    }
}
