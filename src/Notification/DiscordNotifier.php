<?php

namespace Hitrov\Notification;

use Hitrov\Interfaces\NotifierInterface;

class DiscordNotifier implements NotifierInterface
{
    private $webhookUrl;

    public function __construct(string $webhookUrl)
    {
        $this->webhookUrl = $webhookUrl;
    }

    public function isSupported(): bool
    {
        // Discord notifications are always supported
        return true;
    }

    public function notify(string $message): array
    {
        $data = json_encode(["content" => $message]);

        $ch = curl_init($this->webhookUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // You might want to verify SSL

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        return [
            'response' => $response,
            'info' => $info
        ];
    }
}