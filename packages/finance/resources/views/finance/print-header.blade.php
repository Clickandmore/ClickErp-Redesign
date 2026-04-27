<style>
    header {
        padding: 12px;

        .tnavbar {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;

            .logo {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 4px;

                img {
                    object-fit: contain;
                    height: 100px;
                    width: 100px;
                    aspect-ratio: 1 / 1;
                    margin: auto;
                }

                p {
                    font-weight: 500;
                }
            }

            .Name {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 4px;
                text-align: center;

                h1 {
                    font-size: 16px;
                    text-transform: uppercase;
                    font-weight: bold;
                    color: #1e9226;
                }

                p {
                    font-weight: 500;
                }
            }
        }

        .line {
            border: 1px solid #1e9226;
        }
    }

    .line_hr {
        border: 1px solid #1e9226;
    }
</style>
<header>
    <div class="tnavbar">
        <div class="logo">
            @if(isset($setting_web))
                <img src="{{ showFile(@$setting_web->logo_header) }}" alt="logo" style="object-fit: contain"/>
                <p>الرقم الضريبي : {{ @$setting_web->tax_number }}</p>
            @endif
        </div>
        <div class="Name Ar">
            @if(isset($setting_web))
                <h1> {{ @$setting_web->translate('ar')->website_name }} </h1>
                <h1> {{ @$setting_web->translate('en')->website_name }} </h1>
                <p>{{ __('crm.commercial_registration_number', [], 'Arabic-ar') }} :
                    {{ @$setting_web->commercial_register }}
                </p>
            @endif
            {{-- <p>الهاتف : {{ @$setting_web->phone }} </p> --}}
        </div>
        {{-- <div class="Name En">
            <p> {{ @$setting_web->phone }} : Phone</p>
            <p> {{ @$setting_web->tax_number }} : Tax Number </p>
            <p> commercial registration number : {{ @$setting_web->commercial_register }} </p>
        </div> --}}
    </div>
</header>
{{-- <div>
    <hr class="line_hr" />
</div> --}}
@if (@$from_date or @$to_date)
    <h4 class="text-center">
        {{ __('hr.report_from', [], 'Arabic-ar') }} {{ @$from_date }} {{ __('hr.to', [], 'Arabic-ar') }}
        {{ @$to_date }}
    </h4>
@endif
{{-- <header> --}}
{{--    <nav> --}}
{{--        <div class="tnavbar"> --}}
{{--            <div class="Name En"> --}}
{{--                <h2>Al-Faisal Transport Company</h2> --}}
{{--                <p>For selling Toyta spare Parts</p> --}}
{{--            </div> --}}
{{--            <div class="logo"> --}}
{{--                <img src="{{ asset('assets/images/logo-40.png') }}" alt="logo" width="250" height="250" /> --}}
{{--            </div> --}}
{{--            <div class="Name Ar"> --}}
{{--                <h2>شركة الفيصل للنقل</h2> --}}
{{--                <p>لبيع قطع غيـار تويوتـا</p> --}}
{{--            </div> --}}
{{--        </div> --}}
{{--    </nav> --}}
{{--    <br> --}}
{{--    <br> --}}
{{--    <hr class="line" /> --}}
{{-- </header> --}}
