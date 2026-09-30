{{-- تنسيق جدول الخبرات في إضافة/تعديل السيرة الذاتية --}}
<style>
    #experience_section .experience-table-wrap {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fff;
        overflow-x: auto;
    }

    #experience_table {
        min-width: 1100px;
        margin-bottom: 0;
        table-layout: fixed;
        border: 0;
    }

    #experience_table thead th {
        background: #f8fafc;
        color: #334155;
        font-weight: 600;
        font-size: .85rem;
        white-space: nowrap;
        vertical-align: middle;
        text-align: center;
        padding: .75rem .6rem;
        border-color: #e5e7eb;
    }

    #experience_table tbody td {
        vertical-align: top;
        padding: .75rem .6rem;
        border-color: #eef2f7;
    }

    #experience_table tbody tr.y-group:hover {
        background: #fbfdff;
    }

    /* عرض ثابت لكل عمود حتى لا تنضغط قوائم الدولة والوظيفة */
    #experience_table th:nth-child(1) { width: 190px; }
    #experience_table th:nth-child(2) { width: 180px; }
    #experience_table th:nth-child(3),
    #experience_table th:nth-child(4) { width: 160px; }
    #experience_table th:nth-child(5) { width: 250px; }
    #experience_table th:nth-child(6) { width: 190px; }
    #experience_table th:nth-child(7) { width: 90px; }

    #experience_table .form-control {
        min-height: 38px;
        font-size: .875rem;
    }

    #experience_table .select2-container {
        width: 100% !important;
    }

    #experience_table .select2-container .select2-selection--single {
        height: 38px;
        display: flex;
        align-items: center;
        border-color: #dee2e6;
        border-radius: .375rem;
    }

    #experience_table .select2-container .select2-selection__rendered {
        line-height: 36px;
        width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    #experience_table .select2-container .select2-selection__arrow {
        height: 36px;
    }

    /* القائمة المنسدلة لا تقل عن عرض يسمح بقراءة أسماء الدول والوظائف */
    #experience_section .select2-dropdown {
        min-width: 220px;
        border-color: #dee2e6;
        border-radius: .5rem;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .12);
        overflow: hidden;
    }

    #experience_section .select2-results__option {
        white-space: nowrap;
        padding: .45rem .75rem;
        font-size: .875rem;
    }

    #experience_section .select2-search--dropdown .select2-search__field {
        border-radius: .375rem;
        padding: .35rem .5rem;
    }

    #experience_table .experience-work-city-hint {
        display: block;
        padding-top: .55rem;
        font-size: .78rem;
        color: #94a3b8;
    }

    #experience_table .experience-duration-wrapper {
        display: flex;
        flex-direction: column;
        gap: .35rem;
    }

    #experience_table .experience-duration-wrapper .exp-manual-years {
        margin-bottom: 0 !important;
    }

    #experience_table .experience-duration-wrapper small {
        font-size: .72rem;
        line-height: 1.45;
        color: #94a3b8;
    }

    #experience_table .exp-duration-label {
        background: #f1f5f9;
        color: #0f766e;
        font-weight: 600;
    }

    #experience_table td:last-child {
        vertical-align: middle;
        white-space: nowrap;
    }

    #experience_table .btnAdd,
    #experience_table .btnRemove {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: background-color .15s ease;
    }

    #experience_table .btnAdd:hover { background: #ecfdf5; }
    #experience_table .btnRemove:hover { background: #fef2f2; }
</style>
