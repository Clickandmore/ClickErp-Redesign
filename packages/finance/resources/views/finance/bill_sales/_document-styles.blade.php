<style>
    @page {
        size: A4;
        margin: 9mm;
    }

    .invoice-document,
    .invoice-document * {
        box-sizing: border-box;
    }

    .invoice-document {
        direction: rtl;
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
        padding: 24px;
        background: #fff;
        color: #111827;
        border: 1px solid #cfd6dd;
        border-radius: 14px;
        font-family: "DejaVu Sans", "IBM Plex Sans Arabic", Arial, sans-serif;
        font-size: 13px;
        line-height: 1.55;
    }

    .invoice-document__brand {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 86px;
        margin-bottom: 8px;
        text-align: center;
    }

    .invoice-document__brand img {
        max-width: 240px;
        max-height: 76px;
        object-fit: contain;
    }

    .invoice-document__brand-name {
        font-size: 18px;
        font-weight: 900;
        color: #145f58;
    }

    .invoice-document__title {
        margin: 4px 0 14px;
        text-align: center;
    }

    .invoice-document__title h1 {
        margin: 0;
        font-size: 26px;
        font-weight: 900;
        color: #111;
    }

    .invoice-document__title p {
        margin: 2px 0 0;
        color: #64748b;
        font-weight: 700;
    }

    .invoice-meta {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 230px minmax(0, 1fr);
        gap: 16px;
        align-items: center;
        padding: 14px 18px;
        background: #f1f3f4;
        border-radius: 13px;
        page-break-inside: avoid;
    }

    .invoice-meta__column {
        display: grid;
        gap: 8px;
    }

    .invoice-meta__column--en {
        direction: ltr;
        text-align: left;
    }

    .invoice-meta__item strong,
    .invoice-field strong {
        display: block;
        font-weight: 900;
    }

    .invoice-meta__item span {
        display: block;
        margin-top: 1px;
    }

    .invoice-meta__qr {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .invoice-meta__qr img {
        display: block;
        width: 210px;
        height: 210px;
        object-fit: contain;
        background: #fff;
        padding: 5px;
    }

    .invoice-meta__qr-placeholder {
        width: 180px;
        height: 180px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px dashed #9ca3af;
        color: #6b7280;
        text-align: center;
        background: #fff;
    }

    .invoice-section {
        margin-top: 12px;
        padding: 12px;
        border: 1px solid #20252a;
        border-radius: 12px;
        page-break-inside: avoid;
    }

    .invoice-section__title {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        margin: 0 0 9px;
        font-size: 16px;
        font-weight: 900;
    }

    .invoice-fields {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }

    .invoice-field {
        min-width: 0;
    }

    .invoice-field--wide {
        grid-column: 1 / -1;
    }

    .invoice-field__label {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        margin: 0 4px 3px;
        font-size: 11px;
        font-weight: 800;
    }

    .invoice-field__value {
        min-height: 34px;
        padding: 7px 10px;
        background: #e5e7e9;
        word-break: break-word;
    }

    .invoice-lines {
        margin-top: 12px;
        border: 1px solid #20252a;
        border-radius: 12px;
        overflow: hidden;
    }

    .invoice-lines__title {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 12px;
        font-size: 16px;
        font-weight: 900;
    }

    .invoice-lines table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .invoice-lines th {
        padding: 9px 5px;
        background: #dfe2e4;
        border-top: 1px solid #c8cdd1;
        border-left: 1px solid #c8cdd1;
        font-size: 10px;
        line-height: 1.35;
        text-align: center;
        vertical-align: middle;
    }

    .invoice-lines td {
        padding: 10px 6px;
        border-top: 1px solid #e1e5e8;
        border-left: 1px solid #e1e5e8;
        text-align: center;
        vertical-align: middle;
    }

    .invoice-lines th:last-child,
    .invoice-lines td:last-child {
        border-left: 0;
    }

    .invoice-lines .description {
        width: 25%;
        text-align: right;
        font-weight: 800;
    }

    .invoice-summary-wrap {
        display: flex;
        justify-content: flex-end;
        margin-top: 16px;
        page-break-inside: avoid;
    }

    .invoice-summary-table {
        width: min(560px, 100%);
        border-collapse: collapse;
        border: 1px solid #20252a;
    }

    .invoice-summary-table th,
    .invoice-summary-table td {
        padding: 10px 12px;
        border: 1px solid #20252a;
    }

    .invoice-summary-table th {
        text-align: center;
        font-weight: 900;
    }

    .invoice-summary-table td {
        width: 145px;
        direction: ltr;
        text-align: center;
        font-weight: 900;
        white-space: nowrap;
    }

    .invoice-summary-table tr:last-child {
        background: #edf7f5;
        font-size: 15px;
    }

    .invoice-document__notes {
        margin-top: 14px;
        padding: 10px 12px;
        border: 1px solid #d5dbe0;
        border-radius: 9px;
        page-break-inside: avoid;
    }

    .invoice-document__footer {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        margin-top: 14px;
        padding-top: 10px;
        border-top: 1px solid #d8dde2;
        color: #64748b;
        font-size: 10px;
    }

    @media (max-width: 800px) {
        .invoice-document {
            width: 100%;
            padding: 14px;
        }

        .invoice-meta {
            grid-template-columns: 1fr;
        }

        .invoice-meta__qr {
            grid-row: 1;
        }

        .invoice-fields {
            grid-template-columns: 1fr;
        }

        .invoice-field--wide {
            grid-column: auto;
        }

        .invoice-lines {
            overflow-x: auto;
        }

        .invoice-lines table {
            min-width: 820px;
        }
    }

    @media print {
        .invoice-document {
            width: 100%;
            margin: 0;
            padding: 0;
            border: 0;
            border-radius: 0;
            font-size: 8.5px;
            line-height: 1.35;
        }

        .invoice-document__brand {
            min-height: 42px;
            margin-bottom: 2px;
        }

        .invoice-document__brand img {
            max-height: 40px;
        }

        .invoice-document__brand-name {
            font-size: 14px;
        }

        .invoice-document__title {
            margin: 2px 0 7px;
        }

        .invoice-document__title h1 {
            font-size: 17px;
        }

        .invoice-document__title p {
            margin-top: 0;
        }

        .invoice-meta {
            grid-template-columns: minmax(0, 1fr) 125px minmax(0, 1fr);
            padding: 7px 11px;
            gap: 8px;
            border-radius: 8px;
        }

        .invoice-meta__column {
            gap: 4px;
        }

        .invoice-meta__qr img {
            width: 120px;
            height: 120px;
            padding: 3px;
        }

        .invoice-section,
        .invoice-lines {
            margin-top: 5px;
        }

        .invoice-section {
            padding: 5px 7px;
            border-radius: 7px;
        }

        .invoice-section__title,
        .invoice-lines__title {
            margin-bottom: 4px;
            font-size: 11px;
        }

        .invoice-fields {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 4px 6px;
        }

        .invoice-field--wide {
            grid-column: 1 / -1;
        }

        .invoice-field__label {
            margin-bottom: 1px;
            font-size: 7.5px;
        }

        .invoice-field__value {
            min-height: 18px;
            padding: 3px 6px;
        }

        .invoice-lines {
            overflow: visible;
            border-radius: 7px;
        }

        .invoice-lines__title {
            padding: 5px 7px;
        }

        .invoice-lines table {
            min-width: 0;
        }

        .invoice-lines th {
            padding: 4px 2px;
            font-size: 6.7px;
        }

        .invoice-lines td {
            padding: 5px 3px;
        }

        .invoice-summary-wrap {
            margin-top: 6px;
        }

        .invoice-summary-table th,
        .invoice-summary-table td {
            padding: 4px 7px;
        }

        .invoice-summary-table {
            width: 430px;
        }

        .invoice-summary-table td {
            width: 115px;
        }

        .invoice-document__notes {
            margin-top: 6px;
            padding: 5px 7px;
        }

        .invoice-document__footer {
            margin-top: 6px;
            padding-top: 4px;
            font-size: 6.5px;
        }
    }
</style>
