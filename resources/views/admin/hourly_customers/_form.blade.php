{{-- نموذج عميل الساعات (إنشاء وتعديل): الحقول المشتركة تُكتب في users، وبقية الحقول في hourly_customer_profiles. --}}
@php
    $c = fn (string $key, array $replace = []) => __('hourly_customers.'.$key, $replace);
    $v = fn (string $key, $fallback = null) => old($key, $fallback);
    $type = $v('customer_type', $profile->customer_type ?: 'individual');
    $birth = $v('birthdate', $customer?->birthdate ? \Carbon\Carbon::parse($customer->birthdate)->format('Y-m-d') : null);
    $disabled = $readonly ? 'disabled' : '';
    $invalid = fn (string $key) => $errors->has($key) ? 'is-invalid' : '';
    $error = fn (string $key) => $errors->has($key) ? '<div class="invalid-feedback d-block">'.e($errors->first($key)).'</div>' : '';
@endphp
<form method="POST" action="{{ $action }}" class="hc-form" id="hc-customer-form" novalidate>
    @csrf
    @if($method !== 'POST') @method($method) @endif
    <div class="hc-note"><i class="fa fa-circle-info mt-1"></i><div>{{ $c('form.shared_note') }}</div></div>

    <fieldset {{ $disabled }}>
        <legend><i class="fa fa-id-card"></i> {{ $c('form.identity') }}</legend>
        <div class="hc-row">
            <div class="hc-field">
                <span class="hc-label">{{ $c('form.customer_type') }}</span>
                <div class="hc-seg" role="radiogroup" aria-label="{{ $c('form.customer_type') }}">
                    @foreach(['individual' => 'fa-user', 'company' => 'fa-building'] as $key => $icon)
                        <label><input type="radio" name="customer_type" value="{{ $key }}" data-customer-type {{ $type === $key ? 'checked' : '' }}><span><i class="fa {{ $icon }}"></i> {{ $c('types.'.$key) }}</span></label>
                    @endforeach
                </div>
            </div>
            <div class="hc-field" data-only="individual">
                <label for="hc-first-name">{{ $c('form.first_name') }} *</label>
                <input class="form-control {{ $invalid('first_name') }}" id="hc-first-name" name="first_name" maxlength="100" value="{{ $v('first_name', $profile->first_name) }}">
                {!! $error('first_name') !!}
            </div>
            <div class="hc-field" data-only="individual">
                <label for="hc-last-name">{{ $c('form.last_name') }} *</label>
                <input class="form-control {{ $invalid('last_name') }}" id="hc-last-name" name="last_name" maxlength="100" value="{{ $v('last_name', $profile->last_name) }}">
                {!! $error('last_name') !!}
            </div>
            <div class="hc-field" data-only="company">
                <label for="hc-company-name">{{ $c('form.company_name') }} *</label>
                <input class="form-control {{ $invalid('company_name') }}" id="hc-company-name" name="company_name" maxlength="200" value="{{ $v('company_name', $profile->company_name ?? ($profile->exists ? null : $customer?->name)) }}">
                {!! $error('company_name') !!}
            </div>
            <div class="hc-field" data-only="company">
                <label for="hc-company-id">{{ $c('form.company_id') }}</label>
                <input class="form-control {{ $invalid('company_id') }}" id="hc-company-id" name="company_id" maxlength="100" value="{{ $v('company_id', $profile->company_id) }}">
                {!! $error('company_id') !!}
            </div>
        </div>
        <div class="hc-row mt-3">
            <div class="hc-field">
                <label for="hc-identity-type">{{ $c('form.identity_type') }}</label>
                <select class="form-select" id="hc-identity-type" name="identity_type">
                    <option value="">{{ $c('form.choose') }}</option>
                    @foreach(\App\Models\Hourly\HourlyCustomerProfile::IDENTITY_TYPES as $key)<option value="{{ $key }}" {{ $v('identity_type', $profile->identity_type) === $key ? 'selected' : '' }}>{{ $c('identity_types.'.$key) }}</option>@endforeach
                </select>
            </div>
            <div class="hc-field">
                <label for="hc-national">{{ $c('form.national_num') }}</label>
                <input class="form-control {{ $invalid('national_num') }}" id="hc-national" name="national_num" maxlength="50" dir="ltr" value="{{ $v('national_num', $customer?->national_num) }}">
                {!! $error('national_num') !!}
            </div>
            <div class="hc-field" data-only="individual">
                <label for="hc-birthdate">{{ $c('form.birthdate') }}</label>
                <div class="hc-inline">
                    <input class="form-control {{ $invalid('birthdate') }}" type="date" id="hc-birthdate" name="birthdate" max="{{ today()->toDateString() }}" value="{{ $birth }}">
                    <span class="hc-age" id="hc-age" aria-live="polite">{{ $birth ? $c('form.age', ['age' => \Carbon\Carbon::parse($birth)->age]) : '—' }}</span>
                </div>
                {!! $error('birthdate') !!}
            </div>
        </div>
    </fieldset>

    <fieldset {{ $disabled }}>
        <legend><i class="fa fa-phone"></i> {{ $c('form.contact') }}</legend>
        <div class="hc-row">
            <div class="hc-field">
                <label for="hc-phone">{{ $c('form.phone') }} *</label>
                <div class="input-group" dir="ltr"><span class="input-group-text">+966</span><input class="form-control {{ $invalid('phone') }}" id="hc-phone" name="phone" inputmode="tel" maxlength="40" value="{{ $v('phone', $customer?->phone) }}" placeholder="5XXXXXXXX"></div>
                <div class="form-text">{{ $customer ? $c('form.phone_keep_hint') : $c('form.phone_hint') }}</div>
                {!! $error('phone') !!}
            </div>
            <div class="hc-field">
                <label for="hc-phone2">{{ $c('form.phone2') }}</label>
                <input class="form-control" id="hc-phone2" name="phone2" maxlength="50" dir="ltr" value="{{ $v('phone2', $customer?->phone2) }}">
            </div>
            <div class="hc-field">
                <label for="hc-email">{{ $c('form.email') }}</label>
                <input class="form-control {{ $invalid('email') }}" type="email" id="hc-email" name="email" maxlength="255" dir="ltr" value="{{ $v('email', $customer?->email) }}">
                {!! $error('email') !!}
            </div>
        </div>
    </fieldset>

    <fieldset {{ $disabled }}>
        <legend><i class="fa fa-clock"></i> {{ $c('form.hourly') }}</legend>
        <div class="hc-row">
            <div class="hc-field">
                <label for="hc-language">{{ $c('form.language') }}</label>
                <select class="form-select" id="hc-language" name="language">
                    @foreach(\App\Models\Hourly\HourlyCustomerProfile::LANGUAGES as $key)<option value="{{ $key }}" {{ $v('language', $profile->language ?: 'ar') === $key ? 'selected' : '' }}>{{ $c('languages.'.$key) }}</option>@endforeach
                </select>
                <div class="form-text"><i class="fa fa-circle-info"></i> {{ $c('form.language_hint') }}</div>
            </div>
            <div class="hc-field">
                <label for="hc-marketing">{{ $c('form.marketing_source') }}</label>
                <select class="form-select" id="hc-marketing" name="marketing_source">
                    <option value="">{{ $c('none') }}</option>
                    @foreach(\App\Models\Hourly\HourlyCustomerProfile::MARKETING_SOURCES as $key)<option value="{{ $key }}" {{ $v('marketing_source', $profile->marketing_source) === $key ? 'selected' : '' }}>{{ $c('marketing.'.$key) }}</option>@endforeach
                </select>
            </div>
            <div class="hc-field">
                <label for="hc-iban">{{ $c('form.iban') }}</label>
                <input class="form-control {{ $invalid('iban') }}" id="hc-iban" name="iban" maxlength="40" dir="ltr" placeholder="SA0000000000000000000000" value="{{ $v('iban', $profile->iban) }}">
                <div class="form-text"><i class="fa fa-circle-info"></i> {{ $c('form.iban_hint') }}</div>
                {!! $error('iban') !!}
            </div>
            <div class="hc-field">
                <label for="hc-group">{{ $c('form.group') }}</label>
                <select class="form-select {{ $invalid('group_id') }}" id="hc-group" name="group_id">
                    <option value="">{{ $c('none') }}</option>
                    @foreach($groups as $group)
                        {{-- المجموعة غير النشطة تظهر فقط إن كانت مختارة للعميل حاليًا --}}
                        @if($group->is_active || (string) $v('group_id', $profile->group_id) === (string) $group->id)
                            <option value="{{ $group->id }}" {{ (string) $v('group_id', $profile->group_id) === (string) $group->id ? 'selected' : '' }}>{{ $group->name }}{{ $group->discount_percent ? ' · '.rtrim(rtrim($group->discount_percent, '0'), '.').'%' : '' }}</option>
                        @endif
                    @endforeach
                </select>
                {!! $error('group_id') !!}
            </div>
            <div class="hc-field">
                <label for="hc-payment-term">{{ $c('form.payment_term') }}</label>
                <select class="form-select" id="hc-payment-term" name="payment_term">
                    <option value="">{{ $c('payment_terms.unset') }}</option>
                    @foreach(\App\Models\Hourly\HourlyCustomerProfile::PAYMENT_TERMS as $key)<option value="{{ $key }}" {{ $v('payment_term', $profile->payment_term) === $key ? 'selected' : '' }}>{{ $c('payment_terms.'.$key) }}</option>@endforeach
                </select>
                <div class="form-text"><i class="fa fa-circle-info"></i> {{ $c('form.payment_term_hint') }}</div>
            </div>
        </div>
        <div class="hc-field mt-3">
            <label for="hc-note">{{ $c('form.note') }}</label>
            <textarea class="form-control" id="hc-note" name="note" rows="3" maxlength="2000">{{ $v('note', $profile->note) }}</textarea>
        </div>
    </fieldset>

    @unless($customer)
        <fieldset>
            <legend><i class="fa fa-key"></i> {{ $c('form.security') }}</legend>
            <div class="hc-row">
                <div class="hc-field">
                    <label for="hc-password">{{ $c('form.password') }}</label>
                    <input class="form-control {{ $invalid('password') }}" type="password" id="hc-password" name="password" minlength="8" maxlength="100" autocomplete="new-password">
                    {!! $error('password') !!}
                </div>
            </div>
            <div class="form-text"><i class="fa fa-circle-info"></i> {{ $c('form.password_hint') }}</div>
        </fieldset>
    @else
        <fieldset {{ $disabled }}>
            <legend><i class="fa fa-key"></i> {{ $c('form.security') }}</legend>
            <div class="hc-row">
                <div class="hc-field">
                    <label for="hc-new-password">{{ $c('form.new_password') }}</label>
                    <input class="form-control {{ $invalid('new_password') }}" type="password" id="hc-new-password" name="new_password" minlength="8" maxlength="100" autocomplete="new-password">
                    {!! $error('new_password') !!}
                </div>
                <div class="hc-field">
                    <label for="hc-new-password-confirmation">{{ $c('form.new_password_confirmation') }}</label>
                    <input class="form-control" type="password" id="hc-new-password-confirmation" name="new_password_confirmation" minlength="8" maxlength="100" autocomplete="new-password">
                </div>
            </div>
            <div class="form-text"><i class="fa fa-circle-info"></i> {{ $c('form.new_password_hint') }}</div>
        </fieldset>
    @endunless

    @unless($readonly)
        <div class="hc-actions">
            <button class="hc-btn is-primary"><i class="fa fa-floppy-disk"></i> {{ $customer ? $c('form.save') : $c('form.create') }}</button>
            <a class="hc-btn" href="{{ $customer ? route('admin.hourly-customers.show', $customer->id) : route('admin.hourly-customers.index') }}">{{ $c('form.cancel') }}</a>
        </div>
    @endunless
</form>
