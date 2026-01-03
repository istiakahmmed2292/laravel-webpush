<?php

namespace App\Notifications;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class AdminBlogCreatedNotification extends Notification
{
    use Queueable;

    protected Blog $blog;
    protected User $author;

    public function __construct(Blog $blog, User $author)
    {
        $this->blog = $blog;
        $this->author = $author;
    }

    public function via(object $notifiable): array
    {
        return ['webpush'];
    }

    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('New blog by ' . $this->author->name)
            ->body('"' . $this->blog->title . '" was just published.')
            ->icon('/icon.png')
            ->data([
                'blog_id' => $this->blog->id,
                'author_id' => $this->author->id,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'blog_id' => $this->blog->id,
            'author_id' => $this->author->id,
        ];
    }
}
