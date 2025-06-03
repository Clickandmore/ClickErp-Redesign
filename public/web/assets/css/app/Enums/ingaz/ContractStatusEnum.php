<?php

namespace App\Enums\ingaz;

enum ContractStatusEnum: string
{
    case New = 'new'; // بانتظار الاعتماد
    case Msaned = 'msaned'; // عقود مساند
    case Tafwed = 'tafwed'; // تفويض
    case Taffez = 'taffez'; // التفييز
    case Tazker = 'tazker'; // تذكرة
    case UnderWarranty = 'under_warranty'; // تحت الضمان
    case Back = 'back'; // مرتجع
    case Arrival = 'arrival'; // مرحلة الوصول
    case Sari = 'sari'; // عقد ساري
    case Closed = 'closed'; // عقد مغلق - منتهي
    case Shelter = 'shelter'; // عقد ايواء
}
