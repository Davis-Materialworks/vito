<?php

namespace App\NotificationChannels;

use App\Models\NotificationChannel;
use App\Notifications\NotificationInterface;
use Illuminate\Support\Facades\Http;
use Throwable;

class Ntfy extends AbstractNotificationChannel
{
    public static function id(): string
    {
        return 'ntfy';
    }

    public function createRules(array $input): array
    {
        return [
            'server_url' => ['nullable', 'url'],
            'topic' => ['required'],
            'token' => ['nullable'],
        ];
    }

    public function createData(array $input): array
    {
        return [
            'server_url' => $input['server_url'] ?: 'https://ntfy.sh',
            'topic' => $input['topic'],
            'token' => $input['token'] ?? '',
        ];
    }

    public function data(): array
    {
        return [
            'server_url' => $this->notificationChannel->data['server_url'] ?? 'https://ntfy.sh',
            'topic' => $this->notificationChannel->data['topic'] ?? '',
            'token' => $this->notificationChannel->data['token'] ?? '',
        ];
    }

    public function connect(): bool
    {
        try {
            $this->sendToNtfy(__('Connected!'));
        } catch (Throwable) {
            return false;
        }

        return true;
    }

    public function send(object $notifiable, NotificationInterface $notification): void
    {
        /** @var NotificationChannel $notifiable */
        $this->notificationChannel = $notifiable;
        $this->sendToNtfy($notification->toNtfy($notifiable));
    }

    private function sendToNtfy(string $text): void
    {
        $data = $this->data();
        $url = rtrim($data['server_url'], '/').'/'.$data['topic'];

        $request = Http::asJson();
        if (! empty($data['token'])) {
            $request = $request->withHeaders(['Authorization' => 'Bearer '.$data['token']]);
        }

        $request->post($url, [
            'title' => config('app.name'),
            'message' => $text,
        ])->throw();
    }
}
