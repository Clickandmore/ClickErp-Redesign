@extends('agent.layout')

@php
    $isAr = app()->getLocale() === 'ar';
@endphp

@section('page-title', $isAr ? 'الإشعارات' : 'Notifications')

@section('page-action')
    @if($notifications->count() > 0)
        <form action="{{ route('agent.notifications.mark-all-read') }}" method="POST" style="display: inline-block;">
            @csrf
            <button type="submit" class="agent-button" style="background: var(--agent-muted);">
                <i class="fa fa-check-double"></i>
                <span>{{ $isAr ? 'تحديد الكل كمقروء' : 'Mark All as Read' }}</span>
            </button>
        </form>
    @endif
@endsection

@section('content')
    <section class="agent-section">
        <div style="display: flex; flex-direction: column; gap: 10px;">
            @forelse($notifications as $notification)
                <div style="padding: 15px; border-radius: 8px; border: 1px solid var(--agent-line); background: {{ $notification->read_at ? 'var(--agent-panel)' : '#f0f9ff' }}; display: flex; justify-content: space-between; align-items: flex-start; gap: 15px;">
                    <div>
                        <h4 style="margin: 0 0 5px 0; color: var(--agent-primary-dark); font-size: 16px;">
                            {{ $notification->title ?? ($isAr ? 'إشعار جديد' : 'New Notification') }}
                        </h4>
                        <p style="margin: 0; color: var(--agent-ink); font-size: 14px;">
                            {{ $notification->message ?? '' }}
                        </p>
                        <small style="color: var(--agent-muted); display: block; margin-top: 8px; font-size: 12px;">
                            <i class="fa fa-clock"></i> {{ $notification->created_at->diffForHumans() }}
                        </small>
                    </div>
                    
                    @if(!$notification->read_at)
                        <form action="{{ route('agent.notifications.read', $notification->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="agent-button" style="padding: 4px 10px; min-height: 30px; font-size: 12px; background: transparent; color: var(--agent-primary); border: 1px solid var(--agent-primary);">
                                <i class="fa fa-check"></i> {{ $isAr ? 'مقروء' : 'Mark Read' }}
                            </button>
                        </form>
                    @endif
                </div>
            @empty
                <div style="padding: 30px; text-align: center; color: var(--agent-muted);">
                    <i class="fa fa-bell-slash" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                    <p style="margin: 0;">{{ $isAr ? 'لا توجد إشعارات في الوقت الحالي.' : 'No notifications at the moment.' }}</p>
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="agent-pagination" style="margin-top: 20px;">
                {{ $notifications->links() }}
            </div>
        @endif
    </section>
@endsection
