<?php

namespace App\Enums\ingaz;

enum ContractStatusEnum: string
{
    case New = 'new'; // بانتظار الاعتماد
    case AccreditationContracts = 'accreditation_contracts'; // اعتماد العقود
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
    case Trial = 'trial'; // في فترة التجربة
    case InProgress = 'in_progress'; // قيد التنفيذ
    case ClientContracted = 'client_contracted'; // تم التعاقد مع العميل
    case DelegateSelected = 'delegate_selected'; // تم اختيار المندوب

    // الحالات القديمة للتوافق مع البيانات الموجودة
    case UnderWarranty = 'under_warranty'; // تحت الضمان (متروك للتوافق)
    case Closed = 'closed'; // عقد مغلق (متروك للتوافق)

    public function lang()
    {
        return match ($this) {
            self::New => __('ingaz.new'),
            self::Msaned => __('ingaz.msaned'),
            self::Tafwed => __('ingaz.tafwed'),
            self::Taffez => __('ingaz.taffez'),
            self::Tazker => __('ingaz.tazker'),
            self::Arrival => __('ingaz.arrival'),
            self::Completed => __('ingaz.completed'),
            self::Back => __('ingaz.back'),
            self::Sari => __('ingaz.sari'),
            self::Shelter => __('ingaz.shelter'),
            self::PendingMaidSelection => __('ingaz.pending_maid_selection'),
            self::Trial => __('ingaz.trial'),
            self::UnderWarranty => __('ingaz.under_warranty'),
            self::Closed => __('ingaz.closed'),
            self::InProgress => __('ingaz.in_progress'),
            self::ClientContracted => __('ingaz.client_contracted'),
            self::DelegateSelected => __('ingaz.delegate_selected'),
        };
    }

    public function color()
    {
        return match ($this) {
            self::New => 'primary',
            self::Msaned => 'primary',
            self::Tafwed => 'primary',
            self::Taffez => 'primary',
            self::Tazker => 'primary',
            self::Arrival => 'primary',
            self::Completed => 'success',
            self::Back => 'danger',
            self::Sari => 'warning',
            self::Shelter => 'info',
            self::PendingMaidSelection => 'dark',
            self::Trial => 'success',
            self::UnderWarranty => 'info',
            self::Closed => 'danger',
            self::InProgress => 'warning',
            self::ClientContracted => 'primary',
            self::DelegateSelected => 'info',
        };
    }
}
