@php
    $whatsappNumber = isset($settings) ? ($settings->whatsapp ?? null) : null;
    $cleanWhatsappNumber = $whatsappNumber ? preg_replace('/\D+/', '', $whatsappNumber) : null;
    if ($cleanWhatsappNumber && str_starts_with($cleanWhatsappNumber, '00')) {
        $cleanWhatsappNumber = substr($cleanWhatsappNumber, 2);
    }
    if ($cleanWhatsappNumber && strlen($cleanWhatsappNumber) === 10 && str_starts_with($cleanWhatsappNumber, '05')) {
        $cleanWhatsappNumber = '966' . substr($cleanWhatsappNumber, 1);
    } elseif ($cleanWhatsappNumber && strlen($cleanWhatsappNumber) === 9 && str_starts_with($cleanWhatsappNumber, '5')) {
        $cleanWhatsappNumber = '966' . $cleanWhatsappNumber;
    }
    $whatsappMessage = __('مرحباً، أحتاج إلى مساعدة.');
    $whatsappUrl = $cleanWhatsappNumber
        ? 'https://wa.me/' . $cleanWhatsappNumber . '?text=' . rawurlencode($whatsappMessage)
        : null;
@endphp

@if ($whatsappUrl)
    <div class="site-whatsapp-float is-open" id="siteWhatsappFloat">
        <button type="button" class="site-whatsapp-float__button" id="siteWhatsappToggle"
            aria-label="{{ __('تواصل عبر واتساب') }}">
            <i class="fab fa-whatsapp"></i>
        </button>
        <div class="site-whatsapp-float__message" id="siteWhatsappMessage" data-whatsapp-number="{{ $cleanWhatsappNumber }}">
            <div class="site-whatsapp-float__head">
                <div class="site-whatsapp-float__avatar">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <div>
                    <strong>{{ __('أهلاً بك') }}</strong>
                    <span>{{ __('كيف نقدر نخدمك؟') }}</span>
                </div>
                <button type="button" class="site-whatsapp-float__close" id="siteWhatsappClose"
                    aria-label="{{ __('إغلاق') }}">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <p class="site-whatsapp-float__intro">
                {{ __('اكتب رسالتك وسنحوّلك إلى واتساب لإرسالها مباشرة لفريقنا.') }}
            </p>
            <form class="site-whatsapp-float__form" id="siteWhatsappForm">
                <textarea id="siteWhatsappText" rows="3" maxlength="300"
                    placeholder="{{ __('اكتب رسالتك هنا...') }}">{{ $whatsappMessage }}</textarea>
                <button type="submit" class="site-whatsapp-float__cta">
                    <i class="fab fa-whatsapp"></i>
                    <span>{{ __('إرسال عبر واتساب') }}</span>
                </button>
            </form>
        </div>
    </div>

    <style>
        .site-whatsapp-float {
            position: fixed;
            right: 22px;
            bottom: 24px;
            z-index: 1040;
            display: flex;
            flex-direction: row-reverse;
            align-items: flex-end;
            gap: 0;
            font-family: inherit;
            direction: ltr;
        }

        .site-whatsapp-float__message {
            position: relative;
            width: min(340px, calc(100vw - 44px));
            padding: 0;
            border: 1px solid rgba(18, 140, 126, 0.16);
            border-radius: 8px;
            background: #ffffff;
            box-shadow: 0 22px 55px rgba(15, 23, 42, 0.18);
            color: #17233c;
            direction: rtl;
            display: none;
            transform: translateX(12px) translateY(8px);
            opacity: 0;
            overflow: visible;
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .site-whatsapp-float__message::after {
            content: "";
            position: absolute;
            right: -10px;
            bottom: 22px;
            width: 18px;
            height: 18px;
            background: #ffffff;
            border-top: 1px solid rgba(18, 140, 126, 0.16);
            border-right: 1px solid rgba(18, 140, 126, 0.16);
            transform: rotate(45deg);
        }

        .site-whatsapp-float.is-open .site-whatsapp-float__message {
            display: block;
            transform: translateX(0) translateY(0);
            opacity: 1;
        }

        .site-whatsapp-float__head {
            min-height: 78px;
            padding: 16px 52px 16px 16px;
            background: linear-gradient(135deg, #128c7e 0%, #075e54 100%);
            border-radius: 8px 8px 0 0;
            color: #ffffff;
            display: grid;
            grid-template-columns: auto 1fr;
            align-items: center;
            gap: 12px;
        }

        .site-whatsapp-float__avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.16);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.18);
        }

        .site-whatsapp-float__message strong {
            display: block;
            margin-bottom: 2px;
            font-size: 16px;
            font-weight: 800;
            line-height: 1.4;
        }

        .site-whatsapp-float__message span {
            display: block;
            color: rgba(255, 255, 255, 0.84);
            font-size: 14px;
            line-height: 1.6;
        }

        .site-whatsapp-float__close {
            position: absolute;
            top: 16px;
            right: 14px;
            width: 28px;
            height: 28px;
            border: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.14);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .site-whatsapp-float__intro {
            margin: 0;
            padding: 16px 16px 10px;
            color: #5c667a;
            font-size: 14px;
            line-height: 1.8;
        }

        .site-whatsapp-float__form {
            padding: 0 16px 16px;
        }

        .site-whatsapp-float__form textarea {
            width: 100%;
            min-height: 94px;
            padding: 12px 14px;
            border: 1px solid #dde4ee;
            border-radius: 8px;
            background: #f8fafc;
            color: #17233c;
            resize: vertical;
            outline: none;
            font: inherit;
            font-size: 14px;
            line-height: 1.7;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .site-whatsapp-float__form textarea:focus {
            border-color: #25d366;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.14);
        }

        .site-whatsapp-float__cta {
            width: 100%;
            margin-top: 12px;
            min-height: 42px;
            padding: 10px 18px;
            border: 0;
            border-radius: 8px;
            background: #25d366;
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 10px 24px rgba(37, 211, 102, 0.28);
        }

        .site-whatsapp-float__cta:hover,
        .site-whatsapp-float__button:hover {
            color: #ffffff;
            text-decoration: none;
        }

        .site-whatsapp-float__button {
            position: relative;
            width: 62px;
            height: 62px;
            border-radius: 50%;
            border: 0;
            background: #25d366;
            color: #ffffff;
            box-shadow: 0 14px 34px rgba(37, 211, 102, 0.38);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            text-decoration: none;
            cursor: pointer;
            flex: 0 0 62px;
            margin-inline-start: 14px;
        }

        .site-whatsapp-float__button::before {
            content: "";
            position: absolute;
            width: 62px;
            height: 62px;
            border-radius: 50%;
            background: rgba(37, 211, 102, 0.28);
            animation: siteWhatsappPulse 1.8s ease-out infinite;
            z-index: -1;
        }

        @keyframes siteWhatsappPulse {
            0% {
                transform: scale(1);
                opacity: 0.9;
            }

            100% {
                transform: scale(1.45);
                opacity: 0;
            }
        }

        @media (max-width: 575.98px) {
            .site-whatsapp-float {
                right: 14px;
                bottom: 16px;
                left: 14px;
                flex-direction: column-reverse;
                align-items: flex-end;
                gap: 12px;
            }

            .site-whatsapp-float__message {
                width: 100%;
                max-height: calc(100vh - 108px);
                transform: translateY(10px);
            }

            .site-whatsapp-float__message::after {
                right: 19px;
                bottom: -9px;
                border-top: 0;
                border-right: 1px solid rgba(18, 140, 126, 0.16);
                border-bottom: 1px solid rgba(18, 140, 126, 0.16);
            }

            .site-whatsapp-float.is-open .site-whatsapp-float__message {
                transform: translateY(0);
            }

            .site-whatsapp-float__head {
                min-height: 70px;
                padding: 14px 50px 14px 14px;
            }

            .site-whatsapp-float__avatar {
                width: 40px;
                height: 40px;
                font-size: 22px;
            }

            .site-whatsapp-float__intro {
                padding: 14px 14px 8px;
                font-size: 13px;
                line-height: 1.7;
            }

            .site-whatsapp-float__form {
                padding: 0 14px 14px;
            }

            .site-whatsapp-float__form textarea {
                min-height: 82px;
                max-height: 140px;
                padding: 11px 12px;
                font-size: 13px;
            }

            .site-whatsapp-float__cta {
                min-height: 44px;
            }

            .site-whatsapp-float__button {
                width: 56px;
                height: 56px;
                font-size: 29px;
                flex-basis: 56px;
                margin-inline-start: 0;
            }

            .site-whatsapp-float__button::before {
                width: 56px;
                height: 56px;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const whatsappFloat = document.getElementById('siteWhatsappFloat');
            const closeButton = document.getElementById('siteWhatsappClose');
            const whatsappMessage = document.getElementById('siteWhatsappMessage');
            const whatsappForm = document.getElementById('siteWhatsappForm');
            const whatsappText = document.getElementById('siteWhatsappText');
            const whatsappToggle = document.getElementById('siteWhatsappToggle');

            if (!whatsappFloat || !closeButton || !whatsappMessage || !whatsappForm || !whatsappText || !whatsappToggle) {
                return;
            }

            closeButton.addEventListener('click', function () {
                whatsappFloat.classList.remove('is-open');
            });

            whatsappToggle.addEventListener('click', function () {
                whatsappFloat.classList.add('is-open');
                whatsappText.focus();
            });

            whatsappForm.addEventListener('submit', function (event) {
                event.preventDefault();

                const whatsappNumber = whatsappMessage.dataset.whatsappNumber;
                const message = whatsappText.value.trim() || '{{ $whatsappMessage }}';

                window.open('https://wa.me/' + whatsappNumber + '?text=' + encodeURIComponent(message), '_blank', 'noopener');
            });
        });
    </script>
@endif
