# Web Push + Auth + Blog Flow

This document captures the end-to-end setup we implemented: packages, code snippets, permission flow, admin/user roles, and the main debugging steps.

## 1) Packages Installed
- Required: `laravel-notification-channels/webpush` (composer require laravel-notification-channels/webpush)
- Already present: Laravel 12, Tinker, dev tools (Pint, Sail, etc.).

## 2) Database / Models
- Users migration includes role flag: `is_admin` tinyint default 0 (0=User, 1=Admin). Create admins via seeder or direct DB update.
- Blogs migration includes `user_id` foreign key with cascade delete.
- `User` model: `blogs()` hasMany and WebPush trait already in place.
- `Blog` model: fillable includes `user_id`; belongsTo User.

## 3) Routing & Middleware
- Auth routes for login/register/dashboard.
- Blog resource routes are protected with `auth` middleware.
- Push subscribe endpoint: `POST /push/subscribe` with `auth` middleware, uses `$request->user()->updatePushSubscription(...)`.
- Home `/` redirects guests to login and authenticated users to dashboard.

## 4) Service Provider
- Register the webpush channel to avoid "Driver [webpush] not supported": see [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php#L5-L29).

## 5) Service Worker
- File: [public/sw.js](public/sw.js)
- Minimal handler to display incoming push data:
  ```js
  self.addEventListener('push', event => {
      const data = event.data.json();
      self.registration.showNotification(data.title, {
          body: data.body,
          icon: data.icon,
      });
  });
  ```

## 6) Notifications
- User self-notify (on create) and admin notify (when non-admin creates a blog).
- Files:
  - [app/Notifications/TestPushNotification.php](app/Notifications/TestPushNotification.php)
  - [app/Notifications/AdminBlogCreatedNotification.php](app/Notifications/AdminBlogCreatedNotification.php)

### TestPushNotification (simplified)
```php
public function __construct(?Blog $blog = null) { $this->blog = $blog; }
public function via(object $notifiable): array { return ['webpush']; }
public function toWebPush(object $notifiable, $notification): WebPushMessage {
    $title = $this->blog ? 'New blog created: ' . $this->blog->title : 'Laravel Web Push Test 🎉';
    $body  = $this->blog ? 'Your blog has been published successfully.' : 'This is a test notification.';
    return (new WebPushMessage)->title($title)->body($body)->icon('/icon.png');
}
```

### AdminBlogCreatedNotification (simplified)
```php
public function toWebPush(object $notifiable, $notification): WebPushMessage {
    return (new WebPushMessage)
        ->title('New blog by ' . $this->author->name)
        ->body('"' . $this->blog->title . '" was just published.')
        ->icon('/icon.png')
        ->data(['blog_id' => $this->blog->id, 'author_id' => $this->author->id]);
}
```

## 7) BlogController flow
- On store:
  - Validate, create blog with `user_id` = auth user.
  - Notify the author (`TestPushNotification`).
  - Notify all admins (`is_admin=1`, excluding the author) via `AdminBlogCreatedNotification` using `Notification::send`.
  - Errors in push are caught and logged without blocking the request.
- File: [app/Http/Controllers/BlogController.php](app/Http/Controllers/BlogController.php#L11-L79).

## 8) Dashboard subscription UI
- File: [resources/views/dashboard.blade.php](resources/views/dashboard.blade.php#L1-L97)
- Button triggers subscription:
  ```html
  <button onclick="subscribePush()" class="btn btn-primary">Enable Notifications</button>
  ```
- Script (key bits):
  ```js
  const registration = await navigator.serviceWorker.register('/sw.js');
  const subscription = await registration.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: '{{ config('webpush.vapid.public_key') }}'
  });
  await fetch('/push/subscribe', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify(subscription)
  });
  ```

## 9) Errors Encountered & Fixes
1) **Route [welcome] not defined**
   - Cause: Navbar link pointed to non-existent route after welcome view removed.
   - Fix: Link to `/` instead.

2) **Call to undefined method BlogController::middleware()**
   - Cause: Middleware applied in controller constructor incorrectly.
   - Fix: Move `auth` middleware to routes on the resource.

3) **Push subscription not saved**
   - Cause: `/push/subscribe` not guarded by auth and fetch lacked cookies.
   - Fix: Add `->middleware('auth')`; add `credentials: 'same-origin'` in fetch.

4) **Driver [webpush] not supported**
   - Cause: WebPush channel not registered (package discovery gap).
   - Fix: Register channel in `AppServiceProvider` with `Notification::resolved(... extend('webpush', ...) ...)` and run `php artisan optimize:clear`.

5) **Notification constructor mismatch**
   - Cause: `TestPushNotification` originally took no args but was constructed with `$blog`.
   - Fix: Accept optional Blog and use it in the payload.

## 10) How to test
- Ensure VAPID keys are set in config/webpush and `.env`.
- Log in, open dashboard, click Enable Notifications; confirm `POST /push/subscribe` returns 200.
- Create a blog as a normal user:
  - You receive author notification.
  - Admins with subscriptions receive admin notification.
- Check `storage/logs/laravel.log` if delivery errors occur.

## 11) Admin setup
- Default registration creates `is_admin = 0`.
- Promote an admin via seeder or manual DB update: set `is_admin` to 1 for the chosen user.

## 12) Useful commands
- Clear caches: `php artisan optimize:clear`
- Quick tinker check: `php artisan tinker --execute "dump(App\\Models\\User::withCount('pushSubscriptions')->get(['id','email','push_subscriptions_count']));"`

---
This setup now supports role-based notifications: authors get confirmation; admins get alerted when users publish blogs.
