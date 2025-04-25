<?php

namespace App\Enums\ingaz;

enum OrderEnum: string
{
    case New = 'new'; // جديد
    case Completed = 'completed'; // مكتملة
    case Done = 'done'; // مكتملة
    case Refused = 'refused'; // مرفوضه
    case Return = 'return'; // طلبات الاسترجاع


    // case Paid = 'paid';  // مدفوع
    // case Refused = 'refused'; // ملغي
    // case Msaned = 'msaned'; // تم الربط في مساند
    // case Training = 'training'; // تحت الاجراء والتدريب
    // case Arrived = 'arrived'; // تم وصول العمالة
    // حالات الايجار
    // case Started = 'started'; // بدء التعاقد
    // case Escape = 'escape'; // هروب
    // case Deportation = 'deportation'; // تم الترحيل
    // //** NEW ADDED D18M7Y24 => IS */
    // case Delivered = 'delivered';

}
