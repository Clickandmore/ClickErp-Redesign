@extends('admin.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{asset("admin")}}/custom/status-badges.css">


    <style>
        .maids-grid {
            max-height: 400px;
            overflow-y: auto;
        }

        .maid-card {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fff;
        }

        .maid-card:hover {
            border-color: #007bff;
            box-shadow: 0 4px 8px rgba(0,123,255,0.1);
            transform: translateY(-2px);
        }

        .maid-card.selected {
            border-color: #28a745;
            background-color: #f8fff9;
            box-shadow: 0 4px 8px rgba(40,167,69,0.2);
        }

        .maid-card .maid-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e9ecef;
        }

        .maid-card .maid-info h6 {
            margin-bottom: 5px;
            color: #333;
        }

        .maid-card .maid-info p {
            margin-bottom: 3px;
            font-size: 0.9em;
            color: #666;
        }

        .maid-card .maid-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #28a745;
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .maid-card .maid-status {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
        }

        .maid-card .maid-status.available {
            background: #d4edda;
            color: #155724;
        }

        .maid-card .maid-status.reserved {
            background: #fff3cd;
            color: #856404;
        }

        .filters-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .search-highlight {
            background-color: #fff3cd;
            padding: 1px 3px;
            border-radius: 3px;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            border-radius: 0.2rem;
        }

        .btn-sm i {
            font-size: 0.875rem;
        }

        .table td {
            vertical-align: middle;
        }

        .contract-info {
            font-size: 0.8rem;
            line-height: 1.2;
        }

        .contract-info div {
            margin-bottom: 2px;
        }

        .maid-info {
            text-align: center;
        }

        .maid-info small {
            display: block;
            margin-top: 5px;
        }

        .user-info {
            line-height: 1.3;
            text-align: center;
        }

        .user-info strong {
            color: #333;
            font-size: 0.9rem;
        }

        .user-info small {
            display: block;
            margin-top: 3px;
            font-size: 0.75rem;
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }

        .contract-info {
            font-size: 0.75rem;
            line-height: 1.3;
        }

        .contract-info div {
            margin-bottom: 2px;
            color: #666;
        }

        .contract-info strong {
            color: #333;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
        }

        .table td {
            vertical-align: middle;
            padding: 0.75rem;
        }

        .filters-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .filter-card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .filter-card .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: black;
            border-radius: 8px 8px 0 0;
        }

        .filter-card .form-control,
        .filter-card .form-select {
            border-radius: 6px;
            border: 1px solid #e9ecef;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .filter-card .form-control:focus,
        .filter-card .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .filter-card .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .statistics-card {
            transition: transform 0.2s ease-in-out;
        }

        .statistics-card:hover {
            transform: translateY(-2px);
        }

        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-action {
            margin: 0 2px;
            transition: all 0.2s ease;
        }

        .btn-action:hover {
            transform: scale(1.05);
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        /* تحسين ألوان البادجات لتكون أكثر وضوحاً */
        .badge.bg-primary {
            background-color: #0d6efd !important;
            color: white !important;
        }

        .badge.bg-success {
            background-color: #198754 !important;
            color: white !important;
        }

        .badge.bg-warning {
            background-color: #ffc107 !important;
            color: #212529 !important;
        }

        .badge.bg-info {
            background-color: #0dcaf0 !important;
            color: white !important;
        }

        .badge.bg-danger {
            background-color: #dc3545 !important;
            color: white !important;
        }

        .badge.bg-secondary {
            background-color: #6c757d !important;
            color: white !important;
        }

        .badge.bg-dark {
            background-color: #212529 !important;
            color: white !important;
        }

        /* تحسين مظهر بادج حالة العقد */
        .status-badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            font-weight: 600;
            border-radius: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .status-badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .modal-header .btn-close {
            filter: invert(1);
        }

        .maid-image-container {
            position: relative;
            display: inline-block;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .maid-image-container:hover {
            transform: scale(1.05);
        }

        .maid-image-container img {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            transition: border-color 0.2s ease;
        }

        .maid-image-container:hover img {
            border-color: #007bff;
            box-shadow: 0 4px 8px rgba(0,123,255,0.2);
        }

        .shadow {
            box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important;
        }

        .table-hover {
            background-color: #f8f9fa !important;
        }

        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* تحسين مظهر البادجات في الإحصائيات */
        .statistics-card .badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            font-weight: 700;
            border-radius: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        .statistics-card .badge.bg-primary {
            background-color: #ffffff !important;
            color: #0d6efd !important;
        }

        .statistics-card .badge.bg-success {
            background-color: #ffffff !important;
            color: #198754 !important;
        }

        .statistics-card .badge.bg-warning {
            background-color: #ffffff !important;
            color: #ffc107 !important;
        }

        .statistics-card .badge.bg-info {
            background-color: #ffffff !important;
            color: #0dcaf0 !important;
        }

        .statistics-card .badge.bg-danger {
            background-color: #ffffff !important;
            color: #dc3545 !important;
        }

        .statistics-card .badge.bg-secondary {
            background-color: #ffffff !important;
            color: #6c757d !important;
        }
         /* تحسين مظهر الفلاتر */
    .filter-card {
        border: 1px solid #e3e6f0;
        border-radius: 0.35rem;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }

    .filter-card .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 0.35rem 0.35rem 0 0;
    }

    /* تحسين مظهر القائمة المنسدلة */
    .dropdown-menu {
        border: none;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        border-radius: 0.5rem;
        min-width: 200px;
    }

    .dropdown-item {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f8f9fa;
        transition: all 0.2s ease;
    }

    .dropdown-item:last-child {
        border-bottom: none;
    }

    .dropdown-item:hover {
        background-color: #f8f9fa;
        transform: translateX(5px);
    }

    .dropdown-item.btn-info {
        color: #0dcaf0;
    }

    .dropdown-item.btn-info:hover {
        background-color: #0dcaf0;
        color: white;
    }

    .dropdown-item.btn-secondary {
        color: #6c757d;
    }

    .dropdown-item.btn-secondary:hover {
        background-color: #6c757d;
        color: white;
    }

    .dropdown-item.btn-warning {
        color: #ffc107;
    }

    .dropdown-item.btn-warning:hover {
        background-color: #ffc107;
        color: white;
    }

    .dropdown-item.btn-primary {
        color: #0d6efd;
    }

    .dropdown-item.btn-primary:hover {
        background-color: #0d6efd;
        color: white;
    }

    .dropdown-item.btn-success {
        color: #198754;
    }

    .dropdown-item.btn-success:hover {
        background-color: #198754;
        color: white;
    }

    .dropdown-item.btn-danger {
        color: #dc3545;
    }

    .dropdown-item.btn-danger:hover {
        background-color: #dc3545;
        color: white;
    }

    /* تحسين مظهر الجدول */
    /* .table th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        font-weight: 600;
    } */

    .table td {
        vertical-align: middle;
        border-color: #e3e6f0;
        color: black;
        font-style: bold;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa;
    }

    /* تحسين مظهر الأزرار */
    .btn-action {
        transition: all 0.2s ease;
        border: 1px solid #e3e6f0;

    }

    .btn-action:hover {
        transform: translateY(-1px);
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.15);
    }

    /* تحسين مظهر البطاقات */
    .info-card {
        border: 1px solid #e3e6f0;
        border-radius: 0.5rem;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        transition: all 0.3s ease;
    }

    .info-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
    }

    /* تحسين مظهر الفلاتر */
    .form-control, .form-select {
        border-radius: 0.5rem;
        border: 1px solid #d1d3e2;
        transition: all 0.2s ease;
    }

    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    /* تحسين مظهر أيقونة الفلاتر */
    #filtersIcon {
        transition: transform 0.3s ease;
    }

    #filtersCollapse.collapsing #filtersIcon {
        transform: rotate(180deg);
    }

    /* تحسين مظهر البطاقات */
    .card {
        border: none;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        border-radius: 0.5rem;
    }

    .card-header {
        border-bottom: 1px solid #e3e6f0;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 0.5rem 0.5rem 0 0;
    }

    /* تحسين مظهر الأزرار */
    .btn {
        border-radius: 0.5rem;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.15);
    }

    /* تحسين مظهر الجدول */
    .table {
        margin-bottom: 0;
    }

    .table th {
      font-weight: 600;
        padding: 1rem 0.75rem;
text-align: center

    }

    .table td {
        vertical-align: middle;
        border-color: #e3e6f0;
        padding: 0.75rem;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa;
        /* transform: scale(1.01); */
        transition: all 0.2s ease;
    }

    /* تحسين مظهر البطاقات المعلوماتية */
    .user-info, .maid-info, .contract-info {
        background: #f8f9fa;
        padding: 0.5rem;
        border-radius: 0.25rem;
        border-left: 3px solid #667eea;
    }

    /* تحسين مظهر البطاقات */
    .status-badge {
        font-size: 0.8rem;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    /* تحسين مظهر القائمة المنسدلة */
    .dropdown-toggle::after {
        margin-right: 0.5rem;
    }

    /* تحسين مظهر الرسائل */
    .alert {
        border: none;
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    /* تحسين مظهر الفلاتر */
    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
    }

    /* تحسين مظهر أيقونات Font Awesome */
    .fas, .fa {
        width: 1rem;
        text-align: center;
    }
        /* تحسين مظهر القائمة المنسدلة */
        .dropdown-menu {
            border: 1px solid #e3e6f0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            padding: 0.5rem 0;
            background-color: #f8f9fa;
            color: black;
            font-style: bold;
            z-index: 9999 !important;
            position: absolute !important;
        }

        .dropdown {
            position: relative;
        }

        .dropdown-toggle {
            position: relative;
        }

        .dropdown-toggle::after {
            margin-right: 0.5em;
        }

        /* تحسين مظهر عناصر القائمة المنسدلة */
        .dropdown-item {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            margin: 0 0.25rem;
            transition: all 0.2s ease;
            color: black;
            font-style: bold;
            position: relative;
            z-index: 10000;
        }

        /* .dropdown-item:hover {
            background-color: #f8f9fa;
            transform: translateX(2px);

        } */

        .dropdown-item.btn-danger:hover {
            background-color: #dc3545;
            color: white;
        }

        .dropdown-item.btn-warning:hover {
            background-color: #ffc107;
            color: #212529;
        }

        .dropdown-item.btn-info:hover {
            background-color: #17a2b8;
            color: white;
        }

        .dropdown-item.btn-success:hover {
            background-color: #28a745;
            color: white;
        }

        .dropdown-item.btn-secondary:hover {
            background-color: #6c757d;
            color: white;
        }

        .dropdown-item.badge {
            margin: 0.25rem 0.5rem;
            padding: 0.5rem 0.75rem;
            border-radius: 4px;
        }

        /* تحسين مظهر زر الإجراءات */
        .actions-dropdown {
            position: relative;
        }

        .actions-dropdown .btn {
            position: relative;
            z-index: 1000;
        }

        /* إضافة tooltip لزر الإجراءات */
        .actions-dropdown .btn[data-bs-toggle="tooltip"] {
            position: relative;
        }

        /* تحسين مظهر الجدول لضمان عدم تداخل الصفوف مع القائمة المنسدلة */
        .table-responsive {
            position: relative;
            z-index: 1;
        }

        .table {
            position: relative;
            z-index: 1;
        }

        .table tbody tr {
            position: relative;
            z-index: 1;
        }

        .table tbody tr td {
            position: relative;
            z-index: 1;
        }

        /* ضمان ظهور القائمة المنسدلة فوق جميع العناصر */
        .table tbody tr td .dropdown {
            position: relative;
            z-index: 9999;
        }

        /* تحسين مظهر الصفوف لضمان عدم التداخل */
        .table tbody tr {
            position: relative;
            z-index: 1;
        }

        .table tbody tr:hover {
            z-index: 2;
        }

        .table tbody tr td {
            position: relative;
            z-index: 1;
            background-color: white;
        }

        /* ضمان ظهور القائمة المنسدلة فوق الصفوف */
        .dropdown-menu.show {
            z-index: 10000 !important;
            position: absolute !important;
        }

        /* تحسين مظهر زر الإجراءات */
        .actions-dropdown .btn {
            position: relative;
            z-index: 1000;
            background-color: white;
            border: 1px solid #dee2e6;
        }

        .actions-dropdown .btn:hover {
            background-color: #f8f9fa;
            border-color: #adb5bd;
        }

        /* إضافة تأثير بصرية للقائمة المنسدلة */
        .dropdown-menu {
            animation: fadeInDown 0.2s ease-in-out;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ضمان عدم تداخل الصفوف مع القائمة المنسدلة */
        .table-container {
            position: relative;
            overflow: visible;
        }

        .table tbody tr {
            position: relative;
        }

        .table tbody tr:last-child .dropdown-menu {
            top: auto !important;
            bottom: 100% !important;
        }

        /* تحسين مظهر القائمة المنسدلة للصفوف الأخيرة */
        .dropdown-menu.dropdown-menu-end {
            right: 0;
            left: auto;
        }

        /* إضافة مساحة إضافية للصفوف الأخيرة */
        .table tbody tr:last-child td {
            padding-bottom: 20px;
        }

        /* إضافة مساحة إضافية للصفوف الأخيرة */
        .table tbody tr:last-child td {
            padding-bottom: 20px;
        }

        /* ضمان عدم تداخل الصفوف مع القائمة المنسدلة */
        .table-responsive {
            overflow: visible !important;
        }

        .table {
            margin-bottom: 50px;
        }

        /* تحسين مظهر القائمة المنسدلة */
        .dropdown-menu {
            min-width: 200px;
            max-width: 300px;
        }

        /* إضافة تأثير بصرية للقائمة المنسدلة */
        .dropdown-menu.show {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        /* تحسين مظهر عناصر القائمة المنسدلة */
        .dropdown-item {
            font-size: 0.9rem;
            padding: 0.6rem 1rem;
        }

        .dropdown-item i {
            width: 16px;
            text-align: center;
            margin-left: 8px;
        }

        /* ضمان عدم تداخل الصفوف مع القائمة المنسدلة */
        .table-container {
            position: relative;
            overflow: visible;
        }

        .table tbody tr {
            position: relative;
        }

        .table tbody tr:last-child {
            margin-bottom: 100px;
        }

        /* تحسين مظهر القائمة المنسدلة */
        .dropdown-menu {
            position: absolute !important;
            z-index: 10000 !important;
        }

        /* ضمان ظهور القائمة المنسدلة فوق جميع العناصر */
        .dropdown-menu.show {
            display: block !important;
            position: absolute !important;
            z-index: 10000 !important;
        }

        /* تحسين مظهر زر الإجراءات */
        .actions-dropdown .btn {
            position: relative;
            z-index: 1000;
        }

        /* إضافة تأثير بصرية للقائمة المنسدلة */
        .dropdown-menu {
            transform-origin: top center;
            animation: dropdownFadeIn 0.2s ease-out;
        }

        @keyframes dropdownFadeIn {
            from {
                opacity: 0;
                transform: scaleY(0.8) translateY(-10px);
            }
            to {
                opacity: 1;
                transform: scaleY(1) translateY(0);
            }
        }

        /* ضمان عدم تداخل الصفوف مع القائمة المنسدلة */
        .table-responsive {
            overflow: visible !important;
        }

        .table {
            margin-bottom: 100px;
        }

        /* تحسين مظهر القائمة المنسدلة للصفوف الأخيرة */
        .table tbody tr:last-child .dropdown-menu {
            top: auto !important;
            bottom: 100% !important;
            margin-bottom: 10px;
        }

        /* تحسين مظهر القائمة المنسدلة للصفوف العادية */
        .table tbody tr:not(:last-child) .dropdown-menu {
            top: 100% !important;
            bottom: auto !important;
            margin-top: 5px;
        }

        /* ضمان ظهور القائمة المنسدلة فوق جميع العناصر */
        .dropdown-menu {
            position: absolute !important;
            z-index: 10000 !important;
            background-color: white !important;
            border: 1px solid #dee2e6 !important;
            margin-top: 20px
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
        }

        /* تحسين مظهر زر الإجراءات */
        .actions-dropdown .btn {
            position: relative;
            z-index: 1000;
            background-color: white;
            border: 1px solid #dee2e6;
            color: #495057;
        }

        .actions-dropdown .btn:hover {
            background-color: #f8f9fa;
            border-color: #adb5bd;
        }

        /* إضافة تأثير بصرية للقائمة المنسدلة */
        .dropdown-menu.show {
            animation: dropdownFadeIn 0.2s ease-out;
        }

        /* ضمان ظهور القائمة المنسدلة للصف الواحد */
        .table tbody tr:only-child .dropdown-menu {
            top: 100% !important;
            bottom: auto !important;
            margin-top: 5px;
            position: absolute !important;
            z-index: 99999 !important;
            transform: none !important;
            max-height: none !important;
            overflow: visible !important;
        }

        /* ضمان ظهور القائمة المنسدلة للصفوف الأخيرة */
        .table tbody tr:last-child:not(:only-child) .dropdown-menu {
            top: auto !important;
            bottom: 100% !important;
            margin-bottom: 10px;
        }

        /* ضمان ظهور القائمة المنسدلة للصفوف العادية */
        .table tbody tr:not(:last-child):not(:only-child) .dropdown-menu {
            top: 100% !important;
            bottom: auto !important;
            margin-top: 5px;
        }

        /* إضافة مساحة للصف الواحد */
        .table tbody tr:only-child {
            margin-bottom: 200px !important;
        }

        /* ضمان عدم قطع القائمة */
        .table-container {
            overflow: visible !important;
        }

        .table {
            overflow: visible !important;
        }

        .table tbody {
            overflow: visible !important;
        }
    </style>

@endsection
@section('page-title')
    {{helperTrans("ingaz.All Recruitment contracts")}}
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_VIEW))

    <!-- Filters Section -->
    <div class="card mb-4">
        <div class="card-header bg-light text-black" id="filtersHeader" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#filtersCollapse" aria-expanded="true" aria-controls="filtersCollapse">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="fas fa-filter me-2"></i>
                    فلاتر البحث
                </h6>

            </div>
            <i class="fas fa-chevron-up "    id="filtersIcon"></i>
        </div>
        <div class="collapse show" id="filtersCollapse">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label for="statusFilter" class="form-label">حالة العقد:</label>
                        <select id="statusFilter" class="form-select">
                            <option value="">جميع الحالات</option>
                            <option value="new">جديد</option>
                            <option value="msaned">مساند</option>
                            <option value="tafwed">تفويض</option>
                            <option value="taffez">التفييز</option>
                            <option value="tazker">التذاكر</option>
                            <option value="arrival">وصول</option>
                            <option value="sari">ساري</option>
                            <option value="completed">مكتمل</option>
                            <option value="back">مرتجع</option>
                            <option value="shelter">الايواء</option>
                            <option value="pending_maid_selection">انتظار اختيار العاملة</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="dateFromFilter" class="form-label">تاريخ من:</label>
                        <input type="date" id="dateFromFilter" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="dateToFilter" class="form-label">تاريخ إلى:</label>
                        <input type="date" id="dateToFilter" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="clientPhoneFilter" class="form-label">رقم جوال العميل:</label>
                        <input type="text" id="clientPhoneFilter" class="form-control" placeholder="ابحث برقم الجوال">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-3">
                        <label for="clientNameFilter" class="form-label">اسم العميل:</label>
                        <input type="text" id="clientNameFilter" class="form-control" placeholder="ابحث باسم العميل">
                    </div>
                    <div class="col-md-3">
                        <label for="maidPassportFilter" class="form-label">رقم جواز العاملة:</label>
                        <input type="text" id="maidPassportFilter" class="form-control" placeholder="ابحث برقم الجواز">
                    </div>
                    <div class="col-md-3">
                        <label for="maidNameFilter" class="form-label">اسم العاملة:</label>
                        <input type="text" id="maidNameFilter" class="form-control" placeholder="ابحث باسم العاملة">
                    </div>
                    <div class="col-md-3">
                        <label for="contractNumberFilter" class="form-label">رقم العقد:</label>
                        <input type="text" id="contractNumberFilter" class="form-control" placeholder="ابحث برقم العقد">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-3">
                        <label for="nationalityFilter" class="form-label">جنسية العاملة:</label>
                        <select id="nationalityFilter" class="form-select">
                            <option value="">جميع الجنسيات</option>
                            @if(isset($filterOptions) && isset($filterOptions['nationalities']))
                                @foreach($filterOptions['nationalities'] as $nationality)
                                    <option value="{{ $nationality->sub_title }}">{{ $nationality->sub_title }}</option>
                                @endforeach
                            @else
                                <option value="">لا توجد بيانات</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="religionFilter" class="form-label">ديانة العاملة:</label>
                        <select id="religionFilter" class="form-select">
                            <option value="">جميع الديانات</option>
                            @if(isset($filterOptions) && isset($filterOptions['religions']))
                                @foreach($filterOptions['religions'] as $religion)
                                    <option value="{{ $religion->title }}">{{ $religion->title }}</option>
                                @endforeach
                            @else
                                <option value="">لا توجد بيانات</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="officeFilter" class="form-label"> المكتب الخارجي:</label>
                        <select id="officeFilter" class="form-select">
                            <option value="">جميع المكاتب الخارجية</option>
                            @if(isset($filterOptions) && isset($filterOptions['offices']))
                                @foreach($filterOptions['offices'] as $office)
                                    <option value="{{ $office->id }}">{{ $office->title }}</option>
                                @endforeach
                            @else
                                <option value="">لا توجد بيانات</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3" style="display: none;">
                        <label for="warrantyStatusFilter" class="form-label">حالة الضمان:</label>
                        <select id="warrantyStatusFilter" class="form-select">
                            <option value="">جميع حالات الضمان</option>
                            <option value="active">نشط</option>
                            <option value="expired">منتهي</option>
                            <option value="not_applicable">غير قابل للتطبيق</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="button" id="clearFilters" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> مسح
                            </button>
                            <button type="button" id="btnSubmit" class="btn btn-primary ms-2">
                                <i class="fas fa-search me-1"></i>بحث
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="fas fa-file-export me-2"></i>
                                تصدير البيانات
                            </h5>
                        </div>
                        <div>
        @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_EXPORT))
                            <a href="{{ route('RecruitmentContract.export.pdf') }}" class="btn btn-danger me-2" target="_blank">
                                <i class="fa fa-file-pdf me-1"></i> {{helperTrans("ingaz.export_pdf")}}
            </a>
            <a href="{{ route('RecruitmentContract.export.excel') }}" class="btn btn-success">
                                <i class="fa fa-file-excel me-1"></i> {{helperTrans("ingaz.export_excel")}}
            </a>
        @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-table me-2"></i>
                    قائمة العقود
                </h5>
            </div>
            <div class="card-body p-0">
                <table id="dataTableObject" class="table table-hover mb-0" style="width:100%">
        <thead>
        <tr>
            <th>ID</th>
            <th>معلومات العميل</th>
            <th>العاملة</th>
            <th>معلومات العقد</th>
            <th>الحالة</th>
            <th>تحديث الحالة</th>
            <th>الإجراءات</th>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" role="dialog" aria-labelledby="updateStatusModalLabel"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">تحديث حالة العقد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="rowId" name="rowId">
                    <input type="hidden" id="status" name="status">
                    <input type="hidden" id="nextStatus" name="nextStatus">

                    <div class="mb-3">
                        <label for="currentStatus" class="form-label">الحالة الحالية:</label>
                        <input type="text" id="currentStatus" class="form-control" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="nextStatusDisplay" class="form-label">المرحلة التالية:</label>
                        <input type="text" id="nextStatusDisplay" class="form-control" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="statusDate" class="form-label">تاريخ التحديث:</label>
                    <input type="date" id="statusDate" required name="statusDate" class="form-control" value="{{date('Y-m-d')}}">
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        سيتم تحديث حالة العقد إلى المرحلة التالية في التاريخ المحدد
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-success" id="saveStatusBtn">
                        <i class="fas fa-save me-1"></i>تحديث الحالة
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal تأكيد الإرجاع -->
    <div class="modal fade" id="confirmBackModal" tabindex="-1" role="dialog" aria-labelledby="confirmBackModalLabel"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmBackModalLabel">تأكيد إرجاع العقد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        هل أنت متأكد من إرجاع هذا العقد؟ هذا الإجراء لا يمكن التراجع عنه.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-danger" id="confirmBackBtn">
                        <i class="fas fa-undo me-1"></i>تأكيد الإرجاع
                    </button>
                </div>
            </div>
        </div>
    </div>



