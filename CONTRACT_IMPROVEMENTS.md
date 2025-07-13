# تحسينات صفحة إنشاء العقد

## نظرة عامة
تم تحسين صفحة إنشاء العقد في `resources/views/admin/ingaz/RecruitmentContract/create.blade.php` لتوفير واجهة مستخدم أفضل وأكثر وضوحاً لإنشاء العقود.

## التحسينات الرئيسية

### 1. واجهة اختيار نوع العقد
- **تصميم جديد**: إضافة بطاقات تفاعلية لاختيار نوع العقد
- **خياران رئيسيان**:
  - **طلب مسبق**: اختيار طلب موجود من قائمة الطلبات المسبقة
  - **عقد جديد**: اختيار عميل وعاملة منفصلين لإنشاء عقد جديد

### 2. تحسينات التصميم
- **أيقونات Font Awesome**: إضافة أيقونات واضحة لكل قسم
- **تأثيرات بصرية**: 
  - تأثيرات hover للبطاقات
  - انتقالات سلسة بين الأقسام
  - تأثيرات اهتزاز للأخطاء
- **تدرجات لونية**: استخدام تدرجات لونية جذابة للبطاقات والأزرار

### 3. تحسينات JavaScript
- **معالجة ذكية للأقسام**: إظهار/إخفاء الأقسام بناءً على الاختيار
- **تحقق محسن**: تحقق شامل من جميع الحقول المطلوبة
- **رسائل خطأ محسنة**: رسائل واضحة ومفيدة للمستخدم
- **تأثيرات بصرية**: تأثيرات fadeIn/fadeOut للانتقالات

### 4. تحسينات التحقق من الصحة
- **تحقق من نوع العقد**: التأكد من اختيار نوع العقد المناسب
- **تحقق من الطلب**: التأكد من اختيار طلب في حالة الطلب المسبق
- **تحقق من العاملة**: التأكد من اختيار عاملة في حالة العقد الجديد
- **تحقق من بيانات العميل**: التحقق من الحقول المطلوبة للعميل الجديد
- **تحقق من العميل الموجود**: التأكد من اختيار عميل موجود في حالة العقد الجديد

### 5. تحسينات تجربة المستخدم
- **إرشادات واضحة**: رسائل توضيحية لكل قسم
- **حالة التحميل**: إظهار حالة التحميل أثناء الحفظ
- **رسائل النجاح/الخطأ**: رسائل واضحة مع تأثيرات بصرية
- **تمرير تلقائي**: التمرير إلى أول حقل خطأ

## الميزات الجديدة

### 1. اختيار نوع العقد
```html
<div class="card contract-type-card" data-type="existing_order">
    <div class="card-body">
        <div class="icon">
            <i class="fas fa-list-check"></i>
        </div>
        <h5>طلب مسبق</h5>
        <p class="text-muted">اختيار طلب موجود من قائمة الطلبات المسبقة</p>
    </div>
</div>
```

### 2. قسم الطلب المسبق
- قائمة منسدلة بالطلبات المسبقة
- عرض معلومات العميل والخادمة تلقائياً
- تحقق من اختيار الطلب

### 3. قسم العقد الجديد
- اختيار نوع العميل (موجود/جديد)
- اختيار العاملة من قائمة العمال المتاحين
- إرشادات واضحة لكل خيار

### 4. قسم اختيار العميل الموجود
- **قائمة منسدلة بالعملاء الموجودين**: عرض جميع العملاء المسجلين
- **ملء تلقائي للبيانات**: عند اختيار عميل، يتم ملء جميع بياناته تلقائياً
- **تعطيل الحقول**: الحقول تُعطل بعد ملئها لمنع التعديل
- **إعادة تعيين**: عند تغيير الاختيار، يتم إعادة تعيين الحقول

```html
<div id="existing-client-selection" class="row g-3 mb-4" style="display: none;">
    <div class="col-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>اختيار العميل الموجود</strong>
        </div>
    </div>
    <div class="col-md-12">
        <label for="existing_client_id" class="form-label">اختيار العميل <span class="text-danger">*</span></label>
        <select name="existing_client_id" id="existing_client_id" class="form-select form-control select2">
            <option value="" disabled selected>{{helperTrans('ingaz.choose')}}</option>
            @if(isset($existing_clients))
                @foreach($existing_clients as $client)
                    <option value="{{$client->id}}"
                        data-name="{{$client->name}}"
                        data-phone="{{$client->phone}}"
                        data-email="{{$client->email}}"
                        data-nationality="{{$client->nationality_id}}"
                        data-national-num="{{$client->national_num}}"
                        data-birthdate="{{$client->birthdate}}"
                        data-phone2="{{$client->phone2}}"
                        data-address="{{$client->address}}"
                        data-city="{{$client->city}}"
                        data-beds-num="{{$client->rooms_num}}"
                        data-family-num="{{$client->family_num}}"
                        data-children="{{$client->children}}"
                        data-job="{{$client->job}}">
                        {{$client->name}} - {{$client->phone}} - {{$client->national_num}}
                    </option>
                @endforeach
            @endif
        </select>
    </div>
</div>
```

