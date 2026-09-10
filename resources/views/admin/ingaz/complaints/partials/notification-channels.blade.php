<div class="mb-3 mt-3">
    <label class="form-label d-block">إخطار الموظف عند تغيير الإسناد</label>
    @foreach (['database' => 'إشعار داخل النظام', 'sms' => 'رسالة نصية', 'email' => 'بريد إلكتروني'] as $channel => $label)
        <label class="me-3"><input type="checkbox" name="notification_channels[]" value="{{ $channel }}" {{ $channel === 'database' ? 'checked' : '' }}> {{ $label }}</label>
    @endforeach
    <input type="hidden" name="notification_channels_present" value="1">
</div>
