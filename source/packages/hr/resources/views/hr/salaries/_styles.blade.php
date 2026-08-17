<style>
    .payroll-page .payroll-toolbar {
        display: flex;
        align-items: end;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .payroll-page .payroll-note {
        background: #2387da;
        color: #fff;
        border-radius: 10px;
        padding: 1rem 1.25rem;
        font-size: 1rem;
        line-height: 1.9;
        box-shadow: 0 10px 30px rgba(35, 135, 218, 0.16);
    }

    .payroll-page .payroll-card {
        border: 0;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 10px 35px rgba(15, 23, 42, 0.08);
    }

    .payroll-page .payroll-table {
        margin-bottom: 0;
        min-width: 1400px;
    }

    .payroll-page .payroll-table thead th {
        background: #24343e;
        color: #fff;
        border-color: rgba(255, 255, 255, 0.08);
        font-weight: 700;
        vertical-align: middle;
        text-align: center;
        white-space: nowrap;
    }

    .payroll-page .payroll-table tbody td {
        vertical-align: middle;
        text-align: center;
        background: #fff;
    }

    .payroll-page .payroll-table tbody tr:nth-of-type(even) td {
        background: #fafbfd;
    }

    .payroll-page .payroll-input {
        min-width: 95px;
        text-align: center;
    }

    .payroll-page .payroll-input[readonly] {
        background: #f7f9fc;
        color: #25313d;
        font-weight: 600;
    }

    .payroll-page .payroll-net {
        color: #28a9cf;
        font-weight: 700;
    }

    .payroll-page .payroll-row.is-inactive td {
        opacity: 0.55;
    }

    .payroll-page .payroll-empty {
        padding: 3rem 1rem;
        color: #6b7280;
        font-weight: 600;
    }

    .payroll-page .payroll-meta {
        color: #64748b;
        font-size: 0.95rem;
    }
</style>
