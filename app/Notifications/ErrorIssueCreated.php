<?php

namespace App\Notifications;

use App\Models\ErrorIssue;
use App\Models\Site;
use App\Notifications\Channels\AbstractNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Str;

class ErrorIssueCreated extends AbstractNotification
{
    public function __construct(
        protected ErrorIssue $issue,
        protected Site $site
    ) {}

    public function rawText(): string
    {
        return __('New error detected on [:site] :class: :message', [
            'site' => $this->site->domain,
            'class' => $this->issue->exception_class,
            'message' => Str::limit($this->issue->normalized_message, 120),
        ]);
    }

    public function toEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('New Error: :class on :site', [
                'class' => class_basename($this->issue->exception_class),
                'site' => $this->site->domain,
            ]))
            ->line($this->rawText());
    }
}
