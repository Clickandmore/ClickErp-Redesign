<?php

namespace App\Enums\ingaz;

enum ContractStatusEnum: string
{
    case New = 'new'; // بانتظار الاعتماد
    case Msaned = 'msaned'; // عقود مساند
    case Tafwed = 'tafwed'; // تفويض
    case Taffez = 'taffez'; // التفييز
    case Tazker = 'tazker'; // تذكرة
    case Arrival = 'arrival'; // مرحلة الوصول
    case Completed = 'completed'; // عقد مكتمل (يشمل الضمان)
    case Back = 'back'; // مرتجع
    case Sari = 'sari'; // عقد ساري
    case Shelter = 'shelter'; // عقد ايواء
    case PendingMaidSelection = 'pending_maid_selection'; // بانتظار اختيار العاملة

    // الحالات القديمة للتوافق مع البيانات الموجودة
    case UnderWarranty = 'under_warranty'; // تحت الضمان (متروك للتوافق)
    case Closed = 'closed'; // عقد مغلق (متروك للتوافق)
}