### 5. تحسينات CSS
```css
.contract-type-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    overflow: hidden;
}

.contract-type-card:hover {
    border-color: #007bff;
    transform: translateY(-3px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.15);
}
```

## كيفية الاستخدام

### 1. إنشاء عقد من طلب مسبق
1. اختيار "طلب مسبق" من البطاقات
2. اختيار الطلب من القائمة المنسدلة
3. مراجعة معلومات العميل والخادمة (تعبأ تلقائياً)
4. إدخال معلومات العقد
5. الضغط على "حفظ"

### 2. إنشاء عقد جديد مع عميل موجود
1. اختيار "عقد جديد" من البطاقات
2. اختيار "عميل موجود" من خيارات نوع العميل
3. اختيار العاملة من القائمة
4. اختيار العميل من قائمة العملاء الموجودين
5. مراجعة بيانات العميل (تعبأ تلقائياً)
6. إدخال معلومات العقد
7. الضغط على "حفظ"

### 3. إنشاء عقد جديد مع عميل جديد
1. اختيار "عقد جديد" من البطاقات
2. اختيار "عميل جديد" من خيارات نوع العميل
3. اختيار العاملة من القائمة
4. إدخال بيانات العميل الجديد
5. إدخال معلومات العقد
6. الضغط على "حفظ"

## التحسينات التقنية

### 1. معالجة AJAX محسنة
- إرسال النموذج عبر AJAX
- عرض رسائل النجاح/الخطأ
- معالجة أخطاء التحقق

### 2. تحقق محسن
```javascript
// التحقق من نوع العقد
var contractType = $('#contract_type').val();
if (contractType === 'existing_order') {
    // التحقق من الطلب
    if (!$('#order_id').val()) {
        // رسالة خطأ
    }
} else {
    // التحقق من العاملة
    if (!$('#new_maid_id').val()) {
        // رسالة خطأ
    }
    
    // التحقق من العميل الموجود
    var newClientType = $('input[name="new_client_type"]:checked').val();
    if (newClientType === 'ex_client') {
        if (!$('#existing_client_id').val()) {
            // رسالة خطأ
        }
    }
}
```

### 3. معالجة العميل الموجود
```javascript
// التعامل مع اختيار العميل الموجود
$('#existing_client_id').change(function() {
    var selectedOption = $(this).find('option:selected');
    if (selectedOption.val()) {
        // ملء بيانات العميل تلقائياً
        $('#client_name').val(selectedOption.data('name'));
        $('#client_phone').val(selectedOption.data('phone'));
        // ... باقي الحقول
        
        // تعطيل الحقول بعد ملئها
        $('#client_name, #client_phone, ...').prop('disabled', true);
    } else {
        // إعادة تعيين الحقول عند إلغاء الاختيار
        $('#client_name, #client_phone, ...').val('');
        $('#client_name, #client_phone, ...').prop('disabled', false);
    }
});
```

### 4. تأثيرات بصرية
- تأثيرات fadeIn/fadeOut للانتقالات
- تأثير اهتزاز للأخطاء
- تأثيرات hover للبطاقات

## الملفات المتأثرة
- `resources/views/admin/ingaz/RecruitmentContract/create.blade.php` (الملف الرئيسي)
- `resources/views/admin/ingaz/contract/client-section.blade.php` (قسم العميل - محدث)
- `resources/views/admin/ingaz/contract/maid-section.blade.php` (قسم الخادمة)
- `resources/views/admin/ingaz/contract/contract-section.blade.php` (قسم العقد)

## ملاحظات مهمة
1. تأكد من وجود البيانات المطلوبة في المتغيرات المرسلة من Controller:
   - `$existing_clients` - قائمة العملاء الموجودين
   - `$orders` - قائمة الطلبات المسبقة
   - `$all_available_maids` - قائمة العمال المتاحين
   - `$countries` - قائمة الدول
2. تأكد من وجود الأذونات المطلوبة للمستخدم
3. تأكد من عمل Select2 بشكل صحيح
4. تأكد من وجود SweetAlert2 للرسائل

## التطوير المستقبلي
- إضافة معاينة للعقد قبل الحفظ
- إضافة خيارات متقدمة للعقد
- إضافة تاريخ للعقود السابقة
- إضافة تصدير العقد كـ PDF
- إضافة بحث في قائمة العملاء
- إضافة تصفية للعملاء حسب المدينة أو الجنسية 
