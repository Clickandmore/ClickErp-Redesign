<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\Admin;

class DeportationProcedureLog extends Model
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        "deportation_procedure_id",
        "admin_id",
        "action_type",
        "field_name",
        "old_value",
        "new_value",
        "description",
        "attachments"
    ];

    protected $casts = [
        "attachments" => "array",
        "created_at" => "datetime"
    ];

    public function deportationProcedure()
    {
        return $this->belongsTo(DeportationProcedure::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function getActionTypeTextAttribute()
    {
        $types = [
            "update" => "تحديث",
            "confirm" => "تأكيد",
            "complete" => "إكمال",
            "create" => "إنشاء",
            "delete" => "حذف",
            "attachment_added" => "إضافة مرفق",
            "attachment_removed" => "حذف مرفق"
        ];

        return $types[$this->action_type] ?? $this->action_type;
    }

    public static function getFieldDisplayName($fieldName)
    {
        $fields = [
            "salary_received" => "استلام الراتب",
            "salary_received_date" => "تاريخ استلام الراتب",
            "has_passport" => "وجود الجواز",
            "passport_date" => "تاريخ الجواز",
            "has_deportation_letter" => "خطاب الترحيل",
            "deportation_letter_date" => "تاريخ خطاب الترحيل",
            "has_settlement_session" => "جلسة التسوية",
            "settlement_date" => "تاريخ جلسة التسوية",
            "has_final_exit" => "الخروج النهائي",
            "final_exit_date" => "تاريخ الخروج النهائي",
            "status" => "الحالة",
            "notes" => "الملاحظات"
        ];

        return $fields[$fieldName] ?? $fieldName;
    }

    public function getFieldNameTextAttribute()
    {
        return self::getFieldDisplayName($this->field_name);
    }

    public static function logUpdate($procedureId, $adminId, $fieldName, $oldValue, $newValue, $description = null, $attachments = null)
    {
        return self::create([
            "deportation_procedure_id" => $procedureId,
            "admin_id" => $adminId,
            "action_type" => "update",
            "field_name" => $fieldName,
            "old_value" => $oldValue,
            "new_value" => $newValue,
            "description" => $description ?? "تم تحديث " . self::getFieldDisplayName($fieldName),
            "attachments" => $attachments
        ]);
    }

    public static function logConfirmation($procedureId, $adminId, $description = null, $attachments = null)
    {
        return self::create([
            "deportation_procedure_id" => $procedureId,
            "admin_id" => $adminId,
            "action_type" => "confirm",
            "description" => $description ?? "تم تأكيد الإجراءات",
            "attachments" => $attachments
        ]);
    }

    public static function logCompletion($procedureId, $adminId, $description = null, $attachments = null)
    {
        return self::create([
            "deportation_procedure_id" => $procedureId,
            "admin_id" => $adminId,
            "action_type" => "complete",
            "description" => $description ?? "تم إكمال الإجراءات",
            "attachments" => $attachments
        ]);
    }

    public static function logAttachmentAdded($procedureId, $adminId, $attachmentName, $description = null)
    {
        return self::create([
            "deportation_procedure_id" => $procedureId,
            "admin_id" => $adminId,
            "action_type" => "attachment_added",
            "field_name" => "attachments",
            "new_value" => $attachmentName,
            "description" => $description ?? "تم إضافة مرفق: {$attachmentName}",
            "attachments" => [$attachmentName]
        ]);
    }
}
