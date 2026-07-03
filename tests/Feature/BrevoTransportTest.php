<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Kreatif\BrevoMailer\Brevo\BrevoApiClient;
use Kreatif\BrevoMailer\Brevo\BrevoTransport;
use Kreatif\BrevoMailer\Exceptions\BrevoApiException;
use Symfony\Component\Mime\Email;

function makeClient(MockHandler $mock): BrevoApiClient
{
    $httpClient = new Client(['handler' => HandlerStack::create($mock)]);

    return new BrevoApiClient(apiKey: 'test-key', baseUri: 'https://api.brevo.com/v3/', client: $httpClient);
}

it('sends an email through the Brevo API and captures the message id', function () {
    $mock = new MockHandler([
        new Response(201, ['Content-Type' => 'application/json'], json_encode(['messageId' => '<abc@brevo>'])),
    ]);

    $transport = new BrevoTransport(makeClient($mock));

    $email = (new Email())
        ->from('sender@monni.bz.it')
        ->to('customer@example.com')
        ->subject('Hello')
        ->html('<p>Hi</p>');

    $sentMessage = $transport->send($email);

    expect($sentMessage->getMessageId())->toBe('<abc@brevo>');
});

it('throws when the Brevo API responds with an error status', function () {
    $mock = new MockHandler([
        new Response(400, ['Content-Type' => 'application/json'], json_encode(['code' => 'invalid_parameter', 'message' => 'bad request'])),
    ]);

    $transport = new BrevoTransport(makeClient($mock));

    $email = (new Email())
        ->from('sender@monni.bz.it')
        ->to('customer@example.com')
        ->subject('Hello')
        ->html('<p>Hi</p>');

    expect(fn () => $transport->send($email))
        ->toThrow(\Symfony\Component\Mailer\Exception\TransportException::class);
});

it('throws a BrevoApiException with the status code preserved', function () {
    $mock = new MockHandler([
        new Response(500, [], 'server error'),
    ]);

    $client = makeClient($mock);

    try {
        $client->sendTransactionalEmail(['subject' => 'x']);
        expect(false)->toBeTrue('expected exception was not thrown');
    } catch (BrevoApiException $e) {
        expect($e->statusCode)->toBe(500);
    }
});
