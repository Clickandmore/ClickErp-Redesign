@extends('admin.layout.indexs.index')

@section('page-title')
    {{helperTrans("ingaz.about_us")}}
@endsection

@section('content')

    @php
        $route ="";
        $method = "POST";
        if(isset($about)){
            $route = route('about-us.update', $about->id);
            $method = "PUT";
         }
        else
        {
            $route = route('about-us.store');

        }

        $defaultContent = [
            'ar' => [
                'title' => 'من نحن',
                'description' => 'بلاتينيوم الشرقية للاستقدام مرخصة من وزارة العمل والموارد البشرية لخدمات التوسط في استقدام العمالة الوافدة ومقرها المنطقة الشرقية في المملكة العربية السعودية، وحرصاً منا على تقديم خدمات متميزة تم إطلاق منصة رقمية متكاملة لتبسيط عملية الاستقدام وجعلها أكثر كفاءة وسهولة لعملائنا بما يتوافق مع رؤية المملكة.',
                'mission' => '<p><strong>((الإنسانية))</strong> نفتخر بعملنا كونه يسهم بتوفير موارد ودخل جيّد لأسرة وفرد بحاجة لهذه الموارد لدعم أسرته وبناء مستقبله، وبالمقابل توفير سبل الراحة للأسرة السعودية والمقيمة في وطننا المعطاء.</p><p>((( احتياجاتكم أولوياتنا ))).</p>',
                'vision' => '<p>أن نكون الخيار الأول للاستقدام في المملكة العربية السعودية، وذلك بالتفرد بتقديم أجود الخدمات وانتقاء أجود الكفاءات، والحرص على راحة العملاء وحفظ حقوقهم، ونحقق ذلك بعد اكتساب ثقة العملاء وتوصيتهم بنا لمن حولهم.</p>',
                'value' => '<p>قيمنا تركز على رضى العميل، حيث نولي الأولوية للجودة والكمال في كل خدماتنا. نحن روح الفريق لتحقيق النجاح، نحرص على السرعة والإنجاز لتلبية احتياجات عملائنا، ونعتبر النزاهة والشفافية أساساً في كل تعاملاتنا.</p>',
            ],
            'en' => [
                'title' => 'About Us',
                'description' => 'Platinum Eastern Recruitment is licensed by the Ministry of Human Resources and Social Development to mediate the recruitment of domestic workers. We are headquartered in the Eastern Province of the Kingdom of Saudi Arabia, and to deliver exceptional services we launched an integrated digital platform that simplifies recruitment and makes it more efficient and convenient for our clients in line with the Kingdom’s vision.',
                'mission' => '<p><strong>((Humanity))</strong> We are proud that our work helps provide sustainable income for individuals and families who depend on it to build their future, while ensuring comfort for Saudi and resident families in our generous nation.</p><p>((( Your needs are our priority ))).</p>',
                'vision' => '<p>To be the first choice for recruitment in the Kingdom of Saudi Arabia by delivering outstanding services, selecting the best talents, safeguarding customers’ comfort and rights, and continuously earning their trust and referrals.</p>',
                'value' => '<p>Our values focus on customer satisfaction. We prioritise quality and excellence in every service, embrace teamwork to achieve success, act with speed and efficiency, and consider integrity and transparency the foundation of all our dealings.</p>',
            ],
        ];
    @endphp

    <form class="row from-submit-global" action="{{ $route}}" method="post" enctype="multipart/form-data">
        @csrf
        @if(isset($about))
            @method('PUT')
        @endif
        @foreach (config('translatable.locales') as $locale)
            @php
                $translation = $about?->translate($locale);
                $defaults = $defaultContent[$locale] ?? [];
            @endphp
            <div class="col-6">
                <label class="form-label">
                    {{ helperTrans('ingaz.title') }}
                    ({{ helperTrans('ingaz.'.$locale) }})
                </label>
                <input
                    type="text"
                    name="title:{{ $locale }}"
                    class="form-control"
                    value="{{ old('title:'.$locale, $translation->title ?? ($defaults['title'] ?? '')) }}"
                    data-validation="required"
                >
            </div>
        @endforeach
        @foreach (config('translatable.locales') as $locale)
            @php
                $translation = $about?->translate($locale);
                $defaults = $defaultContent[$locale] ?? [];
            @endphp
            <div class="col-6">
                <label class="form-label">
                    {{ helperTrans('ingaz.about') }}
                    ({{ helperTrans('ingaz.'.$locale) }})
                </label>
                <textarea
                    name="description:{{ $locale }}"
                    class="form-control mytextarea"
                    rows="10"
                    data-validation="required"
                >{!! old('description:'.$locale, $translation->description ?? ($defaults['description'] ?? '')) !!}</textarea>
            </div>

        @endforeach

        @foreach (config('translatable.locales') as $locale)
            @php
                $translation = $about?->translate($locale);
                $defaults = $defaultContent[$locale] ?? [];
            @endphp
            <div class="col-6">
                <label class="form-label">
                    {{ helperTrans('ingaz.mission') }}
                    ({{ helperTrans('ingaz.'.$locale) }})
                </label>
                <textarea
                    name="mission:{{ $locale }}"
                    class="form-control mytextarea"
                    data-validation="required"
                >{!! old('mission:'.$locale, $translation->mission ?? ($defaults['mission'] ?? '')) !!}</textarea>
            </div>

        @endforeach

        @foreach (config('translatable.locales') as $locale)
            @php
                $translation = $about?->translate($locale);
                $defaults = $defaultContent[$locale] ?? [];
            @endphp
            <div class="col-6">
                <label class="form-label">
                    {{ helperTrans('ingaz.vision') }}
                    ({{ helperTrans('ingaz.'.$locale) }})
                </label>
                <textarea
                    name="vision:{{ $locale }}"
                    class="form-control mytextarea"
                    data-validation="required"
                >{!! old('vision:'.$locale, $translation->vision ?? ($defaults['vision'] ?? '')) !!}</textarea>
            </div>

        @endforeach
        @foreach (config('translatable.locales') as $locale)
            @php
                $translation = $about?->translate($locale);
                $defaults = $defaultContent[$locale] ?? [];
            @endphp
            <div class="col-6">
                <label class="form-label">
                    {{ helperTrans('ingaz.value') }}
                    ({{ helperTrans('ingaz.'.$locale) }})
                </label>
                <textarea
                    name="value:{{ $locale }}"
                    class="form-control mytextarea"
                    data-validation="required"
                >{!! old('value:'.$locale, $translation->value ?? ($defaults['value'] ?? '')) !!}</textarea>
            </div>

        @endforeach
        <div class="col-6">
            <label for="TextInput" class="form-label">{{__('ingaz.image')}} </label>
            <input type="file" data-default-file="{{isset($abouts) ? showFile($abouts->image) : ''}}"
                   name="image" class="dropify">
        </div>
        <div class="col-6">
            <label for="TextInput" class="form-label">{{__('ingaz.our_vision_image')}} </label>
            <input type="file" data-default-file="{{isset($abouts) ? showFile($abouts->image_vision) : ''}}"
                   name="image_vision" class="dropify">
        </div>
        <div class="col-6">
            <label for="TextInput" class="form-label">{{__('ingaz.our_message_image')}} </label>
            <input type="file" data-default-file="{{isset($abouts) ? showFile($abouts->image_message) : ''}}"
                   name="image_message" class="dropify">
        </div>
        <div class="col-6">
            <label for="TextInput" class="form-label">{{__('ingaz.our_value_image')}} </label>
            <input type="file" data-default-file="{{isset($abouts) ? showFile($abouts->image_value) : ''}}"
                   name="image_value" class="dropify">
        </div>
        <div class="mb-3 row">
            <div class="col-sm-12 text-center">
                <button type="submit" class="btn btn-primary">{{ __('admin.save') }}</button>
            </div>
        </div>
    </form>

@endsection

@section('js')
    @include('components.ckeditor')

    <script src="{{asset('admin')}}/assets/js/tinymce.min.js"></script>
    <script>
        $(document).ready(function () {
            tinymce.init({
                selector: '.mytextarea',
                plugins: 'ai tinycomments mentions anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount checklist mediaembed casechange export formatpainter pageembed permanentpen footnotes advtemplate advtable advcode editimage tableofcontents mergetags powerpaste tinymcespellchecker autocorrect a11ychecker typography inlinecss textcolor colorpicker',
                toolbar: 'undo redo | formatselect | fontselect fontsizeselect | forecolor backcolor | bold italic underline strikethrough | align lineheight | tinycomments | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
            });
        });
    </script>
    {{-- <script src="{{asset(" admin")}}/assets/js/bundle/summernote.bundle.js"></script>
    <script>
        $(document).ready(function () {
                $('.summernote').summernote();
            });
    </script> --}}

@endsection

@include('components.sweetalert')
