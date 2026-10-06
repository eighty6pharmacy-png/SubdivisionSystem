<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Helpers\PushNotificationHelper;

class WebAndPushNotification extends Notification
{
    use Queueable;

    public $title;
    public $message;
    public $category;

    /**
     * Create a new notification instance.
     */
    public function __construct($title, $message, $category = 'system')
    {
        $this->title = $title;
        $this->message = $message;
        $this->category = $category;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // For OneSignal, we can either make a custom channel or just fire it here.
        // Firing it inline since we just want to execute a REST API call.
        if (isset($notifiable->id)) {
            PushNotificationHelper::send($this->title, $this->message, $notifiable->id);
        } else {
            PushNotificationHelper::send($this->title, $this->message);
        }

        // Return 'database' so it also saves to the `notifications` table for the web UI.
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'category' => $this->category,
        ];
    }
}
