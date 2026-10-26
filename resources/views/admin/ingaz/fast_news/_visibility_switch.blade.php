{{-- مفتاح ظهور الخبر: الحقل المخفي يرسل 0 عند الإطفاء، والمفتاح يرسل 1 عند التشغيل --}}
<div class="col-12">
    <style>
        .fn-switch {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #f9fafb;
            cursor: pointer;
            margin: 0;
            transition: background .2s, border-color .2s;
        }

        .fn-switch:has(input:checked) {
            background: #f0faf4;
            border-color: #c6ebd4;
        }

        .fn-switch__text strong {
            display: block;
            font-size: 14px;
            margin-bottom: 2px;
        }

        .fn-switch__state {
            font-size: 12px;
            color: #6b7280;
        }

        .fn-switch__state--on,
        .fn-switch input:checked ~ .fn-switch__text .fn-switch__state--off {
            display: none;
        }

        .fn-switch input:checked ~ .fn-switch__text .fn-switch__state--on {
            display: inline;
            color: #1e8e4e;
        }

        .fn-switch input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .fn-switch__track {
            position: relative;
            flex-shrink: 0;
            width: 52px;
            height: 28px;
            border-radius: 999px;
            background: #c4c9d1;
            transition: background .2s;
        }

        .fn-switch__track::after {
            content: "";
            position: absolute;
            top: 3px;
            inset-inline-start: 3px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .25);
            transition: inset-inline-start .2s;
        }

        .fn-switch input:checked ~ .fn-switch__track {
            background: #28a745;
        }

        .fn-switch input:checked ~ .fn-switch__track::after {
            inset-inline-start: 27px;
        }

        .fn-switch input:focus-visible ~ .fn-switch__track {
            outline: 2px solid #3b6fd8;
            outline-offset: 2px;
        }
    </style>

    <input type="hidden" name="is_visible" value="0">
    <label class="fn-switch" for="fast-news-visibility">
        <input type="checkbox" id="fast-news-visibility" name="is_visible" value="1" {{ $checked ? 'checked' : '' }}>
        <span class="fn-switch__text">
            <strong>{{ __('web.fast_news_visibility') }}</strong>
            <span class="fn-switch__state fn-switch__state--on">{{ __('web.fast_news_visible') }} — {{ __('web.fast_news_switch_on_hint') }}</span>
            <span class="fn-switch__state fn-switch__state--off">{{ __('web.fast_news_hidden') }} — {{ __('web.fast_news_switch_off_hint') }}</span>
        </span>
        <span class="fn-switch__track"></span>
    </label>
</div>
