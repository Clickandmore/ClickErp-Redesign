<?php

namespace Database\Seeders\hr;

use DB;
use Illuminate\Database\Seeder;

class HRTableSeeder extends Seeder
{

    public function run(): void
    {

        $tables = [
          /*  'admins',*/
            'hr_allowances',
            'hr_department',
            'hr_employee',
            'hr_employee_allowances',
            'hr_employee_attendances',
            'hr_employee_delays',
            'hr_employee_files',
            'hr_employee_insurances',
            'hr_employee_permissions',
            'hr_employee_responsible',
            'hr_employee_sanctions',
            'hr_employee_shift',
            'hr_employee_tax',
            'hr_employee_vacation_balance',
            'hr_insurances',
            'hr_jobs',
            'hr_official_vacation',
            'hr_permissions',
            'hr_perrmission_setting',
            'hr_rewards',
            'hr_sanctions',
            'hr_shifts',
            'hr_shift_days',
            'hr_shift_roles',
            'hr_tax',
            'hr_vacations',
            'hr_vacation_years'
        ];

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        // Enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');




        ################# Create HR Jobs ##############
        $hr_jobs = array(
            array('id' => '2','title' => 'موظف مشتريات','created_at' => '2024-09-17 09:18:17','updated_at' => '2024-09-18 11:23:29'),
            array('id' => '3','title' => 'أخصائي موارد بشرية','created_at' => '2024-09-18 11:23:12','updated_at' => '2024-09-18 11:23:12'),
            array('id' => '4','title' => 'موظف استقبال','created_at' => '2024-09-18 11:23:36','updated_at' => '2024-09-18 11:23:36'),
            array('id' => '5','title' => 'محاسب مالي','created_at' => '2024-09-18 11:23:43','updated_at' => '2024-09-18 11:23:43'),
            array('id' => '6','title' => 'مدير تنفيذي','created_at' => '2024-09-18 11:23:49','updated_at' => '2024-09-18 11:23:49'),
            array('id' => '8','title' => 'مهندس IT','created_at' => '2024-09-18 12:40:09','updated_at' => '2024-09-18 12:40:09')
        );
        DB::table('hr_jobs')->insert($hr_jobs);
        #######################################################


        ################# Create HR Insurances ##############
        $hr_insurances = array(
            array('id' => '2','title' => 'لا يخضع','type' => '1','value' => '0.00','emp_value' => '0','company_value' => '0','status' => '1','created_at' => '2024-09-17 10:20:59','updated_at' => '2024-09-17 10:20:59'),
            array('id' => '11','title' => 'تأمين طبي','type' => '1','value' => '30.00','emp_value' => '18','company_value' => '12','status' => '1','created_at' => '2024-09-18 11:32:42','updated_at' => '2024-09-18 11:33:58'),
            array('id' => '12','title' => 'تامين اجتماعى','type' => '0','value' => '25.00','emp_value' => '10','company_value' => '90','status' => '1','created_at' => '2024-09-18 11:33:36','updated_at' => '2024-09-18 11:33:36'),
            array('id' => '13','title' => 'تأمين اجهزة للشركة','type' => '1','value' => '100.00','emp_value' => '100','company_value' => '0','status' => '0','created_at' => '2024-09-18 11:34:45','updated_at' => '2024-09-18 11:34:45')
        );
        DB::table('hr_insurances')->insert($hr_insurances);
        #######################################################


        ################# Create HR Allowances ########
        $hr_allowances = array(
            array('id' => '1','title' => 'بدل سكن','type' => '3','value' => '5.00','created_at' => '2024-09-17 09:19:17','updated_at' => '2024-09-18 11:24:27'),
            array('id' => '2','title' => 'بدل نقل','type' => '2','value' => '500.00','created_at' => '2024-09-17 10:21:41','updated_at' => '2024-09-18 11:24:16'),
            array('id' => '4','title' => 'بدل يومي للسفر','type' => '1','value' => '1.00','created_at' => '2024-09-18 11:26:59','updated_at' => '2024-09-18 11:26:59')
        );
        DB::table('hr_allowances')->insert($hr_allowances);
        ###############################################


        ################# Create HR Vacation Years ########
        $hr_vacation_years = array(
            array('id' => '1','title' => 'اجازات سنة 2024','from_date' => '2024-01-01','to_date' => '2024-12-31','status' => 1 ,'created_at' => '2024-09-17 09:19:17','updated_at' => '2024-09-18 11:24:27'),
            array('id' => '2','title' => 'اجازات سنة 2025','from_date' => '2025-01-01','to_date' => '2025-12-31','status' => 0 ,'created_at' => '2024-09-17 09:19:17','updated_at' => '2024-09-18 11:24:27'),
        );
        DB::table('hr_vacation_years')->insert($hr_vacation_years);
        ###############################################


        ################# Create HR Department ########
        $hr_department = array(
            array('id' => '1','title' => 'إدارة تقنية المعلومات','created_at' => '2024-09-17 09:17:38','updated_at' => '2024-09-18 11:21:51'),
            array('id' => '4','title' => 'الادارة المالية ','created_at' => '2024-09-18 11:19:25','updated_at' => '2024-09-18 11:19:25'),
            array('id' => '5','title' => 'إدارة التشغيل','created_at' => '2024-09-18 11:19:57','updated_at' => '2024-09-18 11:19:57'),
            array('id' => '6','title' => 'الموارد البشرية','created_at' => '2024-09-18 11:21:10','updated_at' => '2024-09-18 11:21:10'),
            array('id' => '7','title' => 'الادارة العليا','created_at' => '2024-09-18 11:21:19','updated_at' => '2024-09-18 11:21:58'),
            array('id' => '8','title' => 'قسم المبيعات','created_at' => '2024-09-18 11:22:18','updated_at' => '2024-09-18 11:22:18')
        );
        DB::table('hr_department')->insert($hr_department);
        ###############################################



        ################# Create HR permissions ##############
        $hr_permissions = array(
            array('id' => '1','title' => 'اذن تأخير','created_at' => '2024-09-17 09:21:12','updated_at' => '2024-09-18 11:30:40'),
            array('id' => '2','title' => 'اذن خروج مبكر','created_at' => '2024-09-18 11:30:30','updated_at' => '2024-09-18 11:30:30')
        );
        DB::table('hr_permissions')->insert($hr_permissions);
        #######################################################



        ################# Create HR Official Vacation ##############
        $hr_official_vacation = array(
            array('id' => '1','name' => 'عطلة عيد الاضحى ','from_date' => '2024-11-01','to_date' => '2024-11-06','created_at' => '2024-09-17 09:24:44','updated_at' => '2024-09-17 09:24:44'),
            array('id' => '2','name' => 'اليوم الوطنى ','from_date' => '2025-01-01','to_date' => '2025-01-01','created_at' => '2024-09-18 12:27:56','updated_at' => '2024-09-18 12:27:56'),
            array('id' => '3','name' => 'عطلة عيدالفطر ','from_date' => '2024-09-15','to_date' => '2024-09-15','created_at' => '2024-09-18 12:28:28','updated_at' => '2024-09-18 12:28:28'),
            array('id' => '4','name' => 'عطلة يوم التاسيس ','from_date' => '2025-04-10','to_date' => '2025-04-10','created_at' => '2024-09-18 12:29:24','updated_at' => '2024-09-18 12:29:41')
        );
        DB::table('hr_official_vacation')->insert($hr_official_vacation);
        #######################################################


        ################# Create HR Rewards ##############
        $hr_rewards = array(
            array('id' => '1','title' => 'مكافاة موظف الشهر','value' => '10.00','type' => '3','status' => '1','created_at' => '2024-09-17 09:22:37','updated_at' => '2024-09-18 11:37:48'),
            array('id' => '2','title' => 'نهاية خدمة','value' => '10000.00','type' => '2','status' => '1','created_at' => '2024-09-18 11:37:33','updated_at' => '2024-09-18 11:37:33'),
            array('id' => '3','title' => 'انجاز مهمة استثنائية','value' => '3.00','type' => '1','status' => '1','created_at' => '2024-09-18 11:38:16','updated_at' => '2024-09-18 11:38:16')
        );
        DB::table('hr_rewards')->insert($hr_rewards);
        #######################################################

        ################# Create HR Sanctions ##############
        $hr_sanctions = array(
            array('id' => '1','title' => 'خصم تأخير','type' => '2','value' => '20.00','is_delays' => '1','created_at' => '2024-09-17 09:19:42','updated_at' => '2024-09-18 11:27:49'),
            array('id' => '3','title' => 'خصم خروج مبكر','type' => '2','value' => '25.00','is_delays' => '0','created_at' => '2024-09-18 11:27:29','updated_at' => '2024-09-18 11:27:29'),
            array('id' => '4','title' => 'خصم غياب يوم دون اذن','type' => '3','value' => '3.00','is_delays' => '0','created_at' => '2024-09-18 11:28:34','updated_at' => '2024-09-18 11:41:35'),
            array('id' => '5','title' => 'اهمال في العمل','type' => '1','value' => '5.00','is_delays' => '0','created_at' => '2024-09-18 11:28:58','updated_at' => '2024-09-18 11:28:58'),
            array('id' => '6','title' => 'بدون','type' => '2','value' => '0.00','is_delays' => '1','created_at' => '2024-09-18 12:23:27','updated_at' => '2024-09-18 12:23:27')
        );
        DB::table('hr_sanctions')->insert($hr_sanctions);
        #######################################################


        ################# Create HR Shifts ##############
        $hr_shifts = array(
            array('id' => '2','name' => 'دوام الصباح','from_hour' => '07:30:00','to_hour' => '16:00:00','month_days' => '30','status' => '1','absence_sanctions_id' => '4','created_at' => '2024-09-18 11:40:04','updated_at' => '2024-09-18 12:07:47'),
            array('id' => '3','name' => 'دوام عمل مسائي','from_hour' => '16:30:00','to_hour' => '22:00:00','month_days' => '30','status' => '1','absence_sanctions_id' => '4','created_at' => '2024-09-18 12:27:04','updated_at' => '2024-09-18 12:27:04')
        );
        DB::table('hr_shifts')->insert($hr_shifts);
        #####################################################


        ################# Create HR Shift Days ##############
        $hr_shift_days = array(
            array('id' => '13','days' => 'SUN','shift_id' => '2','created_at' => '2024-09-18 12:07:47','updated_at' => '2024-09-18 12:07:47'),
            array('id' => '14','days' => 'MON','shift_id' => '2','created_at' => '2024-09-18 12:07:47','updated_at' => '2024-09-18 12:07:47'),
            array('id' => '15','days' => 'TUE','shift_id' => '2','created_at' => '2024-09-18 12:07:47','updated_at' => '2024-09-18 12:07:47'),
            array('id' => '16','days' => 'WED','shift_id' => '2','created_at' => '2024-09-18 12:07:47','updated_at' => '2024-09-18 12:07:47'),
            array('id' => '17','days' => 'THU','shift_id' => '2','created_at' => '2024-09-18 12:07:47','updated_at' => '2024-09-18 12:07:47'),
            array('id' => '18','days' => 'SUN','shift_id' => '3','created_at' => '2024-09-18 12:27:04','updated_at' => '2024-09-18 12:27:04'),
            array('id' => '19','days' => 'MON','shift_id' => '3','created_at' => '2024-09-18 12:27:04','updated_at' => '2024-09-18 12:27:04'),
            array('id' => '20','days' => 'TUE','shift_id' => '3','created_at' => '2024-09-18 12:27:04','updated_at' => '2024-09-18 12:27:04'),
            array('id' => '21','days' => 'WED','shift_id' => '3','created_at' => '2024-09-18 12:27:04','updated_at' => '2024-09-18 12:27:04'),
            array('id' => '22','days' => 'THU','shift_id' => '3','created_at' => '2024-09-18 12:27:04','updated_at' => '2024-09-18 12:27:04')
        );
        DB::table('hr_shift_days')->insert($hr_shift_days);
        #####################################################

        ################# Create HR Shift Roles ##############
        $hr_shift_roles = array(
            array('id' => '3','minutes' => '30','shift_id' => '2','sanctions_id' => '1','created_at' => '2024-09-18 12:07:47','updated_at' => '2024-09-18 12:07:47'),
            array('id' => '4','minutes' => '30','shift_id' => '3','sanctions_id' => '6','created_at' => '2024-09-18 12:27:04','updated_at' => '2024-09-18 12:27:04')
        );
        DB::table('hr_shift_roles')->insert($hr_shift_roles);
        #####################################################


        ################# Create HR Tax ##############
        $hr_tax = array(
            array('id' => '1','title' => 'ضريبة الدخل ','value' => '14.00','status' => '1','created_at' => '2024-09-17 09:22:13','updated_at' => '2024-09-18 11:35:56'),
            array('id' => '2','title' => 'لا يخضع','value' => '0.00','status' => '1','created_at' => '2024-09-17 10:20:38','updated_at' => '2024-09-17 10:20:38'),
            array('id' => '3','title' => 'ضريبة دخل مرتفع','value' => '5.00','status' => '0','created_at' => '2024-09-18 11:37:06','updated_at' => '2024-09-18 11:37:06')
        );
        DB::table('hr_tax')->insert($hr_tax);
        #####################################################


        ################# Create HR Vacations ##############
        $hr_vacations = array(
            array('id' => '5','title' => 'عارضة','number_of_days' => '7','with_salary' => '0','created_at' => '2024-09-18 09:40:30','updated_at' => '2024-09-18 09:40:30'),
            array('id' => '6','title' => 'اعتيادى ','number_of_days' => '5','with_salary' => '1','created_at' => '2024-09-18 09:52:57','updated_at' => '2024-09-18 09:52:57'),
            array('id' => '7','title' => 'جواز ','number_of_days' => '21','with_salary' => '0','created_at' => '2024-09-18 09:53:05','updated_at' => '2024-09-18 11:29:45'),
            array('id' => '8','title' => 'مرضيى','number_of_days' => '10','with_salary' => '0','created_at' => '2024-09-18 11:29:31','updated_at' => '2024-09-18 11:29:31')
        );
        DB::table('hr_vacations')->insert($hr_vacations);
        #####################################################



        ################# Create HR Employee ########
        $hr_employee = array(
            array('id' => '79','name' => 'محمود ابراهيم ','admin_id' => NULL,'image' => 'employees/UPncNLq0AI1726655668.webp','code' => '30010','phone' => '509082341','birthday' => '1997-06-18','email' => 'elkomy@gmail.com','national_num' => '30002111700159','address' => 'المنوفية شبين الكوم البتانون','hired_date' => '2024-04-01','contract_end_date' => '2025-04-30','salary' => '12000','net_salary' => '11402','status' => '1','deparment_id' => '1','job_id' => '8','country_id' => '50','created_at' => '2024-09-18 13:34:28','updated_at' => '2024-09-18 13:34:29'),
            array('id' => '80','name' => 'احمد سعد','admin_id' => NULL,'image' => 'employees/bBcazkqhdx1726655909.webp','code' => '10043','phone' => '509082342','birthday' => '1998-06-07','email' => 'saad@gmail.com','national_num' => '3223442424224','address' => 'المنوفية شنوان','hired_date' => '2023-08-03','contract_end_date' => '2025-09-23','salary' => '9000','net_salary' => '9950','status' => '1','deparment_id' => '6','job_id' => '3','country_id' => '50','created_at' => '2024-09-18 13:38:29','updated_at' => '2024-09-18 13:38:30'),
            array('id' => '81','name' => 'احمد طارق عباس يحيي','admin_id' => NULL,'image' => 'employees/VmifHurZhH1726656086.webp','code' => '90023','phone' => '509082343','birthday' => '2000-06-14','email' => 'ahmedtarekya100@gmail.com','national_num' => '30006141700858','address' => 'المنوفية شبين الكوم البتانون امام مول الحاج عبودة العشماوي مباشرة شقة 212','hired_date' => '2022-01-18','contract_end_date' => '2026-01-18','salary' => '15000','net_salary' => '12082','status' => '1','deparment_id' => '8','job_id' => '2','country_id' => '50','created_at' => '2024-09-18 13:41:26','updated_at' => '2024-09-18 16:02:39'),
            array('id' => '82','name' => 'عماد مجدي','admin_id' => NULL,'image' => 'employees/CDX9Ut18p61726656261.webp','code' => '20192','phone' => '509082345','birthday' => '1981-03-14','email' => 'gupicapi@mailinator.com','national_num' => '134443122834','address' => 'القاهرة شارع المعز عمارة 2','hired_date' => '2023-02-18','contract_end_date' => '2026-06-18','salary' => '14000','net_salary' => '14966.67','status' => '1','deparment_id' => '4','job_id' => '5','country_id' => '50','created_at' => '2024-09-18 13:44:21','updated_at' => '2024-09-18 13:44:21'),
            array('id' => '83','name' => 'محمد رضوان','admin_id' => NULL,'image' => 'employees/pCP6biwejB1726656379.webp','code' => '49922','phone' => '509082346','birthday' => '1993-02-18','email' => 'mo-radwan@gmail.com','national_num' => '322333131424224','address' => 'الرياض السعودية','hired_date' => '2024-01-18','contract_end_date' => '2024-12-31','salary' => '3500','net_salary' => '3801.67','status' => '1','deparment_id' => '8','job_id' => '6','country_id' => '142','created_at' => '2024-09-18 13:46:19','updated_at' => '2024-09-18 13:46:19'),
            array('id' => '84','name' => 'محمد المالكى ','admin_id' => NULL,'image' => 'employees/4qSN3LIffy1726661988.webp','code' => '1333','phone' => '509082347','birthday' => '1995-02-18','email' => 'ashry@gmail.com','national_num' => '32234421333224','address' => 'البتانون شارع انور السادات','hired_date' => '2021-02-18','contract_end_date' => '2028-07-19','salary' => '15000','net_salary' => '13400','status' => '1','deparment_id' => '7','job_id' => '6','country_id' => '50','created_at' => '2024-09-18 15:19:48','updated_at' => '2024-09-18 15:19:48'),
            array('id' => '85','name' => 'محمد حلمى ','admin_id' => NULL,'image' => 'employees/wuaYkrfBhl1726732389.webp','code' => '13332','phone' => '509082348','birthday' => '1999-01-19','email' => 'salm@gmail.com','national_num' => '3223498324224','address' => 'الرياض - شارع الملك فهد حربي','hired_date' => '2018-10-19','contract_end_date' => '2026-06-19','salary' => '12000','net_salary' => '12400','status' => '1','deparment_id' => '1','job_id' => '8','country_id' => '142','created_at' => '2024-09-19 10:53:10','updated_at' => '2024-09-19 10:54:13')
        );
        DB::table('hr_employee')->insert($hr_employee);
        ###############################################

        ################# Create Admins #############
        DB::table('admins')->whereIn("id",['29','30','31','32',])->delete();
        ###############################################

        ################# Create Admins #############
        $admins = array(
            array('id' => '29','name' => 'محمود ابراهيم','phone' => '509082341','email' => 'elkomy@gmail.com','email_verified_at' => NULL,'password' => '$2y$10$bYAqzYux71suSyEDmEzCg.NzKk1Y/Wc4fJKNhvd.H0ViMyBhn4a2.','image' => 'images/admin/YRBU9sItjs1726655669.webp','admin_type' => '6','remember_token' => NULL,'created_at' => '2024-09-18 13:34:29','updated_at' => '2024-09-18 13:34:29'),
            array('id' => '30','name' => 'احمد سعد','phone' => '509082342','email' => 'saad@gmail.com','email_verified_at' => NULL,'password' => '$2y$10$.RZ.746Fwl4rQaFzEzefkOv8CgB/TUyh2EHV6rPvHwVbperzMtZE6','image' => 'images/admin/gppLm5lEkP1726655910.webp','admin_type' => '6','remember_token' => NULL,'created_at' => '2024-09-18 13:38:30','updated_at' => '2024-09-18 13:38:30'),
            array('id' => '31','name' => 'احمد طارق عباس يحيي','phone' => '509082343','email' => 'ahmedtarekya100@gmail.com','email_verified_at' => NULL,'password' => '$2y$10$Uk4YzM.wkeHwuirRFiZ/k.V1E9.BylkvQoKxoycRI9v.bIYseFGcS','image' => 'images/admin/o2BNN7WhH71726656086.webp','admin_type' => '6','remember_token' => NULL,'created_at' => '2024-09-18 13:41:26','updated_at' => '2024-09-18 13:41:26'),
            array('id' => '32','name' => 'محمد حلمى','phone' => '509082348','email' => 'salm@gmail.com','email_verified_at' => NULL,'password' => '$2y$10$jw/cafOPGuX5COfNJJagT.x8o.EY3mOPKnapkIbaghEAg4ltcx9va','image' => 'images/admin/YYkRBWwu4h1726732390.webp','admin_type' => '6','remember_token' => NULL,'created_at' => '2024-09-19 10:53:10','updated_at' => '2024-09-19 10:53:10')
        );
        DB::table('admins')->insert($admins);

        ###############################################

        ################# ADD Admins To Employee #############
        DB::table('hr_employee')->where("id",'79')->update(["admin_id"=>"29"]);
        DB::table('hr_employee')->where("id",'80')->update(["admin_id"=>"30"]);
        DB::table('hr_employee')->where("id",'81')->update(["admin_id"=>"31"]);
        DB::table('hr_employee')->where("id",'85')->update(["admin_id"=>"32"]);


        ###############################################

        ################# Create HR Employee Allowances ########
        $hr_employee_allowances = array(
            array('id' => '96','employee_id' => '79','allowances_id' => '1','type' => '3','value' => '5.00','created_at' => '2024-09-18 13:34:28','updated_at' => '2024-09-18 13:34:28'),
            array('id' => '97','employee_id' => '79','allowances_id' => '2','type' => '2','value' => '500.00','created_at' => '2024-09-18 13:34:28','updated_at' => '2024-09-18 13:34:28'),
            array('id' => '98','employee_id' => '80','allowances_id' => '1','type' => '3','value' => '5.00','created_at' => '2024-09-18 13:38:29','updated_at' => '2024-09-18 13:38:29'),
            array('id' => '99','employee_id' => '80','allowances_id' => '2','type' => '2','value' => '500.00','created_at' => '2024-09-18 13:38:29','updated_at' => '2024-09-18 13:38:29'),
            array('id' => '103','employee_id' => '82','allowances_id' => '2','type' => '2','value' => '500.00','created_at' => '2024-09-18 13:44:21','updated_at' => '2024-09-18 13:44:21'),
            array('id' => '104','employee_id' => '82','allowances_id' => '4','type' => '1','value' => '1.00','created_at' => '2024-09-18 13:44:21','updated_at' => '2024-09-18 13:44:21'),
            array('id' => '105','employee_id' => '83','allowances_id' => '1','type' => '3','value' => '5.00','created_at' => '2024-09-18 13:46:19','updated_at' => '2024-09-18 13:46:19'),
            array('id' => '106','employee_id' => '83','allowances_id' => '2','type' => '2','value' => '500.00','created_at' => '2024-09-18 13:46:19','updated_at' => '2024-09-18 13:46:19'),
            array('id' => '107','employee_id' => '83','allowances_id' => '4','type' => '1','value' => '1.00','created_at' => '2024-09-18 13:46:19','updated_at' => '2024-09-18 13:46:19'),
            array('id' => '108','employee_id' => '84','allowances_id' => '2','type' => '2','value' => '500.00','created_at' => '2024-09-18 15:19:48','updated_at' => '2024-09-18 15:19:48'),
            array('id' => '109','employee_id' => '81','allowances_id' => '1','type' => '3','value' => '5.00','created_at' => '2024-09-18 16:02:39','updated_at' => '2024-09-18 16:02:39'),
            array('id' => '110','employee_id' => '81','allowances_id' => '2','type' => '2','value' => '500.00','created_at' => '2024-09-18 16:02:39','updated_at' => '2024-09-18 16:02:39'),
            array('id' => '111','employee_id' => '81','allowances_id' => '4','type' => '1','value' => '1.00','created_at' => '2024-09-18 16:02:39','updated_at' => '2024-09-18 16:02:39'),
            array('id' => '113','employee_id' => '85','allowances_id' => '2','type' => '2','value' => '500.00','created_at' => '2024-09-19 10:54:13','updated_at' => '2024-09-19 10:54:13')
        );
        DB::table('hr_employee_allowances')->insert($hr_employee_allowances);
        ###############################################


        ################# Create HR Employee Attendances ########
        $hr_employee_attendances = array(
            array('id' => '44','date' => '2024-09-19','type' => '2','attendance_time' => '08:50:00','leave_time' => '00:00:00','month_year' => '09-2024','shift_id' => '2','employee_id' => '79','have_permission' => '1','created_at' => '2024-09-19 08:50:43','updated_at' => '2024-09-19 08:51:13'),
            array('id' => '46','date' => '2024-09-20','type' => '1','attendance_time' => '10:12:00','leave_time' => '11:00:00','month_year' => '09-2024','shift_id' => '3','employee_id' => '81','have_permission' => '0','created_at' => '2024-09-19 09:31:27','updated_at' => '2024-09-19 09:32:14'),
            array('id' => '47','date' => '2024-09-19','type' => '1','attendance_time' => '09:26:00','leave_time' => '14:50:00','month_year' => '09-2024','shift_id' => '2','employee_id' => '82','have_permission' => '0','created_at' => '2024-09-19 09:47:31','updated_at' => '2024-09-19 09:47:31')
        );
        DB::table('hr_employee_attendances')->insert($hr_employee_attendances);
        ###############################################

        ################# Create HR Employee Delays ########
        $hr_employee_delays = array(
            array('id' => '1','month_year' => '09-2024','value' => '360','employee_id' => '79','employee_attendances_id' => '44','sanction_id' => '4','created_at' => '2024-09-19 08:50:43','updated_at' => '2024-09-19 08:50:43'),
            array('id' => '2','month_year' => '09-2024','value' => '420','employee_id' => '82','employee_attendances_id' => '47','sanction_id' => '4','created_at' => '2024-09-19 09:47:31','updated_at' => '2024-09-19 09:47:31')
        );
        DB::table('hr_employee_delays')->insert($hr_employee_delays);
        ###############################################

        ################# Create HR Employee Files ########
        $hr_employee_files = array(
            array('id' => '11','title' => 'سيرة ذاتيه','file' => 'employees/nCvaiWtj6v1726655668.webp','employee_id' => '79','created_at' => '2024-09-18 13:34:28','updated_at' => '2024-09-18 13:34:28'),
            array('id' => '12','title' => 'جواز سفر','file' => 'employees/NqonKC8Hkh1726655909.webp','employee_id' => '80','created_at' => '2024-09-18 13:38:29','updated_at' => '2024-09-18 13:38:29'),
            array('id' => '13','title' => 'بطاقة هوية ','file' => 'employees/S5edRQtbj01726656086.webp','employee_id' => '81','created_at' => '2024-09-18 13:41:26','updated_at' => '2024-09-18 16:02:39')
        );
        DB::table('hr_employee_files')->insert($hr_employee_files);
        ###############################################


        ################# Create HR Employee Insurances ########
        $hr_employee_insurances = array(
            array('id' => '67','employee_id' => '79','insurances_id' => '11','value' => '18','created_at' => '2024-09-18 13:34:28','updated_at' => '2024-09-18 13:34:28'),
            array('id' => '68','employee_id' => '80','insurances_id' => '2','value' => '0','created_at' => '2024-09-18 13:38:29','updated_at' => '2024-09-18 13:38:29'),
            array('id' => '71','employee_id' => '82','insurances_id' => '2','value' => '0','created_at' => '2024-09-18 13:44:21','updated_at' => '2024-09-18 13:44:21'),
            array('id' => '72','employee_id' => '84','insurances_id' => '2','value' => '0','created_at' => '2024-09-18 15:19:48','updated_at' => '2024-09-18 15:19:48'),
            array('id' => '73','employee_id' => '81','insurances_id' => '11','value' => '18','created_at' => '2024-09-18 16:02:39','updated_at' => '2024-09-18 16:02:39'),
            array('id' => '74','employee_id' => '81','insurances_id' => '12','value' => '17','created_at' => '2024-09-18 16:02:39','updated_at' => '2024-09-18 16:02:39'),
            array('id' => '75','employee_id' => '85','insurances_id' => '13','value' => '100','created_at' => '2024-09-19 10:54:13','updated_at' => '2024-09-19 10:54:13')
        );
        DB::table('hr_employee_insurances')->insert($hr_employee_insurances);
        ###############################################


        ################# Create HR Employee Permissions ########
        $hr_employee_permissions = array(
            array('id' => '1','employee_id' => '79','permission_id' => '1','date' => '2024-09-19','time' => '35','reason' => 'عطل موصلات','status' => '1','created_at' => '2024-09-19 08:51:13','updated_at' => '2024-09-19 08:51:13')
        );
        DB::table('hr_employee_permissions')->insert($hr_employee_permissions);
        ###############################################

        ################# Create HR Employee Responsible ########
        $hr_employee_responsible = array(
            array('id' => '1','employee_id' => '73','responsible_employee_id' => '72','created_at' => NULL,'updated_at' => NULL),
            array('id' => '2','employee_id' => '74','responsible_employee_id' => '70','created_at' => NULL,'updated_at' => NULL),
            array('id' => '3','employee_id' => '75','responsible_employee_id' => '74','created_at' => NULL,'updated_at' => NULL),
            array('id' => '4','employee_id' => '75','responsible_employee_id' => '73','created_at' => NULL,'updated_at' => NULL),
            array('id' => '5','employee_id' => '75','responsible_employee_id' => '70','created_at' => NULL,'updated_at' => NULL),
            array('id' => '6','employee_id' => '75','responsible_employee_id' => '72','created_at' => NULL,'updated_at' => NULL),
            array('id' => '7','employee_id' => '76','responsible_employee_id' => '74','created_at' => NULL,'updated_at' => NULL),
            array('id' => '9','employee_id' => '81','responsible_employee_id' => '80','created_at' => NULL,'updated_at' => NULL),
            array('id' => '10','employee_id' => '81','responsible_employee_id' => '79','created_at' => NULL,'updated_at' => NULL),
            array('id' => '11','employee_id' => '82','responsible_employee_id' => '81','created_at' => NULL,'updated_at' => NULL),
            array('id' => '12','employee_id' => '82','responsible_employee_id' => '79','created_at' => NULL,'updated_at' => NULL),
            array('id' => '13','employee_id' => '83','responsible_employee_id' => '82','created_at' => NULL,'updated_at' => NULL),
            array('id' => '14','employee_id' => '85','responsible_employee_id' => '82','created_at' => NULL,'updated_at' => NULL)
        );
        DB::table('hr_employee_responsible')->insert($hr_employee_responsible);
        ###############################################


        ################# Create HR Employee Sanctions ########
        $hr_employee_sanctions = array(
            array('id' => '1','employee_id' => '80','sanction_id' => '5','value' => '1658.33','date' => '2024-09-19','with_salary' => '1','reason' => 'اهمال شديد ادي الي تلف الاجهزة','status' => '1','created_at' => '2024-09-19 10:34:45','updated_at' => '2024-09-19 10:34:45'),
            array('id' => '2','employee_id' => '79','sanction_id' => '3','value' => '25.00','date' => '2024-09-19','with_salary' => '1','reason' => 'خرج دون اذن من احد','status' => '0','created_at' => '2024-09-19 10:36:38','updated_at' => '2024-09-19 10:36:38')
        );
        DB::table('hr_employee_sanctions')->insert($hr_employee_sanctions);
        ###############################################


        ################# Create HR Employee Shift ########
        $hr_employee_shift = array(
            array('id' => '42','employee_id' => '79','shift_id' => '2','created_at' => '2024-09-18 13:34:28','updated_at' => '2024-09-18 13:34:28'),
            array('id' => '43','employee_id' => '80','shift_id' => '2','created_at' => '2024-09-18 13:38:29','updated_at' => '2024-09-18 13:38:29'),
            array('id' => '44','employee_id' => '81','shift_id' => '3','created_at' => '2024-09-18 13:41:26','updated_at' => '2024-09-18 13:41:26'),
            array('id' => '45','employee_id' => '82','shift_id' => '2','created_at' => '2024-09-18 13:44:21','updated_at' => '2024-09-18 13:44:21'),
            array('id' => '46','employee_id' => '83','shift_id' => '3','created_at' => '2024-09-18 13:46:19','updated_at' => '2024-09-18 13:46:19'),
            array('id' => '47','employee_id' => '84','shift_id' => '2','created_at' => '2024-09-18 15:19:48','updated_at' => '2024-09-18 15:19:48'),
            array('id' => '48','employee_id' => '85','shift_id' => '3','created_at' => '2024-09-19 10:53:10','updated_at' => '2024-09-19 10:53:10')
        );
        DB::table('hr_employee_shift')->insert($hr_employee_shift);
        #######################################################


        ################# Create HR Employee Tax ##############
        $hr_employee_tax = array(
            array('id' => '30','employee_id' => '79','tax_id' => '1','created_at' => NULL,'updated_at' => NULL),
            array('id' => '32','employee_id' => '82','tax_id' => '2','created_at' => NULL,'updated_at' => NULL),
            array('id' => '33','employee_id' => '83','tax_id' => '1','created_at' => NULL,'updated_at' => NULL),
            array('id' => '34','employee_id' => '84','tax_id' => '1','created_at' => NULL,'updated_at' => NULL),
            array('id' => '35','employee_id' => '81','tax_id' => '1','created_at' => NULL,'updated_at' => NULL)
        );
        DB::table('hr_employee_tax')->insert($hr_employee_tax);
        #######################################################


        ################# Create HR Employee Vacations ##############
        $hr_employee_vacation_balance = array(
            array('id' => '58','employee_id' => '79','vacation_id' => '7','total_days' => '21','token_days' => '15', 'vacation_year_id' => 1,'remain_days' => '6','created_at' => NULL,'updated_at' => '2024-09-18 14:41:13'),
            array('id' => '59','employee_id' => '79','vacation_id' => '5','total_days' => '7','token_days' => '1', 'vacation_year_id' => 1,'remain_days' => '6','created_at' => NULL,'updated_at' => NULL),
            array('id' => '60','employee_id' => '80','vacation_id' => '8','total_days' => '10','token_days' => '3', 'vacation_year_id' => 1,'remain_days' => '7','created_at' => NULL,'updated_at' => NULL),
            array('id' => '61','employee_id' => '80','vacation_id' => '7','total_days' => '21','token_days' => '19', 'vacation_year_id' => 1,'remain_days' => '2','created_at' => NULL,'updated_at' => NULL),
            array('id' => '62','employee_id' => '80','vacation_id' => '6','total_days' => '5','token_days' => '3', 'vacation_year_id' => 1,'remain_days' => '2','created_at' => NULL,'updated_at' => NULL),
            array('id' => '66','employee_id' => '82','vacation_id' => '8','total_days' => '10','token_days' => '10', 'vacation_year_id' => 1,'remain_days' => '0','created_at' => NULL,'updated_at' => '2024-09-18 16:03:40'),
            array('id' => '67','employee_id' => '82','vacation_id' => '6','total_days' => '5','token_days' => '2', 'vacation_year_id' => 1,'remain_days' => '3','created_at' => NULL,'updated_at' => NULL),
            array('id' => '68','employee_id' => '83','vacation_id' => '5','total_days' => '7','token_days' => '5', 'vacation_year_id' => 1,'remain_days' => '2','created_at' => NULL,'updated_at' => '2024-09-19 08:56:45'),
            array('id' => '69','employee_id' => '83','vacation_id' => '7','total_days' => '21','token_days' => '1', 'vacation_year_id' => 1,'remain_days' => '20','created_at' => NULL,'updated_at' => NULL),
            array('id' => '70','employee_id' => '84','vacation_id' => '7','total_days' => '21','token_days' => '3', 'vacation_year_id' => 1,'remain_days' => '18','created_at' => NULL,'updated_at' => '2024-09-18 15:36:14'),
            array('id' => '71','employee_id' => '81','vacation_id' => '7','total_days' => '21','token_days' => '0', 'vacation_year_id' => 1,'remain_days' => '21','created_at' => NULL,'updated_at' => NULL),
            array('id' => '76','employee_id' => '85','vacation_id' => '5','total_days' => '7','token_days' => '2', 'vacation_year_id' => 1,'remain_days' => '5','created_at' => NULL,'updated_at' => NULL),
            array('id' => '77','employee_id' => '85','vacation_id' => '6','total_days' => '5','token_days' => '0', 'vacation_year_id' => 1,'remain_days' => '5','created_at' => NULL,'updated_at' => NULL),
            array('id' => '78','employee_id' => '85','vacation_id' => '7','total_days' => '21','token_days' => '1', 'vacation_year_id' => 1,'remain_days' => '20','created_at' => NULL,'updated_at' => NULL),
            array('id' => '79','employee_id' => '85','vacation_id' => '8','total_days' => '10','token_days' => '1', 'vacation_year_id' => 1,'remain_days' => '9','created_at' => NULL,'updated_at' => NULL)
        );
        DB::table('hr_employee_vacation_balance')->insert($hr_employee_vacation_balance);
        #######################################################



        ################# Create HR permissions setting ##############
        $hr_perrmission_setting = array(
            array('id' => '1','number_of_hours' => '6','per_month' => '4','created_at' => '2024-09-17 09:20:57','updated_at' => '2024-09-17 09:20:57')
        );
        DB::table('hr_perrmission_setting')->insert($hr_perrmission_setting);
        #######################################################


    }
}
