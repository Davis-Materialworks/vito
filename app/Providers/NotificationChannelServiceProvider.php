<?php

namespace App\Providers;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\NotificationChannels\Discord;
use App\NotificationChannels\Email;
use App\NotificationChannels\Ntfy;
use App\NotificationChannels\Slack;
use App\NotificationChannels\Telegram;
use App\Plugins\RegisterNotificationChannel;
use Illuminate\Support\ServiceProvider;

class NotificationChannelServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->discord();
        $this->slack();
        $this->email();
        $this->telegram();
        $this->ntfy();
    }

    private function discord(): void
    {
        RegisterNotificationChannel::make(Discord::id())
            ->label('Discord')
            ->handler(Discord::class)
            ->form(
                DynamicForm::make([
                    DynamicField::make('webhook_url')
                        ->text()
                        ->label('Webhook URL'),
                ])
            )
            ->register();
    }

    public function slack(): void
    {
        RegisterNotificationChannel::make(Slack::id())
            ->label('Slack')
            ->handler(Slack::class)
            ->form(
                DynamicForm::make([
                    DynamicField::make('webhook_url')
                        ->text()
                        ->label('Webhook URL'),
                ])
            )
            ->register();
    }

    private function email(): void
    {
        RegisterNotificationChannel::make(Email::id())
            ->label('Email')
            ->handler(Email::class)
            ->form(
                DynamicForm::make([
                    DynamicField::make('email')
                        ->text()
                        ->label('Email address'),
                ])
            )
            ->register();
    }

    private function telegram(): void
    {
        RegisterNotificationChannel::make(Telegram::id())
            ->label('Telegram')
            ->handler(Telegram::class)
            ->form(
                DynamicForm::make([
                    DynamicField::make('bot_token')
                        ->text()
                        ->label('Bot Token'),
                    DynamicField::make('chat_id')
                        ->text()
                        ->label('Chat ID'),
                ]),
            )
            ->register();
    }

    private function ntfy(): void
    {
        RegisterNotificationChannel::make(Ntfy::id())
            ->label('ntfy')
            ->handler(Ntfy::class)
            ->form(
                DynamicForm::make([
                    DynamicField::make('server_url')
                        ->text()
                        ->label('Server URL')
                        ->default('https://ntfy.sh')
                        ->placeholder('https://ntfy.sh'),
                    DynamicField::make('topic')
                        ->text()
                        ->label('Topic'),
                    DynamicField::make('token')
                        ->password()
                        ->label('Access Token (optional)'),
                ]),
            )
            ->register();
    }
}
