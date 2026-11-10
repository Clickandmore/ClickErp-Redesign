@if ($statusTemplate->channel === 'whatsapp' || !empty($statusTemplate->clickchat_binding))
    @php($metaBadge = \App\Services\WhatsApp\ClickChatTemplateStatus::describe($statusTemplate->clickchat_binding, $clickChatStatus ?? []))
    <div class="mt-2">
        <span class="badge bg-{{ $metaBadge['color'] }}" title="{{ $metaBadge['detail'] }}">{{ $metaBadge['label'] }}</span>
        @if (!empty($showStatusDetail) || in_array($metaBadge['state'], ['api_outdated', 'auth_error', 'forbidden', 'api_missing', 'invalid_sender', 'sender_required', 'unavailable'], true))
            <div class="small text-muted mt-1">{{ $metaBadge['detail'] }}</div>
        @endif
    </div>
@endif
