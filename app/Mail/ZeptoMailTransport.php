<?php

namespace App\Mail;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

class ZeptoMailTransport extends AbstractTransport
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $apiUrl,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();
        $payload = $this->payload($email, $envelope);

        $response = Http::timeout(30)
            ->acceptJson()
            ->withHeaders([
                'Authorization' => 'Zoho-enczapikey '.$this->apiKey,
            ])
            ->post($this->apiUrl, $payload);

        if (! $response->successful()) {
            throw new TransportException(sprintf(
                'ZeptoMail API returned status %s: %s',
                $response->status(),
                $response->body()
            ));
        }
    }

    private function payload(Email $email, Envelope $envelope): array
    {
        $payload = [
            'from' => $this->formatAddress($envelope->getSender()),
            'to' => $this->formatAddresses($this->primaryRecipients($email, $envelope)),
            'cc' => $this->formatAddresses($email->getCc()),
            'bcc' => $this->formatAddresses($email->getBcc()),
            'subject' => $email->getSubject(),
            'htmlbody' => $email->getHtmlBody() ?: nl2br(e((string) $email->getTextBody())),
        ];

        $text = $email->getTextBody();

        if (filled($text)) {
            $payload['textbody'] = $text;
        }

        $attachments = $this->attachments($email);

        if ($attachments !== []) {
            $payload['attachments'] = $attachments;
        }

        return $payload;
    }

    private function primaryRecipients(Email $email, Envelope $envelope): array
    {
        return array_values(array_filter($envelope->getRecipients(), function (Address $address) use ($email): bool {
            return ! in_array($address, array_merge($email->getCc(), $email->getBcc()), true);
        }));
    }

    private function formatAddresses(array $addresses): array
    {
        return array_map(fn (Address $address): array => [
            'email_address' => $this->formatAddress($address),
        ], $addresses);
    }

    private function formatAddress(Address $address): array
    {
        return [
            'address' => $address->getAddress(),
            'name' => $address->getName(),
        ];
    }

    private function attachments(Email $email): array
    {
        $attachments = [];

        foreach ($email->getAttachments() as $attachment) {
            $headers = $attachment->getPreparedHeaders();
            $filename = $headers->getHeaderParameter('Content-Disposition', 'filename') ?: 'attachment';
            $contentType = $headers->get('Content-Type')?->getBody() ?: 'application/octet-stream';

            $attachments[] = [
                'content' => str_replace("\r\n", '', $attachment->bodyToString()),
                'name' => $filename,
                'mime_type' => $contentType,
            ];
        }

        return $attachments;
    }

    public function __toString(): string
    {
        return 'zeptomail';
    }
}
