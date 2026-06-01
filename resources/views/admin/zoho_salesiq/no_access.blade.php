@extends('admin.layout.indexs.index')

@section('page-title', 'Zoho SalesIQ - تحديث الصلاحيات')
@section('title', 'Zoho SalesIQ')

@section('content')
    <div class="container-fluid">
        <div class="alert alert-danger">
            <h4 class="alert-heading">⚠️ مشكلة في صلاحيات OAuth</h4>
            <p>
                Zoho API يرفع <strong>"Invalid OAuthScope"</strong>. هذا يعني أن:
            </p>
            <ul>
                <li>✗ صلاحيات OAuth الحالية محدودة</li>
                <li>✗ قد لم يتم تفعيل SalesIQ في Zoho Account</li>
                <li>✗ قد تحتاج إلى ترقية الخطة أو صلاحيات إدارية</li>
            </ul>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">الحل:</h5>
            </div>
            <div class="card-body">
                <h6>1️⃣ تحقق من تفعيل SalesIQ:</h6>
                <p>
                    اذهب إلى <a href="https://crm.zoho.sa/" target="_blank">Zoho CRM</a> → 
                    Setup → <strong>Marketplace</strong> → ابحث عن <strong>SalesIQ</strong> وفعّله
                </p>

                <hr>

                <h6>2️⃣ تحقق من صلاحيات المستخدم:</h6>
                <p>
                    في Zoho CRM → Setup → Users & Roles → تأكد أن المستخدم لديه صلاحيات Admin أو CRM Module Access
                </p>

                <hr>

                <h6>3️⃣ أنشئ OAuth Client جديد:</h6>
                <p>
                    اذهب إلى <a href="https://api-console.zoho.sa/" target="_blank">API Console</a> →
                    Self Client → اختر CRM → Generate Code مع:<br>
                    <strong>ZohoCRM.modules.all,SalesIQ.modules.all</strong>
                </p>

                <hr>

                <div class="alert alert-info mt-3">
                    <strong>معلومة:</strong> إذا كانت لديك بيانات اعتماد OAuth صحيحة، 
                    قد تحتاج إلى إعادة محاولة بعد 5 دقائق من تفعيل الصلاحيات الجديدة.
                </div>
            </div>
        </div>

        <div class="mt-4">
            <h5>معلومات الاتصال الحالية:</h5>
            <table class="table table-sm">
                <tr>
                    <td><strong>Screen Name:</strong></td>
                    <td>{{ config('services.zoho_salesiq.screen_name') }}</td>
                </tr>
                <tr>
                    <td><strong>Base URL:</strong></td>
                    <td>{{ config('services.zoho_salesiq.salesiq_base_url') }}</td>
                </tr>
                <tr>
                    <td><strong>Client ID:</strong></td>
                    <td><code>{{ substr(config('services.zoho_salesiq.client_id'), 0, 20) }}...</code></td>
                </tr>
            </table>
        </div>
    </div>
@endsection
