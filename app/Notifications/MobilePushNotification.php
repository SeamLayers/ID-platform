<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;

class MobilePushNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public array $data = []
    ) {}

    /**
     * Notification channels
     */
    public function via($notifiable)
    {
        return ['database', FcmChannel::class];
    }

    /**
     * Database notification
     */
    public function toDatabase($notifiable)
    {
        return [
            'title'   => $this->title,
            'message' => $this->message,
            'data'    => $this->data,
        ];
    }

    /**
     * FCM push notification
     */
    public function toFcm($notifiable)
    {
        return FcmMessage::create()
            ->setNotification([
                'title' => $this->title,
                'body'  => $this->message,
            ])
            ->setData($this->data);
    }
}