@else
    @include('components.no_permissions')
@endif

@endsection
@section('js')

    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function () {
            // دالة لقراءة الـ status من الـ URL
            function getStatusFromUrl() {
                const urlParams = new URLSearchParams(window.location.search);
                return urlParams.get('status');
            }

            // تطبيق الـ status من الـ URL على الفلتر أولاً
            const urlStatus = getStatusFromUrl();
            if (urlStatus) {
                $('#statusFilter').val(urlStatus);
                console.log('Status from URL applied to filter:', urlStatus);
                // إزالة الفلتر من localStorage لتجنب التضارب
                localStorage.removeItem('contractFilters');

                // إظهار فلتر الضمان إذا كانت الحالة "عقود مكتملة"
                if (urlStatus === 'completed') {
                    $('#warrantyStatusFilter').parent().show();
                    $('#warrantyStatusFilter').show();
                }
            }

            let columns = [
                {"data": 'id', name: 'id', orderable: false, searchable: false},
                {"data": "phone", orderable: true, searchable: true},
                {"data": "maid_id", orderable: false, searchable: false},
                {"data": "contract_info", orderable: false, searchable: false},
                {"data": "status", orderable: true, searchable: true},
                {"data": "updateStatus", orderable: true, searchable: true},
                {"data": "actions", orderable: true, searchable: true},
            ];

            // إنشاء DataTable مع الفلتر المطبق
            let dataTable = $('#dataTableObject').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                ajax: {
                    url: "{{ route('RecruitmentContract.index') }}",
                    data: function (d) {
                        d.status = $('#statusFilter').val();
                        d.date_from = $('#dateFromFilter').val();
                        d.date_to = $('#dateToFilter').val();
                        d.client_phone = $('#clientPhoneFilter').val();
                        d.client_name = $('#clientNameFilter').val();
                        d.maid_passport = $('#maidPassportFilter').val();
                        d.maid_name = $('#maidNameFilter').val();
                        d.contract_number = $('#contractNumberFilter').val();
                        d.nationality = $('#nationalityFilter').val();
                        d.religion = $('#religionFilter').val();
                        d.office_id = $('#officeFilter').val();
                        d.city = $('#cityFilter').val();

                        // إضافة فلتر حالة الضمان فقط إذا كانت الحالة "عقود مكتملة"
                        if ($('#statusFilter').val() === 'completed') {
                            d.warranty_status = $('#warrantyStatusFilter').val();
                        }
                    },
                    error: function (xhr, error, thrown) {
                        console.error('DataTables AJAX error:', error);
                        console.error('Response:', xhr.responseText);
                    }
                },
                columns: columns,
                order: [[0, 'desc']],
                pageLength: 25,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json',
                    emptyTable: "لا توجد عقود متاحة",
                    zeroRecords: "لم يتم العثور على عقود تطابق معايير البحث",
                    processing: "جاري التحميل...",
                    search: "البحث:",
                    lengthMenu: "عرض _MENU_ سجل",
                    info: "عرض _START_ إلى _END_ من _TOTAL_ سجل",
                    infoEmpty: "عرض 0 إلى 0 من 0 سجل",
                    infoFiltered: "(مفلتر من _MAX_ سجل إجمالي)"
                },
                responsive: true,
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                initComplete: function () {
                    console.log('DataTable initialized successfully');
                    var info = this.api().page.info();
                    console.log('Records total:', info.recordsTotal);
                    console.log('Records displayed:', info.recordsDisplay);
                    console.log('Current status filter:', $('#statusFilter').val());

                    // إظهار رسالة إذا لم توجد بيانات عند التحميل الأولي
                    if (info.recordsTotal === 0) {
                        console.log('No contracts found on initial load');
                    }
                }
            });

            // تحديث الجدول عند تغيير الفلتر
            $('#statusFilter').on('change', function() {
                var selectedStatus = $(this).val();

                // إظهار/إخفاء فلتر حالة الضمان
                if (selectedStatus === 'completed') {
                    $('#warrantyStatusFilter').parent().show();
                    $('#warrantyStatusFilter').show();
                } else {
                    $('#warrantyStatusFilter').parent().hide();
                    $('#warrantyStatusFilter').hide();
                    $('#warrantyStatusFilter').val(''); // مسح القيمة
                }

                // لا تحدث الجدول إذا كان التغيير من الـ URL
                if (urlStatus && $(this).val() === urlStatus) {
                    console.log('Skipping table reload - filter change from URL');
                    return;
                }

                $('#dataTableObject').addClass('loading');
                dataTable.ajax.reload(function() {
                    $('#dataTableObject').removeClass('loading');
                    // إظهار رسالة إذا لم توجد نتائج
                    var info = dataTable.page.info();
                    if (info.recordsTotal === 0) {
                        console.log('لا توجد نتائج بعد تطبيق الفلتر');
                    }
                });
            });

            // تحديث الجدول عند تغيير فلتر حالة الضمان
            $('#warrantyStatusFilter').on('change', function() {
                $('#dataTableObject').addClass('loading');
                dataTable.ajax.reload(function() {
                    $('#dataTableObject').removeClass('loading');
                });
            });

            // تحديث الجدول عند تغيير فلتر التاريخ
            $('#dateFromFilter, #dateToFilter').on('change', function() {
                // لا تحدث الجدول تلقائياً - انتظر زر البحث
            });

            // تحديث الجدول عند تغيير فلاتر البحث
            $('#clientPhoneFilter, #clientNameFilter, #maidPassportFilter, #maidNameFilter, #contractNumberFilter, #nationalityFilter, #religionFilter, #officeFilter, #cityFilter').on('change', function() {
                // لا تحدث الجدول تلقائياً - انتظر زر البحث
            });

            // زر البحث
            $('#btnSubmit').on('click', function() {
                $('#dataTableObject').addClass('loading');
                dataTable.ajax.reload(function() {
                    $('#dataTableObject').removeClass('loading');
                });
            });

            // مسح الفلاتر
            $('#clearFilters').on('click', function() {
                $('#statusFilter, #warrantyStatusFilter, #clientPhoneFilter, #clientNameFilter, #maidPassportFilter, #maidNameFilter, #contractNumberFilter, #nationalityFilter, #religionFilter, #officeFilter, #cityFilter').val('');
                $('#dateFromFilter, #dateToFilter').val('');
                localStorage.removeItem('contractFilters'); // مسح الفلاتر المحفوظة

                // إخفاء فلتر حالة الضمان عند المسح
                $('#warrantyStatusFilter').parent().hide();
                $('#warrantyStatusFilter').hide();

                $('#dataTableObject').addClass('loading');
                dataTable.ajax.reload(function() {
                    $('#dataTableObject').removeClass('loading');
                });
            });

            // تحديث الإحصائيات عند تحميل البيانات
            dataTable.on('xhr', function() {
                // تحديث الإحصائيات فقط إذا كانت معطلة
                // updateStatistics();

                // إظهار إشعار إذا لم توجد نتائج فقط عند البحث (وليس عند فلتر الحالة الأساسي)
                var info = dataTable.page.info();
                var hasSearchFilters = $('#dateFromFilter').val() || $('#dateToFilter').val() ||
                                     $('#clientPhoneFilter').val() || $('#clientNameFilter').val() ||
                                     $('#maidPassportFilter').val() || $('#maidNameFilter').val() ||
                                     $('#contractNumberFilter').val() || $('#nationalityFilter').val() ||
                                     $('#religionFilter').val() || $('#officeFilter').val() || $('#cityFilter').val();

                console.log('DataTable XHR completed:', {
                    recordsTotal: info.recordsTotal,
                    recordsDisplay: info.recordsDisplay,
                    hasSearchFilters: hasSearchFilters,
                    statusFilter: $('#statusFilter').val()
                });

                if (info.recordsTotal === 0 && hasSearchFilters) {
                    Swal.fire({
                        icon: 'info',
                        title: 'لا توجد نتائج',
                        text: 'لم يتم العثور على عقود تطابق معايير البحث المحددة',
                        confirmButtonText: 'حسناً'
                    });
                } else if (info.recordsTotal === 0 && !hasSearchFilters && $('#statusFilter').val()) {
                    // عرض رسالة مناسبة عندما لا توجد عقود في المرحلة المحددة
                    var statusText = $('#statusFilter option:selected').text();
                    var statusValue = $('#statusFilter').val();
                    console.log('No contracts found for status:', statusText);

                    // رسالة خاصة لمرحلة انتظار اختيار العاملة
                    var message = '';
                    if (statusValue === 'pending_maid_selection') {
                        message = '<tr><td colspan="10" class="text-center">' +
                            '<div class="alert alert-warning mb-0">' +
                            '<i class="fas fa-exclamation-triangle me-2"></i>' +
                            '<strong>لا توجد عقود في انتظار اختيار العاملة</strong><br>' +
                            '<small class="text-muted">جميع العقود إما تم تخصيص عاملة لها أو في مراحل أخرى</small>' +
                            '</div></td></tr>';
                    } else {
                        message = '<tr><td colspan="10" class="text-center text-muted">لا توجد عقود في مرحلة "' + statusText + '"</td></tr>';
                    }

                    // إظهار رسالة في الجدول بدلاً من الدايلوج
                    if ($('#dataTableObject tbody tr').length === 0) {
                        $('#dataTableObject tbody').html(message);
                    }
                }
            });

            // دالة تحديث الإحصائيات
            function updateStatistics() {
                // تم تعطيل الإحصائيات مؤقتاً
                return;

                $.ajax({
                    url: '{{ route("RecruitmentContract.statistics") }}',
                    method: 'GET',
                    data: {
                        status: $('#statusFilter').val(),
                        date_from: $('#dateFromFilter').val(),
                        date_to: $('#dateToFilter').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#totalContracts').text(response.data.total || 0);
                            $('#activeContracts').text(response.data.active || 0);
                            $('#pendingContracts').text(response.data.pending || 0);
                            $('#completedContracts').text(response.data.completed || 0);
                            $('#returnedContracts').text(response.data.returned || 0);
                            $('#closedContracts').text(response.data.closed || 0);
                        }
                    }
                });
            }

            // تحديث الإحصائيات عند تحميل الصفحة
            updateStatistics();

            // حفظ الفلاتر في localStorage
            function saveFilters() {
                var filters = {
                    status: $('#statusFilter').val(),
                    clientPhone: $('#clientPhoneFilter').val(),
                    clientName: $('#clientNameFilter').val(),
                    maidPassport: $('#maidPassportFilter').val(),
                    maidName: $('#maidNameFilter').val(),
                    contractNumber: $('#contractNumberFilter').val(),
                    nationality: $('#nationalityFilter').val(),
                    religion: $('#religionFilter').val(),
                    city: $('#cityFilter').val(),
                    dateFrom: $('#dateFromFilter').val(),
                    dateTo: $('#dateToFilter').val()
                };
                localStorage.setItem('contractFilters', JSON.stringify(filters));
            }

            // استعادة الفلاتر من localStorage (فقط إذا لم يكن هناك status في الـ URL)
            function loadFilters() {
                // لا تحمل الفلاتر من localStorage إذا كان هناك status في الـ URL
                if (urlStatus) {
                    console.log('Skipping localStorage load because status is in URL:', urlStatus);
                    return;
                }

                var filters = localStorage.getItem('contractFilters');
                if (filters) {
                    filters = JSON.parse(filters);
                    $('#statusFilter').val(filters.status || '');
                    $('#clientPhoneFilter').val(filters.clientPhone || '');
                    $('#clientNameFilter').val(filters.clientName || '');
                    $('#maidPassportFilter').val(filters.maidPassport || '');
                    $('#maidNameFilter').val(filters.maidName || '');
                    $('#contractNumberFilter').val(filters.contractNumber || '');
                    $('#nationalityFilter').val(filters.nationality || '');
                    $('#religionFilter').val(filters.religion || '');
                    $('#cityFilter').val(filters.city || '');
                    $('#dateFromFilter').val(filters.dateFrom || '');
                    $('#dateToFilter').val(filters.dateTo || '');
                }
            }

            // تحميل الفلاتر عند تحميل الصفحة
            loadFilters();

            // حفظ الفلاتر عند تغييرها (فقط إذا لم يكن هناك status في الـ URL)
            $('.form-control, .form-select').on('change', function() {
                // لا تحفظ الفلاتر إذا كان هناك status في الـ URL لتجنب التضارب
                if (!urlStatus) {
                    saveFilters();
                }
            });

            // التحكم في طي الفلاتر
            $('#filtersCollapse').on('show.bs.collapse', function () {
                $('#filtersIcon').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            });

            $('#filtersCollapse').on('hide.bs.collapse', function () {
                $('#filtersIcon').removeClass('fa-chevron-up').addClass('fa-chevron-down');
            });

            // معالج أزرار الإجراءات من القائمة المنسدلة
            $(document).on('click', '.dropdown-item.complete-trip-btn', function() {
                var tripId = $(this).data('trip-id');
                completeTrip(tripId);
            });

            $(document).on('click', '.dropdown-item.backContract', function() {
                var rowId = $(this).data('row-id');
                backContract(rowId);
            });

            // معالج زر إكمال الرحلة من عمود المرحلة التالية
            $(document).on('click', '.complete-trip-btn', function() {
                var tripId = $(this).data('trip-id');
                completeTrip(tripId);
            });

            // معالج زر الإرجاع من عمود المرحلة التالية
            $(document).on('click', '.backContract', function() {
                var rowId = $(this).data('row-id');
                backContract(rowId);
            });

            // معالج زر إضافة رحلة
            $(document).on('click', '.add-travel-btn', function() {
                var contractId = $(this).data('contract-id');
                addTravel(contractId);
            });

            // معالج زر تحديث الحالة
            $(document).on('click', '.update-status-btn', function() {
                var rowId = $(this).data('row-id');
                var status = $(this).data('status');
                var nextStatus = $(this).data('next-status');
                var date = $(this).data('date');

                // تعبئة البيانات في Modal
                $('#updateStatusModal #rowId').val(rowId);
                $('#updateStatusModal #status').val(status);
                $('#updateStatusModal #nextStatus').val(nextStatus);
                $('#updateStatusModal #statusDate').val(date);

                // عرض Modal
                $('#updateStatusModal').modal('show');
            });

            // معالج زر حفظ الحالة
            $('#saveStatusBtn').click(function() {
                var rowId = $('#updateStatusModal #rowId').val();
                var nextStatus = $('#updateStatusModal #nextStatus').val();
                var date = $('#updateStatusModal #statusDate').val();

                $.ajax({
                    url: '{{ route("RecruitmentContract.updateStatus") }}',
                    method: 'POST',
                    data: {
                        row_id: rowId,
                        next_status: nextStatus,
                        date: date,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'تم تحديث الحالة بنجاح',
                                text: response.message
                            }).then(() => {
                                $('#updateStatusModal').modal('hide');
                                dataTable.ajax.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: response.message
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: 'حدث خطأ أثناء تحديث الحالة'
                        });
                    }
                });
            });

            // دالة إكمال الرحلة
            function completeTrip(tripId) {
                Swal.fire({
                    title: 'تأكيد إكمال الرحلة',
                    text: 'هل أنت متأكد من إكمال هذه الرحلة؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، أكمل الرحلة',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route("RecruitmentContract.completeTrip") }}',
                            method: 'POST',
                            data: {
                                trip_id: tripId,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'تم إكمال الرحلة بنجاح',
                                        text: response.message
                                    }).then(() => {
                                        dataTable.ajax.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'خطأ',
                                        text: response.message
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'خطأ',
                                    text: 'حدث خطأ أثناء إكمال الرحلة'
                                });
                            }
                        });
                    }
                });
            }

            // دالة إرجاع العقد
            function backContract(rowId) {
                Swal.fire({
                    title: 'تأكيد إرجاع العقد',
                    text: 'هل أنت متأكد من إرجاع هذا العقد؟',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'نعم، إرجاع العقد',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route("RecruitmentContract.back", ":id") }}'.replace(':id', rowId),
                            method: 'GET',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'تم إرجاع العقد بنجاح',
                                        text: response.message
                                    }).then(() => {
                                        dataTable.ajax.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'خطأ',
                                        text: response.message
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'خطأ',
                                    text: 'حدث خطأ أثناء إرجاع العقد'
                                });
                            }
                        });
                    }
                });
            }

            // دالة إضافة رحلة
            function addTravel(contractId) {
                Swal.fire({
                    title: 'إضافة رحلة جديدة',
                    text: 'هل تريد إضافة رحلة جديدة لهذا العقد؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، إضافة رحلة',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route("travel.add") }}',
                            method: 'POST',
                            data: {
                                contract_id: contractId,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'تم إضافة الرحلة بنجاح',
                                        text: response.message
                                    }).then(() => {
                                        dataTable.ajax.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'خطأ',
                                        text: response.message
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'خطأ',
                                    text: 'حدث خطأ أثناء إضافة الرحلة'
                                });
                            }
                        });
                    }
                });
            }

            // إضافة تأثيرات بصرية للأزرار
            $('.btn-action').hover(
                function() {
                    $(this).addClass('shadow');
                },
                function() {
                    $(this).removeClass('shadow');
                }
            );

            // تحسين تجربة المستخدم
            $('.table').on('mouseenter', 'tr', function() {
                $(this).addClass('table-hover');
            }).on('mouseleave', 'tr', function() {
                $(this).removeClass('table-hover');
            });

            // معالج فتح Modal تحديث الحالة
        $('#updateStatusModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var rowId = button.data('row-id');
            var status = button.data('status');
                var nextStatus = button.data('next-status');
            var date = button.data('date');
            var modal = $(this);

            modal.find('#rowId').val(rowId);
            modal.find('#status').val(status);
                modal.find('#nextStatus').val(nextStatus);
            modal.find('#statusDate').val(date);

                // عرض الحالة الحالية
                var currentStatusText = getContractStatusText(status);
                modal.find('#currentStatus').val(currentStatusText);

                // عرض المرحلة التالية
                var nextStatusText = getContractStatusText(nextStatus);
                modal.find('#nextStatusDisplay').val(nextStatusText);
            });

            // دالة للحصول على نص الحالة
            function getContractStatusText(status) {
                var statusMap = {
                    'new': 'عقد جديد',
                    'msaned': 'عقود مساند',
                    'tafwed': 'تفويض',
                    'taffez': 'التفييز',
                    'tazker': 'تذكرة',
                    'completed': 'عقد مكتمل',
                    'under_warranty': 'تحت الضمان',
                    'back': 'مرتجع',
                    'arrival': 'مرحلة الوصول',
                    'sari': 'عقد ساري',
                    'closed': 'عقد مغلق',
                    'shelter': 'عقد إيواء',
                    'pending_maid_selection': 'بانتظار اختيار العاملة'
                };
                return statusMap[status] || status;
            }

            // معالج تحديث الحالة
        $('#saveStatusBtn').click(function () {
            var rowId = $('#rowId').val();
            var status = $('#status').val();
                var nextStatus = $('#nextStatus').val();
            var statusDate = $('#statusDate').val();

                if (!statusDate) {
                    alert('يرجى إدخال تاريخ الحالة');
                    return;
                }

            $.ajax({
                url: '{{ route('RecruitmentContract.show', ':rowId') }}'.replace(':rowId', rowId),
                method: 'GET',
                data: {
                    status: status,
                        next_status: nextStatus,
                    date: statusDate,
                },
                success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'تم بنجاح',
                                text: response.message || 'تم تحديث الحالة بنجاح',
                                confirmButtonText: 'حسناً'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: response.message || 'حدث خطأ أثناء تحديث الحالة',
                                confirmButtonText: 'حسناً'
                            });
                        }
                },
                error: function (xhr, status, error) {
                        console.error('AJAX Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: 'حدث خطأ أثناء تحديث الحالة',
                            confirmButtonText: 'حسناً'
                        });
                },
                complete: function () {
                        $('#dataTableObject').addClass('loading');
                        dataTable.ajax.reload(function() {
                            $('#dataTableObject').removeClass('loading');
                        });
                    $('#updateStatusModal').modal('hide');
                }
            });
        });

            // معالج إرجاع العقد
        $(document).on('click', '.backContract', function () {
                var contractId = $(this).data('row-id');

                Swal.fire({
                    title: 'تأكيد الإرجاع',
                    text: 'هل أنت متأكد من إرجاع هذا العقد؟',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'نعم، إرجاع',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
            $.ajax({
                            url: '{{ route('RecruitmentContract.back', ':id') }}'.replace(':id', contractId),
                method: 'GET',
                success: function (response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'تم بنجاح',
                                        text: response.message || 'تم إرجاع العقد بنجاح',
                                        confirmButtonText: 'حسناً'
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'خطأ',
                                        text: response.message || 'حدث خطأ أثناء إرجاع العقد',
                                        confirmButtonText: 'حسناً'
                                    });
                                }
                },
                error: function (xhr, status, error) {
                                console.error('AJAX Error:', error);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'خطأ',
                                    text: 'حدث خطأ أثناء إرجاع العقد',
                                    confirmButtonText: 'حسناً'
                                });
                },
                complete: function () {
                                dataTable.ajax.reload();
                            }
                        });
                    }
                });
            });

                                    // معالج إضافة رحلة
            $(document).on('click', '.add-travel-btn', function (e) {
                e.preventDefault();
                var contractId = $(this).data('contract-id');

                console.log('Add travel button clicked for contract ID:', contractId);

                if (!contractId) {
                    console.error('No contract ID found');
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: 'لم يتم العثور على معرف العقد',
                        confirmButtonText: 'حسناً'
                    });
                    return;
                }

                // التوجيه إلى صفحة إضافة رحلة
                var url = '{{ route("travel.control") }}?contract_id=' + contractId;
                console.log('Redirecting to:', url);
                window.location.href = url;
            });

            // معالج إكمال الرحلة
            $(document).on('click', '.complete-trip-btn', function () {
                var tripId = $(this).data('trip-id');
                var button = $(this);

                console.log('Complete trip button clicked for trip ID:', tripId);

                Swal.fire({
                    title: 'تأكيد إكمال الرحلة',
                    text: 'هل أنت متأكد من إكمال هذه الرحلة؟ سيتم تحديث حالة الرحلة إلى مكتملة.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، إكمال',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // إظهار حالة التحميل
                        button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>جاري الإكمال...');

                        $.ajax({
                            url: '{{ route('travel.complete', ':tripId') }}'.replace(':tripId', tripId),
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function (response) {
                                console.log('Complete trip response:', response);

                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'تم بنجاح',
                                        text: response.message || 'تم إكمال الرحلة بنجاح',
                                        confirmButtonText: 'حسناً'
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'خطأ',
                                        text: response.message || 'حدث خطأ أثناء إكمال الرحلة',
                                        confirmButtonText: 'حسناً'
                                    });
                                }
                            },
                            error: function (xhr, status, error) {
                                console.error('AJAX Error:', {
                                    status: status,
                                    error: error,
                                    response: xhr.responseText
                                });

                                let errorMessage = 'حدث خطأ أثناء إكمال الرحلة';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                }

                                Swal.fire({
                                    icon: 'error',
                                    title: 'خطأ',
                                    text: errorMessage,
                                    confirmButtonText: 'حسناً'
                                });
                            },
                            complete: function () {
                                // إعادة تفعيل الزر
                                button.prop('disabled', false).html('<i class="fas fa-check me-1"></i>إكمال الرحلة');

                                // إعادة تحميل الجدول
                                dataTable.ajax.reload();
                            }
                        });
                    }
                });
            });

            // معالج زر إضافة عاملة
            $(document).on('click', '.btn-action[href*="selectMaid"]', function(e) {
                // السماح بالانتقال الطبيعي للرابط
                return true;
            });

            // معالج زر إضافة عاملة من القائمة المنسدلة
            $(document).on('click', '.dropdown-item[href*="selectMaid"]', function(e) {
                // السماح بالانتقال الطبيعي للرابط
                return true;
            });

            // معالج زر إضافة عاملة من عمود المرحلة التالية
            $(document).on('click', '.btn-action[href*="selectMaid"]', function(e) {
                // السماح بالانتقال الطبيعي للرابط
                return true;
            });

            // ضبط موضع القائمة المنسدلة للصفوف الأخيرة
            $('.dropdown-toggle').on('click', function() {
                var $dropdown = $(this).closest('.dropdown');
                var $dropdownMenu = $dropdown.find('.dropdown-menu');
                var $row = $(this).closest('tr');
                var isLastRow = $row.is(':last-child');
                var isOnlyChild = $row.is(':only-child');

                if (isOnlyChild) {
                    // للصف الواحد، إضافة مساحة كافية
                    $row.css('margin-bottom', '200px');
                    // ضمان ظهور القائمة كاملة
                    $dropdownMenu.css({
                        'z-index': '99999',
                        'max-height': 'none',
                        'overflow': 'visible'
                    });
                } else if (isLastRow) {
                    // للصف الأخير (ولكن ليس الوحيد)، اعرض القائمة لأعلى
                    $dropdownMenu.removeClass('dropdown-menu-end').addClass('dropdown-menu-start');
                    $dropdownMenu.css({
                        'top': 'auto',
                        'bottom': '100%',
                        'margin-bottom': '5px'
                    });
                } else {
                    // للصفوف العادية، اعرض القائمة لأسفل
                    $dropdownMenu.removeClass('dropdown-menu-start').addClass('dropdown-menu-end');
                    $dropdownMenu.css({
                        'top': '100%',
                        'bottom': 'auto',
                        'margin-bottom': '0'
                    });
                }
            });

            // ضمان عدم تداخل الصفوف مع القائمة المنسدلة
            $(document).on('show.bs.dropdown', '.dropdown', function() {
                var $dropdown = $(this);
                var $dropdownMenu = $dropdown.find('.dropdown-menu');
                var $row = $dropdown.closest('tr');
                var isLastRow = $row.is(':last-child');
                var isOnlyChild = $row.is(':only-child');

                if (isOnlyChild) {
                    // للصف الواحد، لا نحتاج لإضافة مساحة إضافية
                    $row.css('margin-bottom', '0');
                } else if (isLastRow) {
                    // إضافة مساحة إضافية للصف الأخير (ولكن ليس الوحيد)
                    $row.css('margin-bottom', '100px');
                } else {
                    // للصفوف العادية، لا نحتاج لإضافة مساحة إضافية
                    $row.css('margin-bottom', '0');
                }
            });

            // إزالة المساحة الإضافية عند إغلاق القائمة المنسدلة
            $(document).on('hide.bs.dropdown', '.dropdown', function() {
                var $dropdown = $(this);
                var $row = $dropdown.closest('tr');
                var isOnlyChild = $row.is(':only-child');

                if (isOnlyChild) {
                    // إزالة المساحة الإضافية للصف الواحد
                    $row.css('margin-bottom', '0');
                } else {
                    // إزالة المساحة الإضافية للصفوف الأخرى
                    $row.css('margin-bottom', '');
                }
            });

            // معالج زر إلغاء العقد
            $(document).on('click', '.cancel-contract-btn', function(e) {
                e.preventDefault();
                var button = $(this);
                var contractId = button.data('contract-id');
                var contractNumber = button.data('contract-number');
                var clientName = button.data('client-name');
                var maidName = button.data('maid-name');

                openCancelContractModal(contractId, contractNumber, clientName, maidName);
            });
        });
    </script>

    <script>
    $(document).ready(function() {
        // Handle complete trip button click
        $(document).on('click', '.complete-trip-btn', function() {
            var tripId = $(this).data('trip-id');
            var button = $(this);

            console.log('Complete trip button clicked for trip ID:', tripId);

            Swal.fire({
                title: '{{ __("admin.confirm") }}',
                text: '{{ __("admin.mark_trip_completed_confirmation") }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{ __("admin.confirm") }}',
                cancelButtonText: '{{ __("admin.cancel") }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log('Sending complete trip request for trip ID:', tripId);

                    $.ajax({
                        url: '/admin/travel/complete/' + tripId,
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            console.log('Complete trip response:', response);

                            if (response.success) {
                                // Update the status badge
                                button.closest('td').find('.badge').removeClass('bg-warning').addClass('bg-success').text('{{ __("admin.trip_completed") }}');
                                // Remove the complete button
                                button.remove();
                                // Show success message
                                Swal.fire('{{ __("admin.success") }}', response.message, 'success');
                                // Reload the table to reflect the new contract status
                                $('#dataTableObject').DataTable().ajax.reload(null, false);

                                // If we're on the tazker status page, redirect to under warranty page
                                if (window.location.href.includes('status=tazker')) {
                                    setTimeout(function() {
                                        window.location.href = '{{ route("RecruitmentContract.index", ["status" => "under_warranty"]) }}';
                                    }, 1500);
                                }
                            } else {
                                Swal.fire('{{ __("admin.error") }}', response.message, 'error');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Complete trip error:', {
                                status: status,
                                error: error,
                                response: xhr.responseText
                            });
                            Swal.fire('{{ __("admin.error") }}', '{{ __("admin.something_went_wrong") }}', 'error');
                        }
                    });
                }
            });
        });
    });
    </script>

    <script>
    $(document).ready(function() {
        // تفعيل tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // إعادة تفعيل tooltips بعد تحميل الجدول
        dataTable.on('draw', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // ضبط موضع القائمة المنسدلة للصفوف الأخيرة
            $('.dropdown-toggle').on('click', function() {
                var $dropdown = $(this).closest('.dropdown');
                var $dropdownMenu = $dropdown.find('.dropdown-menu');
                var $row = $(this).closest('tr');
                var isLastRow = $row.is(':last-child');
                var isOnlyChild = $row.is(':only-child');

                if (isOnlyChild) {
                    // للصف الواحد، إضافة مساحة كافية
                    $row.css('margin-bottom', '200px');
                    // ضمان ظهور القائمة كاملة
                    $dropdownMenu.css({
                        'z-index': '99999',
                        'max-height': 'none',
                        'overflow': 'visible'
                    });
                } else if (isLastRow) {
                    // للصف الأخير (ولكن ليس الوحيد)، اعرض القائمة لأعلى
                    $dropdownMenu.removeClass('dropdown-menu-end').addClass('dropdown-menu-start');
                    $dropdownMenu.css({
                        'top': 'auto',
                        'bottom': '100%',
                        'margin-bottom': '5px'
                    });
                } else {
                    // للصفوف العادية، اعرض القائمة لأسفل
                    $dropdownMenu.removeClass('dropdown-menu-start').addClass('dropdown-menu-end');
                    $dropdownMenu.css({
                        'top': '100%',
                        'bottom': 'auto',
                        'margin-bottom': '0'
                    });
                }
            });
        });
    });
    </script>

    <!-- موديل إلغاء العقد -->
    <div class="modal fade" id="cancelContractModal" tabindex="-1" aria-labelledby="cancelContractModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelContractModalLabel">
                        <i class="fas fa-times-circle text-danger"></i>
                        إلغاء العقد
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- معلومات العقد -->
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle"></i> تحذير:</h6>
                        <p>سيتم إلغاء العقد رقم <strong id="contractNumber"></strong></p>
                        <p>العميل: <strong id="clientName"></strong></p>
                        <p>العاملة: <strong id="maidName"></strong></p>
                    </div>

                    <form id="cancelContractForm">
                        @csrf
                        <input type="hidden" id="contractId" name="contract_id">

                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group mb-3">
                                    <label for="cancel_reason" class="form-label">
                                        <i class="fas fa-comment"></i>
                                        سبب الإلغاء
                                    </label>
                                    <textarea class="form-control" id="cancel_reason" name="cancel_reason" rows="3"
                                              placeholder="اكتب سبب إلغاء العقد..." required></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-user-minus"></i>
                                        حالة العاملة بعد الإلغاء
                                    </label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="maid_status_after_cancel"
                                               id="status_available" value="available" required>
                                        <label class="form-check-label" for="status_available">
                                            <i class="fas fa-check text-success"></i>
                                            متاحة
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="maid_status_after_cancel"
                                               id="status_unavailable" value="backout" required>
                                        <label class="form-check-label" for="status_unavailable">
                                            <i class="fas fa-ban text-danger"></i>
                                            باك اوت
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>
                        إلغاء
                    </button>
                    <button type="button" class="btn btn-danger" onclick="submitCancelContract()">
                        <i class="fas fa-trash"></i>
                        تأكيد الإلغاء
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // دالة فتح موديل إلغاء العقد
    function openCancelContractModal(contractId, contractNumber, clientName, maidName) {
        $('#contractId').val(contractId);
        $('#contractNumber').text(contractNumber);
        $('#clientName').text(clientName);
        $('#maidName').text(maidName);
        $('#cancel_reason').val('');
        $('input[name="maid_status_after_cancel"]').prop('checked', false);
        $('#cancelContractModal').modal('show');
    }

    // دالة إرسال إلغاء العقد
    function submitCancelContract() {
        var formData = $('#cancelContractForm').serialize();

        $.ajax({
            url: '{{ route("RecruitmentContract.cancel") }}',
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    $('#cancelContractModal').modal('hide');
                    Swal.fire('نجح', response.message, 'success');
                    $('#dataTableObject').DataTable().ajax.reload(null, false);
                } else {
                    Swal.fire('خطأ', response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Cancel contract error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                Swal.fire('خطأ', 'حدث خطأ أثناء إلغاء العقد', 'error');
            }
        });
    }
    </script>
@endsection
