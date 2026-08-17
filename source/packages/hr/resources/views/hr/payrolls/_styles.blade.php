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
        overflow-x: auto;
        box-shadow: 0 10px 35px rgba(15, 23, 42, 0.08);
    }

    .payroll-page .payroll-table {
        margin-bottom: 0;
        min-width: 1550px;
        overflow: auto !important;
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

    .payroll-page .payroll-summary tbody tr {
        cursor: pointer;
        transition: background-color 0.18s ease, box-shadow 0.18s ease;
    }

    .payroll-page .payroll-summary tbody tr.is-selected td {
        background: #e9f6ff;
    }

    .payroll-page .payroll-summary tbody tr.is-selected:nth-of-type(even) td {
        background: #dff1ff;
    }

    .payroll-page .payroll-select-cell,
    .payroll-page .payroll-select-header {
        width: 52px;
        text-align: center;
    }

    .payroll-page .payroll-select-input {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .payroll-page .payroll-selection-summary {
        color: #0f5f95;
        font-weight: 600;
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

    .payroll-page .payroll-summary th {
        white-space: nowrap;
    }

    .table-responsive{
        overflow-x: auto !important;
    }

    .payroll-print-sheet {
        display: none;
    }

    @page {
        size: A4 landscape;
        margin: 4mm;
    }

    @media print {
        body {
            background: #fff !important;
            margin: 0 !important;
        }

        body * {
            visibility: hidden !important;
        }

        .payroll-print-sheet,
        .payroll-print-sheet * {
            visibility: visible !important;
            box-sizing: border-box;
        }

        .payroll-print-sheet {
            display: block !important;
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            width: 100%;
            max-width: none !important;
            min-height: 100%;
            box-sizing: border-box;
            color: #111827;
            background: #fff;
            font-family: "DejaVu Sans", Tahoma, Arial, sans-serif;
        }

        .payroll-print-header {
            display: grid;
            grid-template-columns: 155px 1fr 155px;
            align-items: center;
            gap: 12px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }

        .payroll-print-logo img {
            max-width: 135px;
            max-height: 48px;
            object-fit: contain;
        }

        .payroll-print-title {
            text-align: center;
        }

        .payroll-print-title h2 {
            margin: 0 0 4px;
            font-size: 18px;
            font-weight: 800;
        }

        .payroll-print-title div,
        .payroll-print-date {
            font-size: 11px;
            color: #374151;
        }

        .payroll-print-date {
            text-align: left;
        }

        .payroll-print-summary {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 5px;
            margin-bottom: 7px;
        }

        .payroll-print-summary div,
        .payroll-print-notes {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            background: #f8fafc;
        }

        .payroll-print-summary span {
            display: block;
            color: #64748b;
            font-size: 8px;
            margin-bottom: 2px;
        }

        .payroll-print-summary strong {
            display: block;
            font-size: 10px;
            color: #111827;
        }

        .payroll-print-notes {
            margin-bottom: 8px;
            font-size: 10px;
        }

        .payroll-print-table {
            width: 100%;
            max-width: none !important;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 7.8px;
            line-height: 1.25;
        }

        .payroll-print-table th,
        .payroll-print-table td {
            border: 0.7px solid #94a3b8;
            padding: 3px 2px;
            text-align: center;
            vertical-align: middle;
            word-break: normal;
            overflow-wrap: normal;
        }

        .payroll-print-table th {
            background: #e2e8f0 !important;
            color: #111827 !important;
            font-weight: 800;
            white-space: nowrap;
            font-size: 7px;
            padding-top: 4px;
            padding-bottom: 4px;
        }

        .payroll-print-table td {
            white-space: nowrap;
        }

        .payroll-print-table td:nth-child(2),
        .payroll-print-table td:nth-child(17) {
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .payroll-print-table .print-col-index {
            width: 3.5%;
        }

        .payroll-print-table .print-col-name {
            width: 11%;
        }

        .payroll-print-table .print-col-passport,
        .payroll-print-table .print-col-id {
            width: 7.5%;
        }

        .payroll-print-table .print-col-nationality {
            width: 6.5%;
        }

        .payroll-print-table .print-col-days {
            width: 4.2%;
        }

        .payroll-print-table .print-col-money {
            width: 5.4%;
        }

        .payroll-print-table .print-col-notes {
            width: 7%;
        }

        .payroll-print-table tbody tr:nth-child(even) td {
            background: #f8fafc !important;
        }
    }
</style>
