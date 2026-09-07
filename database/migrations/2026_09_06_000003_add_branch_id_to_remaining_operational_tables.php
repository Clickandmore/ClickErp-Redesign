<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Adds branch ownership to the operational tables reported by branches:audit. */
class AddBranchIdToRemainingOperationalTables extends Migration
{
    private array $tables = [
        'client_import_details',
        'arabia_recruitment_import_rows',
        'legacy_finance_bond_source_rows',
        'maid_bulk_import_rows',
        'arabia_recruitment_financial_references',
        'order_details',
        'contract_infos',
        'maid_external_references',
        'travel_histories',
        'rental_contract_infos',
        'hr_employee',
        'hr_employee_shift',
        'maid_status_change_requests',
        'complaint_attachments',
        'maid_expertises',
        'deportation_procedure_logs',
        'maid_unavailable_records',
        'admin_offices',
        'bank_accounts',
        'banks',
        'deportation_procedures',
        'financial_request_status_logs',
        'hr_employee_files',
        'fn_principle_websites',
        'admins_employees',
        'agent_maid_bulk_import_rows',
        'cancel_contract_requests',
        'client_iamages',
        'crm_client_category',
        'crm_client_poll_answers',
        'crm_client_polls',
        'crm_clients',
        'crm_clients_attachments',
        'crm_follow_client',
        'crm_price_offers',
        'fn_conversions',
        'fn_double_account_details',
        'fn_procurement_lines',
        'fn_procurement_payments',
        'hr_advances',
        'hr_complaint_replies',
        'hr_complaints',
        'hr_daily_work_reports',
        'hr_employee_allowances',
        'hr_employee_attendances',
        'hr_employee_custodies',
        'hr_employee_delays',
        'hr_employee_insurances',
        'hr_employee_permissions',
        'hr_employee_responsible',
        'hr_employee_salary',
        'hr_employee_sanctions',
        'hr_employee_tasks',
        'hr_employee_tax',
        'hr_employee_vacation_balance',
        'hr_employee_vacations',
        'hr_payroll_items',
        'hr_reward_employee',
        'hr_task_replies',
        'istiqdam_settings',
        'known_service_requests',
        'maid_offer',
        'messages',
        'order_notes',
        'professional_applications',
        'recruitment_contract_step_completions',
        'renew_contracts',
        'rental_contract_expiry_reminders',
        'rental_order_payments',
        'return_requests',
        'shelter_living_expense_allocations',
        'shelter_living_expenses',
        'shelter_workers',
        'user_addresses',
        'user_firebase_tokens',
        'user_modules',
        'website_visitor_messages',
        'website_visitors',
        'whatsapp_conversations',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'branch_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->index('branch_id', $this->indexName($tableName));
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'branch_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropIndex($this->indexName($tableName));
                $table->dropColumn('branch_id');
            });
        }
    }

    private function indexName(string $tableName): string
    {
        return substr($tableName . '_branch_id_index', 0, 64);
    }
}
