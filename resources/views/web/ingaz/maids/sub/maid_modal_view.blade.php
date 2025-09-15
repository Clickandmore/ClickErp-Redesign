@push('css')

@endpush

<!-- Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            {{-- <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">{{ helperTrans('web.info') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div> --}}
            <div class="modal-body">
                <div class="details">

                    <div class="maid-cv-template">
                        <img class="maid-cv-logo" src="{{ asset('web/assets/images/logoh.svg') }}" alt="" height="80px">
                        {{-- Top section --}}
                        <div class="row">
                            <div class="col-md-3">
                                <img src="{{ asset('/') }}" alt="cv1" id="maidImgTarget"
                                    style="height: 150px; width: 150px; border-radius: 50%; object-fit: cover;">
                            </div>
                            <div class="col-md-9">
                                <div class="row d-flex">
                                    <p class="col-md-6 cv-prop">@lang('web.age')</p>
                                    <p class="col-md-6" id="age-trgt"></p>
                                </div>
                                <div class="row d-flex">
                                    <p class="col-md-6 cv-prop">@lang('web.nationality')</p>
                                    <p class="col-md-6" id="nationality-trgt-1"></p>
                                </div>
                                <div class="row d-flex">
                                    <p class="col-md-6 cv-prop">@lang('web.religion')</p>
                                    <p class="col-md-6" id="religion-trgt-1"></p>
                                </div>
                                <div class="row d-flex">
                                    <p class="col-md-6 cv-prop">@lang('web.passport_num')</p>
                                    <p class="col-md-6" id="passport_number-trgt-1"></p>
                                </div>
                                <div class="row d-flex">
                                    <p class="col-md-6 cv-prop">@lang('web.recruitment_price')</p>
                                    <p class="col-md-6" id="recruitment_price-trgt-1"></p>
                                </div>
                            </div>
                        </div>
                        {{-- details section --}}
                        <div class="divider"></div>
                        <div class="row">
                            <div class="row d-flex">
                                <p class="col-md-4 cv-prop">@lang('web.marital_status')</p>
                                <p class="col-md-8" id="marital_status"></p>
                            </div>
                            <div class="row d-flex">
                                <p class="col-md-4 cv-prop">@lang('web.height')</p>
                                <p class="col-md-8" id="height-trgt"></p>
                            </div>
                            <div class="row d-flex">
                                <p class="col-md-4 cv-prop">@lang('web.weight')</p>
                                <p class="col-md-8" id="weight-trgt"></p>
                            </div>
                        </div>
                        {{-- download cv --}}
                        <div class="divider"></div>
                        <div class="row d-flex justify-content-center">
                            <div class="col-md-12 text-center">
                                <a class="btn btn-primary" id="maidCVTarget" href="#" target="_blank" data-passport="">
                                    @lang('web.download_cv')
                                </a>
                            </div>
                        </div>
                    </div>


            </div>
        </div>
    </div>
</div>


@push('js')
    <script>
        $(document).ready(function () {
            // Use event delegation to handle clicks on dynamically added elements
            $(document).on('click', '.maidViewData', function (e) {
                $('#detailsModal').modal('show');
                e.preventDefault();
                var maidId = $(this).data('id');

                // إعادة تعيين نص الزر إلى القيمة الافتراضية
                $('#maidCVTarget').text('تحميل السيرة الذاتية');
                $.ajax({
                    url: '{{ route('is.getMaidDetails', ['maidId' => ':maidId']) }}'.replace(':maidId', maidId),
                    method: 'GET',
                    success: function (data) {
                        console.log('Maid data received:', data); // للتشخيص

                        // Update modal content with fetched data
                        // الصورة
                        if (data.image) {
                            $('#maidImgTarget').attr('src', data.image);
                            $('#maidImgTarget2').attr('src', data.image);
                        } else {
                            var defaultImg = '{{ asset("web/assets/images/default-avatar.png") }}';
                            $('#maidImgTarget').attr('src', defaultImg);
                            $('#maidImgTarget2').attr('src', defaultImg);
                        }

                        // ملف السيرة الذاتية
                        var cvUrl = data.cv_preview || data.cv_file;
                        var imageUrl = data.image;
                        console.log('CV URL:', cvUrl); // للتشخيص
                        console.log('CV File:', data.cv_file); // للتشخيص
                        console.log('CV Preview:', data.cv_preview); // للتشخيص
                        console.log('Image URL:', imageUrl); // للتشخيص

                        // دالة للتحقق من صحة الرابط
                        function isValidUrl(url) {
                            return url && url !== 'null' && url !== '' && url !== null && url !== undefined;
                        }

                        // التأكد من أن الرابط صحيح
                        if (isValidUrl(cvUrl)) {
                            $('#maidCVTarget').attr('href', cvUrl).attr('data-passport', data.passport_num);
                            $('#maidCVTarget').show(); // إظهار زر التحميل
                            $('#maidCVTarget').text('تحميل السيرة الذاتية'); // نص زر التحميل

                            // إذا كان الملف صورة، اعرضها
                            if (cvUrl.match(/\.(jpg|jpeg|png|gif)$/i)) {
                                $('#maidCVImg').attr('src', cvUrl);
                            } else {
                                // إذا كان PDF أو ملف آخر، اعرض أيقونة
                                $('#maidCVImg').attr('src', '{{ asset("web/assets/images/pdf-icon.png") }}');
                            }
                        } else {
                            // إذا لم تكن هناك سيرة ذاتية، استخدم صورة الموديل
                            if (isValidUrl(imageUrl)) {
                                $('#maidCVTarget').attr('href', imageUrl).attr('data-passport', data.passport_num);
                                $('#maidCVTarget').show(); // إظهار زر التحميل
                                $('#maidCVTarget').text('تحميل صورة الموديل'); // نص زر التحميل
                                $('#maidCVImg').attr('src', imageUrl);
                                console.log('Using maid image instead of CV');
                            } else {
                                $('#maidCVTarget').hide(); // إخفاء زر التحميل إذا لم يكن هناك CV أو صورة
                                $('#maidCVImg').attr('src', '{{ asset("web/assets/images/no-cv.png") }}');
                                console.log('No CV file or image available for this maid');
                            }
                        }

                        // العمر
                        if (data.age) {
                            $('#age-trgt').text(data.age + ' سنة');
                        } else if (data.birth_date) {
                            // حساب العمر من تاريخ الميلاد
                            var birthDate = new Date(data.birth_date);
                            var today = new Date();
                            var age = today.getFullYear() - birthDate.getFullYear();
                            $('#age-trgt').text(age + ' سنة');
                        }

                        // الراتب
                        if (data.salary) {
                            $('#salary-trgt').text(data.salary + ' ر.س');
                        } else {
                            $('#salary-trgt').text('غير محدد');
                        }

                        // سعر الاستقدام
                        if (data.price) {
                            $('#recruitment_price-trgt').text(data.price + ' ر.س');
                            $('#recruitment_price-trgt-1').text(data.price + ' ر.س');
                            $('[id^="recruitment_price-trgt"]').text(data.price + ' ر.س');
                        } else {
                            $('[id^="recruitment_price-trgt"]').text('غير محدد');
                        }

                        // سنوات الخبرة
                        if (data.experience_years) {
                            $('#experience_years-trgt').text(data.experience_years + ' سنوات');
                        } else {
                            $('#experience_years-trgt').text('غير محدد');
                        }

                        // رقم الجواز
                        if (data.passport_num) {
                            $('#passport_number-trgt').text(data.passport_num);
                            $('#passport_number-trgt-1').text(data.passport_num);
                            $('[id^="passport_number-trgt"]').text(data.passport_num);
                        } else {
                            $('[id^="passport_number-trgt"]').text('غير محدد');
                        }

                        // الجنسية
                        if (data.nationality) {
                            var nationalityText = data.nationality.title || data.nationality.name || data.nationality;
                            $('#nationality-trgt').text(nationalityText);
                            $('#nationality-trgt-1').text(nationalityText);
                            $('[id^="nationality-trgt"]').text(nationalityText);
                        } else {
                            $('[id^="nationality-trgt"]').text('غير محدد');
                        }

                        // الديانة
                        if (data.religion_title || data.religion) {
                            var religionText = data.religion_title || (data.religion && data.religion.title) || data.religion;
                            $('#religion-trgt').text(religionText);
                            $('#religion-trgt-1').text(religionText);
                            $('[id^="religion-trgt"]').text(religionText);
                        } else {
                            $('[id^="religion-trgt"]').text('غير محدد');
                        }

                        // الطول
                        if (data.height) {
                            $('#height-trgt').text(data.height + ' سم');
                            $('[id^="height-trgt"]').text(data.height + ' سم');
                        } else {
                            $('[id^="height-trgt"]').text('غير محدد');
                        }

                        // الوزن
                        if (data.weight) {
                            $('#weight-trgt').text(data.weight + ' كجم');
                            $('[id^="weight-trgt"]').text(data.weight + ' كجم');
                        } else {
                            $('[id^="weight-trgt"]').text('غير محدد');
                        }

                        // الحالة الاجتماعية
                        if (data.marital_status) {
                            var maritalText = data.marital_status == 'single' ? 'عزباء' :
                                            data.marital_status == 'married' ? 'متزوجة' :
                                            data.marital_status == 'divorced' ? 'مطلقة' :
                                            data.marital_status == 'widowed' ? 'أرملة' : data.marital_status;
                            $('#marital_status').text(maritalText);
                        } else {
                            $('#marital_status').text('غير محدد');
                        }

                        // المهنة
                        if (data.job_title || data.occupation) {
                            $('#occupation-trgt').text(data.job_title || data.occupation);
                        }
                    },
                    error: function (error) {
                        console.log('Error fetching data:', error);
                    }
                });
            });
            // تحميل السيرة الذاتية أو صورة الموديل
            $('#maidCVTarget').on('click', function (event) {
                event.preventDefault(); // منع السلوك الافتراضي للرابط

                const cvUrl = $(this).attr('href');
                const maidPassport = $(this).data('passport') || 'cv';

                // التحقق من أن الرابط صحيح وليس صفحة HTML
                if (!cvUrl || cvUrl === '#' || cvUrl === '{{ asset("/") }}' || cvUrl === 'null' || cvUrl === null || cvUrl === undefined || cvUrl === '') {
                    alert('السيرة الذاتية غير متوفرة');
                    return false;
                }

                // التحقق من أن الرابط يبدأ بـ http أو https
                if (!cvUrl.startsWith('http://') && !cvUrl.startsWith('https://') && !cvUrl.startsWith('/')) {
                    alert('رابط الملف غير صحيح');
                    return false;
                }

                // تحديد نوع الملف
                const isImage = cvUrl.match(/\.(jpg|jpeg|png|gif)$/i);
                const fileName = isImage ? maidPassport + '_image.jpg' : maidPassport + '_cv.pdf';

                // فتح الملف في نافذة جديدة للعرض/التحميل
                try {
                    var newWindow = window.open(cvUrl, '_blank');
                    if (!newWindow) {
                        alert('تم حظر النافذة المنبثقة. يرجى السماح بالنوافذ المنبثقة لهذا الموقع.');
                    }
                } catch (error) {
                    console.error('Error opening file:', error);
                    alert('حدث خطأ في فتح الملف');
                }

                // أو استخدام طريقة التحميل المباشر
                // fetch(cvUrl)
                //     .then(response => response.blob())
                //     .then(blob => {
                //         const url = window.URL.createObjectURL(blob);
                //         const link = document.createElement('a');
                //         link.href = url;
                //         link.download = fileName;
                //         document.body.appendChild(link);
                //         link.click();
                //         document.body.removeChild(link);
                //         window.URL.revokeObjectURL(url);
                //     })
                //     .catch(error => {
                //         console.error('Error downloading file:', error);
                //         alert('حدث خطأ في تحميل الملف');
                //     });
            });
            //
        });
    </script>
@endpush
