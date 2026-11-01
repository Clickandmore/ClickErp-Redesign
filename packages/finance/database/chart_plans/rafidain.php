<?php

// خطة ترتيب دليل شركة الرافدين. تُنفَّذ بـ: php artisan finance:chart-restructure --plan=rafidain
// الأكواد هنا هي الأكواد الأصلية قبل الترتيب؛ الأكواد الجديدة تُحسب وقت التنفيذ.
return array (
  'digits' => 
  array (
    0 => 1,
    1 => 2,
    2 => 4,
    3 => 8,
    4 => 11,
  ),
  'type_display' => 'hierarchy',
  'expected_names' => 
  array (
    1 => 'الاصول',
    4 => 'الايرادات',
    11 => 'الاصول الثابتة',
    12 => 'الاصول المتداولة',
    13 => 'الاصول الغير ملموسة',
    14 => 'الخصوم قصيرة الاجل',
    21 => 'الخصوم طويلة الاجل',
    41 => 'اجمالى ايراد المبيعات',
    42 => 'اجمالى الايرادات الاخرى',
    51 => 'اجمالى تكاليف المبيعات',
    52 => 'اجمالى مصاريف التشغيل',
    100 => 'باص للايواء',
    101 => 'سيارة كيا',
    102 => 'الأصول المتداولة قصيرة الأجل',
    111 => 'اجهزة كمبيوتر و طابعات',
    112 => 'مبانى وتجهيزات',
    113 => 'اثات ومفروشات',
    127 => 'خطابات الضمان',
    128 => 'أرصدة مدينة أخرى',
    129 => '...',
    132 => 'برامج محاسبية و كمبيوتر',
    141 => 'الموردين',
    142 => 'اوراق الدفع',
    143 => 'أرصدة دائنة أخرى',
    144 => 'المسوقين',
    145 => 'شركات التامين علي عقود الاستقدام',
    146 => 'مخصص الزكاة',
    211 => 'مخصص انهاء خدمة للموظفين',
    212 => 'اطراف ذات علاقة',
    221 => 'الوكلاء الخارجيين',
    311 => 'راس مال المكتب',
    312 => 'حساب الارباح والخسائر',
    400 => 'غرامات إلغاء عقود الاستقدام',
    422 => 'تامين العماله لعقود الاستقدام ( ايراد )',
    512 => 'تامين العماله لعقود الاستقدام  ( مصاريف )',
    522 => 'المصروفات التشغيلية',
    523 => 'مصاريف الايواء التشغيلية',
    1114 => 'مجمع اهلاك اجهزة كمبيوتر وطابعات',
    1124 => '.',
    1126 => 'مجمع اهلاك  مبانى وتجهيزات',
    1133 => 'مجمع اهلاك  اثاث ومفروشات',
    1144 => 'مجمع اهلاك  السيارات',
    1210 => '...',
    1216 => 'عهد وذمم على الموظفين',
    1251 => 'سلفة جاكلين JACKLINE MKARYE',
    1254 => 'سلفه بدر الزمان',
    1256 => 'سلفة البيرا اسفينو',
    1257 => 'سلفه مصطفي يحىى',
    1258 => 'سلفه سجاد الفرج',
    1259 => 'ابجاتي حسين',
    1261 => 'عهدة باسم عاشور',
    1265 => 'عهدة مراد الفرج',
    1266 => 'عهده سجاد الفرج',
    1268 => 'عهد تحت التسوية',
    1269 => 'عهده ليا صح بعد استلام التفاصيل ******',
    1281 => 'حساب ضريبة القيمة المضافة',
    1438 => '.',
    3311 => 'حساب جاري المالك',
    4211 => 'ايرادات اخرى',
    4212 => 'ايرادات تفاويض',
    4213 => 'نقل كفالات',
    4214 => 'حساب تاجير خدم',
    5211 => 'الايجارات',
    5228 => 'عمولة احمد السعيد',
    10201 => 'مصروفات مدفوعة مقدما - الايواء',
    12510 => 'سلفه ايميلي',
    12511 => 'سلفه MEKDES 	HAILU FEREDE 829',
    12610 => 'عهده ليا دولار $ قبل استلام التفاصيل',
    12611 => 'عهده بدر الزمان',
    12612 => 'عهده ايملي دومو ( كينيا المشرفه)',
    22100 => 'روشا مارك',
    22101 => 'مكتب باكستان سنكر سوات انتربرايز',
    22104 => 'مكتب الجريد-مونتين',
    22105 => 'مكتب شريف ادم',
    22106 => 'مكتب لينكينج-بنجلاديش',
    22107 => 'مكتب واي اند ال تايب الفلبين',
    22108 => 'مكتب كليجا-اوغندا',
    22110 => 'مكتب مولينيا كينيا)',
    41119 => 'مردودات مبيعات الاستقدام',
    51121 => 'مردودات تكلفة الاستقدام',
    52100 => 'مصروف اهلاك باص الايواء',
    52128 => 'دعاية واعلان',
    52141 => 'مصاريف علاج وفحوصات',
    52152 => 'رواتب عاملات الايجار',
    52163 => 'مصروفات اخري',
    52165 => 'حساب اقفال القيمه المضافه',
    52175 => 'مصاريف استهلاك الاصول الثابتة',
    52210 => 'عموله محمد يحي',
    52211 => 'عمولة زينب اليوسف',
    52212 => 'عمولات خارجيه',
    52220 => 'عمولات للخارج والمكاتب الخارجيه****************مغلق',
    52222 => 'عموله نقل كفاله عاملات',
    52227 => 'مطبوعات وتصوير',
  ),
  'renames' => 
  array (
    1 => 'الأصول',
    4 => 'الإيرادات',
    11 => 'الأصول الثابتة',
    12 => 'الأصول المتداولة قصيرة الأجل',
    13 => 'الأصول غير الملموسة',
    21 => 'الخصوم طويلة الأجل',
    41 => 'إيراد مبيعات الاستقدام',
    42 => 'إيراد الخدمات',
    51 => 'تكاليف مبيعات الاستقدام',
    52 => 'اجمالي المصروفات عمومية وادارية',
    111 => 'اجهزة كمبيوتر وطابعات',
    112 => 'مباني وتجهيزات',
    113 => 'اثاث ومفروشات',
    128 => 'ارصدة مدينة اخرى',
    132 => 'برامج محاسبية ونظم الاستخدام',
    143 => 'ارصدة دائنة اخرى',
    145 => 'شركات التأمين',
    211 => 'مخصص منافع الموظفين',
    311 => 'رأس المال',
    312 => 'الارباح والخسائر',
    522 => 'مصروفات تشغيلية مباشرة',
    523 => 'مصروفات ايواء التشغيل',
    5211 => 'الإيجارات',
    41119 => 'مرتد مبيعات الاستقدام',
    52163 => 'مصروفات اخرى',
  ),
  'moves' => 
  array (
    100 => 'c:114',
    101 => 'c:114',
    127 => 'n:a25',
    141 => 'c:22',
    142 => 'c:22',
    143 => 'c:22',
    144 => 'c:22',
    145 => 'c:22',
    146 => 'c:22',
    400 => 'n:a43',
    422 => 'n:a43',
    512 => 'c:522',
    522 => 'c:5',
    523 => 'c:54',
    1114 => 'n:a4',
    1126 => 'n:a4',
    1133 => 'n:a4',
    1144 => 'n:a4',
    1251 => 'c:125',
    1254 => 'c:125',
    1256 => 'c:125',
    1257 => 'c:125',
    1258 => 'c:125',
    1259 => 'c:126',
    1261 => 'c:126',
    1265 => 'c:126',
    1266 => 'c:126',
    1268 => 'c:126',
    1269 => 'c:126',
    1281 => 'n:a0',
    3311 => 'c:33',
    4211 => 'n:a42',
    4212 => 'c:42',
    4213 => 'c:4401',
    4214 => 'n:a40',
    5228 => 'n:a84',
    10201 => 'c:12',
    12510 => 'c:125',
    12511 => 'c:125',
    12610 => 'c:126',
    12611 => 'c:126',
    12612 => 'c:126',
    22102 => 'c:141',
    22103 => 'c:141',
    22109 => 'c:141',
    22111 => 'c:141',
    22112 => 'c:141',
    22113 => 'c:141',
    22114 => 'c:141',
    22115 => 'c:141',
    22116 => 'c:141',
    22117 => 'c:141',
    22118 => 'c:141',
    22119 => 'c:141',
    22120 => 'c:141',
    22121 => 'c:141',
    22122 => 'c:141',
    22123 => 'c:141',
    22124 => 'c:141',
    22125 => 'c:141',
    41119 => 'c:41',
    51121 => 'c:51',
    52100 => 'n:a106',
    52128 => 'n:a84',
    52141 => 'c:522',
    52152 => 'c:522',
    52165 => 'n:a0',
    52175 => 'n:a106',
    52210 => 'n:a84',
    52211 => 'n:a84',
    52212 => 'n:a84',
    52220 => 'n:a84',
    52222 => 'n:a84',
    52227 => 'c:521',
  ),
  'merges' => 
  array (
    14 => '22',
    102 => '12',
    221 => '141',
    1216 => '126',
    22100 => '14116',
    22101 => '14168',
    22104 => '14169',
    22105 => '14123',
    22106 => '14131',
    22107 => '14127',
    22108 => '14129',
    22110 => '14152',
  ),
  'deletes' => 
  array (
    0 => '129',
    1 => '212',
    2 => '1210',
    3 => '1124',
    4 => '1438',
  ),
  'creates' => 
  array (
    'a0' => 
    array (
      'name' => 'ضريبة القيمة المضافة',
      'type' => 'NormalAccount',
      'status' => 'creditor',
      'parent' => 'c:22',
    ),
    'a1' => 
    array (
      'name' => 'الاراضي',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:11',
    ),
    'a2' => 
    array (
      'name' => 'اجهزة والالات',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:11',
    ),
    'a3' => 
    array (
      'name' => 'انظمة وكاميرات',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:11',
    ),
    'a4' => 
    array (
      'name' => 'مجمع اهلاك الاصول الثابتة',
      'type' => 'DepreciationProvision',
      'status' => 'debtor',
      'parent' => 'c:11',
    ),
    'a5' => 
    array (
      'name' => 'عملاء عقود رجالية',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:12',
    ),
    'a6' => 
    array (
      'name' => 'اطراف ذات العلاقة',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:12',
    ),
    'a7' => 
    array (
      'name' => 'خصومات العاملين',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:12',
    ),
    'a8' => 
    array (
      'name' => 'مصروفات مدفوعة مقدما مكاتب خارجية',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:12',
    ),
    'a9' => 
    array (
      'name' => 'مصروفات مدفوعة مقدما - ايواء التجربة',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:12',
    ),
    'a10' => 
    array (
      'name' => 'مصروفات مدفوعة مقدما - قصيرة الاجل',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:12',
    ),
    'a11' => 
    array (
      'name' => 'مصروفات مدفوعة مقدما تأجير عمالة',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a10',
    ),
    'a12' => 
    array (
      'name' => 'مخزون مهمات',
      'type' => 'Storage',
      'status' => 'debtor',
      'parent' => 'c:12',
    ),
    'a13' => 
    array (
      'name' => 'محافظ رقمية',
      'type' => 'ElectronicWallets',
      'status' => 'debtor',
      'parent' => 'c:12',
    ),
    'a14' => 
    array (
      'name' => 'مصروفات مدفوعة مقدما - التأجير',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:12',
    ),
    'a15' => 
    array (
      'name' => 'مصروفات مدفوعة مقدما - تكلفة استقدام التأجير',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a14',
      'legacy_code' => '1592',
    ),
    'a16' => 
    array (
      'name' => 'مصروفات مدفوعة مقدما - رسوم إنجاز التأجير',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a14',
      'legacy_code' => '1593',
    ),
    'a17' => 
    array (
      'name' => 'مصروفات مدفوعة مقدما - رسوم إقامات التأجير',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a14',
      'legacy_code' => '1594',
    ),
    'a18' => 
    array (
      'name' => 'مصروفات مدفوعة مقدما - رسوم رخص عمل التأجير',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a14',
      'legacy_code' => '1595',
    ),
    'a19' => 
    array (
      'name' => 'مصروفات مدفوعة مقدما - تأمين طبي عمالة التأجير',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a14',
      'legacy_code' => '1596',
    ),
    'a20' => 
    array (
      'name' => 'مصروفات مدفوعة مقدما - تأشيرات مستخدمة التأجير',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a14',
      'legacy_code' => '1597',
    ),
    'a21' => 
    array (
      'name' => 'مصروفات مدفوعة مقدما - تأشيرات عمل التأجير',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a14',
    ),
    'a22' => 
    array (
      'name' => 'مجمع اهلاك الاصول غير الملموسة',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:13',
    ),
    'a23' => 
    array (
      'name' => 'استخدام التأجير',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:13',
    ),
    'a24' => 
    array (
      'name' => 'برامج وانظمة تحت التطوير',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:13',
    ),
    'a25' => 
    array (
      'name' => 'الأصول غير المتداولة الأخرى',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:1',
    ),
    'a26' => 
    array (
      'name' => 'مصروفات مدفوعة مقدما - طويلة الاجل',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a25',
    ),
    'a27' => 
    array (
      'name' => 'القروض والالتزامات البنكية',
      'type' => 'NormalAccount',
      'status' => 'creditor',
      'parent' => 'c:21',
    ),
    'a28' => 
    array (
      'name' => 'مصروفات مستحقة رواتب موظفين',
      'type' => 'NormalAccount',
      'status' => 'creditor',
      'parent' => 'c:22',
    ),
    'a29' => 
    array (
      'name' => 'مصروفات مستحقة بدل سكن موظفين',
      'type' => 'NormalAccount',
      'status' => 'creditor',
      'parent' => 'c:22',
    ),
    'a30' => 
    array (
      'name' => 'مصروفات مستحقة بدل مواصلات موظفين',
      'type' => 'NormalAccount',
      'status' => 'creditor',
      'parent' => 'c:22',
    ),
    'a31' => 
    array (
      'name' => 'مصروفات مستحقة عمولات قسم الخارجية',
      'type' => 'NormalAccount',
      'status' => 'creditor',
      'parent' => 'c:22',
    ),
    'a32' => 
    array (
      'name' => 'التزامات بنك الراجحي',
      'type' => 'NormalAccount',
      'status' => 'creditor',
      'parent' => 'c:22',
    ),
    'a33' => 
    array (
      'name' => 'مصروفات مستحقة عمولات مسوقين',
      'type' => 'NormalAccount',
      'status' => 'creditor',
      'parent' => 'c:22',
    ),
    'a34' => 
    array (
      'name' => 'مصروفات مستحقة عمولات سائقين',
      'type' => 'NormalAccount',
      'status' => 'creditor',
      'parent' => 'c:22',
    ),
    'a35' => 
    array (
      'name' => 'دفعات مقدمة تأجير',
      'type' => 'NormalAccount',
      'status' => 'creditor',
      'parent' => 'c:22',
    ),
    'a36' => 
    array (
      'name' => 'إيراد مقدم خدمات تأجير',
      'type' => 'NormalAccount',
      'status' => 'creditor',
      'parent' => 'n:a35',
    ),
    'a37' => 
    array (
      'name' => 'حقوق المساهمين - الاطراف',
      'type' => 'NormalAccount',
      'status' => 'creditor',
      'parent' => 'c:31',
    ),
    'a38' => 
    array (
      'name' => 'إيراد استرداد مبيعات',
      'type' => 'NormalAccount',
      'status' => 'creditor',
      'parent' => 'c:41',
    ),
    'a39' => 
    array (
      'name' => 'إيراد خدمات العقود الرجالية',
      'type' => 'NormalAccount',
      'status' => 'creditor',
      'parent' => 'c:42',
    ),
    'a40' => 
    array (
      'name' => 'إيراد خدمات التأجير',
      'type' => 'NormalAccount',
      'status' => 'creditor',
      'parent' => 'c:42',
    ),
    'a41' => 
    array (
      'name' => 'خصومات نقل الخدمات',
      'type' => 'NormalAccount',
      'status' => 'creditor',
      'parent' => 'c:44',
    ),
    'a42' => 
    array (
      'name' => 'إيرادات اخرى',
      'type' => 'NormalAccount',
      'status' => 'creditor',
      'parent' => 'c:4',
    ),
    'a43' => 
    array (
      'name' => 'إيرادات تشغيلية اخرى',
      'type' => 'NormalAccount',
      'status' => 'creditor',
      'parent' => 'c:4',
    ),
    'a44' => 
    array (
      'name' => 'ايرادات تشغيلية اخرى',
      'type' => 'NormalAccount',
      'status' => 'creditor',
      'parent' => 'n:a43',
    ),
    'a45' => 
    array (
      'name' => 'إيرادات إلغاء التأجير',
      'type' => 'NormalAccount',
      'status' => 'creditor',
      'parent' => 'n:a44',
    ),
    'a46' => 
    array (
      'name' => 'إيراد مبيعات التأجير',
      'type' => 'NormalAccount',
      'status' => 'creditor',
      'parent' => 'c:4',
    ),
    'a47' => 
    array (
      'name' => 'مصروفات ادارات مكتبية وقرطاسية',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:52',
    ),
    'a48' => 
    array (
      'name' => 'مصروفات ضيافة',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:52',
    ),
    'a49' => 
    array (
      'name' => 'مصروفات كهرباء و مياه',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:52',
    ),
    'a50' => 
    array (
      'name' => 'مصروفات هاتف وانترنت',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:52',
    ),
    'a51' => 
    array (
      'name' => 'مصروفات صيانة',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:52',
    ),
    'a52' => 
    array (
      'name' => 'مصروفات نثرية',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:52',
    ),
    'a53' => 
    array (
      'name' => 'مصروفات ديون معدومة',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:52',
    ),
    'a54' => 
    array (
      'name' => 'عمولات ورسوم بنكية',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:52',
    ),
    'a55' => 
    array (
      'name' => 'خصم مبيعات الفلبين',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:52',
    ),
    'a56' => 
    array (
      'name' => 'رواتب عاملات',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:52',
    ),
    'a57' => 
    array (
      'name' => 'مصروفات استرداد',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:52',
    ),
    'a58' => 
    array (
      'name' => 'خصم مبيعات سيرلانكا',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:52',
    ),
    'a59' => 
    array (
      'name' => 'خصم مبيعات اوغندا',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:52',
    ),
    'a60' => 
    array (
      'name' => 'خصم مبيعات اثيوبيا',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:52',
    ),
    'a61' => 
    array (
      'name' => 'خصم مبيعات بنجلاديش',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:52',
    ),
    'a62' => 
    array (
      'name' => 'خصم مبيعات كينيا',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:52',
    ),
    'a63' => 
    array (
      'name' => 'خصم مبيعات الهند',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:52',
    ),
    'a64' => 
    array (
      'name' => 'خصم مبيعات باكستان',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:52',
    ),
    'a65' => 
    array (
      'name' => 'مصروفات بدل سكن موظفين',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:52',
    ),
    'a66' => 
    array (
      'name' => 'مصروفات بدل مواصلات موظفين',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:52',
    ),
    'a67' => 
    array (
      'name' => 'مصروفات الموظفين',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:52',
    ),
    'a68' => 
    array (
      'name' => 'مصروفات ادارية اخرى',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:52',
    ),
    'a69' => 
    array (
      'name' => 'التامينات الاجتماعية',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:52',
    ),
    'a70' => 
    array (
      'name' => 'مصروفات الادارة العامة',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:52',
    ),
    'a71' => 
    array (
      'name' => 'تكاليف نقل خدمات',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:5',
    ),
    'a72' => 
    array (
      'name' => 'تكاليف نقل خدمات',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a71',
    ),
    'a73' => 
    array (
      'name' => 'مصروفات سفرات اجنبية',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:54',
    ),
    'a74' => 
    array (
      'name' => 'تكاليف متابعة وضمان العاملات خلال فترة العقد',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:54',
    ),
    'a75' => 
    array (
      'name' => 'مصروفات تشغيلية اخرى',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:54',
    ),
    'a76' => 
    array (
      'name' => 'مصروفات سفرات الاتصال - عمالة تأجير',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a75',
    ),
    'a77' => 
    array (
      'name' => 'غرامات تشغيل',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:54',
    ),
    'a78' => 
    array (
      'name' => 'اهلاكات تشغيلية',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:54',
    ),
    'a79' => 
    array (
      'name' => 'مصروفات السيارات',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:54',
    ),
    'a80' => 
    array (
      'name' => 'مصروفات ايواء المتوسط',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:54',
      'legacy_code' => '5450',
    ),
    'a81' => 
    array (
      'name' => 'تكلفة الخدمات الإضافية',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:5',
    ),
    'a82' => 
    array (
      'name' => 'مصاريف تشغيل الخدمة/عمولات توصيل السائقين',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a81',
    ),
    'a83' => 
    array (
      'name' => 'مصروفات تذاكر العاملات',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a81',
    ),
    'a84' => 
    array (
      'name' => 'مصروفات بيعية وتسويقية',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:5',
    ),
    'a85' => 
    array (
      'name' => 'مصروفات تسويق',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a84',
    ),
    'a86' => 
    array (
      'name' => 'مصروفات دعاية واعلان',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a84',
    ),
    'a87' => 
    array (
      'name' => 'عمولات قسم الخارجية',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a84',
    ),
    'a88' => 
    array (
      'name' => 'عمولات مسوقين من الخارج',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a84',
    ),
    'a89' => 
    array (
      'name' => 'تكاليف مبيعات التأجير',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:5',
    ),
    'a90' => 
    array (
      'name' => 'تكلفة استقدام التأجير - الموردين',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a89',
    ),
    'a91' => 
    array (
      'name' => 'تكلفة استقدام عمالة تأجير',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a90',
      'legacy_code' => '5911',
    ),
    'a92' => 
    array (
      'name' => 'تكلفة استقدام عمالة تأجير أخرى 1',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a90',
    ),
    'a93' => 
    array (
      'name' => 'تكلفة استقدام عمالة تأجير أخرى 2',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a90',
    ),
    'a94' => 
    array (
      'name' => 'رسوم حكومية - التأجير',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a89',
    ),
    'a95' => 
    array (
      'name' => 'رسوم تأشيرات عمل التأجير',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a94',
      'legacy_code' => '5921',
    ),
    'a96' => 
    array (
      'name' => 'رسوم إنجاز التأجير',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a94',
      'legacy_code' => '5922',
    ),
    'a97' => 
    array (
      'name' => 'رسوم إقامات التأجير',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a94',
      'legacy_code' => '5923',
    ),
    'a98' => 
    array (
      'name' => 'رسوم رخص عمل التأجير',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a94',
      'legacy_code' => '5924',
    ),
    'a99' => 
    array (
      'name' => 'رسوم تأمين طبي التأجير',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a94',
      'legacy_code' => '5925',
    ),
    'a100' => 
    array (
      'name' => 'رسوم تأمينات اجتماعية التأجير',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a94',
      'legacy_code' => '5926',
    ),
    'a101' => 
    array (
      'name' => 'رواتب تشغيلية - عمالة التأجير',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a89',
    ),
    'a102' => 
    array (
      'name' => 'رواتب عمالة التأجير',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a101',
      'legacy_code' => '5931',
    ),
    'a103' => 
    array (
      'name' => 'مصروفات انجاز تفويض الكترونية',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:522',
    ),
    'a104' => 
    array (
      'name' => 'مصروفات اخرى',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:5',
    ),
    'a105' => 
    array (
      'name' => 'مصروفات متنوعة اخرى',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'n:a104',
    ),
    'a106' => 
    array (
      'name' => 'مصروفات الاهلاك',
      'type' => 'NormalAccount',
      'status' => 'debtor',
      'parent' => 'c:5',
    ),
    'a107' => 
    array (
      'name' => 'مصروفات الاهلاك',
      'type' => 'DepreciationExpenses',
      'status' => 'debtor',
      'parent' => 'n:a106',
    ),
  ),
  'retype_children' => 
  array (
    141 => 'Supplier',
  ),
);
