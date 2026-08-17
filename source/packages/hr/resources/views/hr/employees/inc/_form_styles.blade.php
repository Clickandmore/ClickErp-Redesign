<style>
    .employee-form-page {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .employee-form-hero {
        border: 1px solid #e3eaf3;
        border-radius: 18px;
        overflow: hidden;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        box-shadow: 0 10px 28px rgba(31, 70, 125, 0.06);
    }

    .employee-form-hero .card-body {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1.5rem;
        flex-wrap: wrap;
        padding: 1.5rem;
    }

    .employee-form-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.45rem 0.9rem;
        border-radius: 999px;
        background: #eef4ff;
        color: #2b5cab;
        font-size: 0.85rem;
        font-weight: 700;
        margin-bottom: 0.85rem;
    }

    .employee-form-title {
        margin-bottom: 0.5rem;
        color: #243447;
        font-weight: 700;
    }

    .employee-form-subtitle {
        margin-bottom: 0;
        max-width: 760px;
        color: #68778c;
        line-height: 1.8;
    }

    .employee-form-chip-list {
        display: flex;
        gap: 0.65rem;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .employee-form-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.65rem 0.95rem;
        border-radius: 14px;
        background: #ffffff;
        border: 1px solid #e3eaf3;
        color: #4e6077;
        font-weight: 600;
    }

    .employee-form-card {
        border: 1px solid #e3eaf3;
        border-radius: 18px;
        box-shadow: 0 10px 28px rgba(31, 70, 125, 0.05);
        overflow: hidden;
    }

    .employee-form-card .card-header {
        padding: 1.15rem 1.35rem;
        background: #f8fbff;
        border-bottom: 1px solid #e8eef6;
    }

    .employee-form-card .card-body {
        padding: 1.35rem;
        overflow-x: auto;
    }

    .employee-form-card-title {
        margin-bottom: 0.25rem;
        color: #243447;
        font-weight: 700;
    }

    .employee-form-card-hint {
        margin-bottom: 0;
        color: #748399;
        font-size: 0.92rem;
    }

    .employee-form-card .table {
        margin-bottom: 0;
        min-width: 720px;
    }

    .employee-form-card .table thead th {
        background: #f5f8fc;
        border-bottom: 1px solid #e8eef6;
        color: #53657d;
        font-weight: 700;
    }

    .employee-form-card .form-label {
        color: #3e5168;
        font-weight: 700;
    }

    .employee-form-card .employee-native-select,
    .employee-form-card .employee-select-filter {
        border-color: #d9e3f0;
    }

    .employee-form-card .employee-native-select:focus,
    .employee-form-card .employee-select-filter:focus,
    .employee-form-card .form-control:focus {
        border-color: #8fb2e8;
        box-shadow: 0 0 0 0.2rem rgba(63, 114, 202, 0.12);
    }

    .employee-form-card .fa-plus-circle,
    .employee-form-card .fa-trash-o {
        cursor: pointer;
        font-size: 1.2rem;
        margin-inline-start: 0.35rem;
    }

    .employee-form-card .fa-plus-circle {
        color: #2b73d9;
    }

    .employee-form-card .fa-trash-o {
        color: #c85555;
    }

    .employee-form-submit {
        position: sticky;
        bottom: 1rem;
        z-index: 5;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.25rem;
        border-radius: 18px;
        border: 1px solid #e3eaf3;
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 10px 28px rgba(31, 70, 125, 0.08);
        backdrop-filter: blur(10px);
    }

    .employee-form-submit strong {
        display: block;
        color: #243447;
    }

    .employee-form-submit span {
        color: #718096;
        font-size: 0.92rem;
    }

    .employee-form-submit .btn {
        min-width: 180px;
    }

    @media (max-width: 991.98px) {
        .employee-form-hero .card-body,
        .employee-form-submit {
            flex-direction: column;
            align-items: stretch;
        }

        .employee-form-chip-list {
            justify-content: flex-start;
        }
    }

    @media (max-width: 767.98px) {
        .employee-form-submit {
            position: static;
        }
    }
</style>
