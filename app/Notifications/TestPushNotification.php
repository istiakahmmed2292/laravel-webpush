<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage; // ✅ Add this
use App\Models\Blog;

class TestPushNotification extends Notification
{
    use Queueable;

    /** @var Blog|null */
    protected $blog;

    /**
     * Create a new notification instance.
     */
    public function __construct(?Blog $blog = null)
    {
        $this->blog = $blog;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['webpush'];
    }

    /**
     * Get the Web Push representation of the notification.
     */
    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        $title = $this->blog
            ? 'New blog created: ' . $this->blog->title
            : 'Laravel Web Push Test 🎉';

        $body = $this->blog
            ? 'Your blog has been published successfully.'
            : 'This is a test notification.';

        return (new WebPushMessage)
            ->title($title)
            ->body($body)
            ->icon('/icon.png'); // optional
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
