@extends('layouts.app')

@section('title', 'Dashboard')

@section('extra-css')
<style>
    .dashboard-container {
        max-width: 900px;
        width: 100%;
    }
    .welcome-card {
        background: white;
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
    }
    .user-info {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-top: 30px;
    }
    .info-box {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        border-left: 4px solid #667eea;
    }
    .info-label {
        font-size: 0.85rem;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .info-value {
        font-size: 1.3rem;
        font-weight: bold;
        color: #333;
        margin-top: 5px;
    }
</style>
@endsection

@section('content')
<div class="dashboard-container">
    <div class="welcome-card">
        <h1 class="mb-2">Welcome, {{ Auth::user()->name }}! 👋</h1>
        <p class="text-muted">You are successfully logged in to your account.</p>

        <div class="user-info">
            <div class="info-box">
                <div class="info-label">Email Address</div>
                <div class="info-value">{{ Auth::user()->email }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Member Since</div>
                <div class="info-value">{{ Auth::user()->created_at->format('M d, Y') }}</div>
            </div>
        </div>

        <div class="mt-5">
            <h5>Quick Actions</h5>
            <div class="d-grid gap-2 d-sm-flex">
                <a href="{{ route('blogs.index') }}" class="btn btn-primary btn-lg">
                    View Blogs
                </a>
                <a href="{{ route('blogs.create') }}" class="btn btn-outline-primary btn-lg">
                    Create New Blog
                </a>
            </div>
        </div>

        <div class="mt-4">
            <h5>Notifications</h5>
            <p class="text-muted mb-2">Enable browser push notifications for updates.</p>
            <button onclick="subscribePush()" class="btn btn-primary">Enable Notifications</button>
            <div id="push-status" class="small text-muted mt-2"></div>
        </div>
    </div>
</div>
@endsection

@section('extra-js')
<script>
    async function subscribePush() {
        const statusEl = document.getElementById('push-status');

        try {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                statusEl.textContent = 'Permission denied or dismissed.';
                return;
            }

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

            statusEl.textContent = 'Notifications enabled.';
        } catch (error) {
            console.error('Push subscription failed', error);
            statusEl.textContent = 'Unable to enable notifications.';
        }
    }
</script>
@endsection
