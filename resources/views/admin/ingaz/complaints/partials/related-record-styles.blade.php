<style>
    /* شارة "متعلق بـ" — لون مستقل لكل نوع ارتباط (طلب / عقد استقدام / عقد إيواء / مكتب / عميل / مرجع) */
    .relation-badge {
        align-items: center;
        border: 1px solid transparent;
        border-radius: 999px;
        display: inline-flex;
        font-size: 12px;
        font-weight: 600;
        gap: 6px;
        line-height: 1.7;
        max-width: 100%;
        padding: 3px 10px;
        text-decoration: none;
        transition: box-shadow .2s ease, transform .2s ease;
        white-space: nowrap;
        /* حتى لا تتمدد الشارة داخل حاويات grid/flex في صفحة التفاصيل */
        align-self: start;
        justify-self: start;
        width: fit-content;
    }

    .relation-badge i {
        font-size: 11px;
        opacity: .85;
    }

    .relation-badge__ref {
        border-inline-start: 1px solid currentColor;
        direction: ltr;
        font-weight: 700;
        margin-inline-start: 2px;
        opacity: .95;
        padding-inline-start: 7px;
        unicode-bidi: embed;
    }

    a.relation-badge:hover {
        box-shadow: 0 3px 8px rgba(34, 45, 76, .14);
        transform: translateY(-1px);
    }

    .relation-badge--order { background: #e8f4fd; border-color: #bde0f7; color: #1a6ea8; }
    .relation-badge--contract { background: #e8f7ef; border-color: #bfe6d1; color: #237953; }
    .relation-badge--shelter_contract { background: #fff4dc; border-color: #f4dcaa; color: #9a6612; }
    .relation-badge--rental_contract { background: #e6f7f6; border-color: #b9e5e3; color: #12726f; }
    .relation-badge--office { background: #eef0ff; border-color: #cfd4f8; color: #4b58bf; }
    .relation-badge--client { background: #fdeaf3; border-color: #f6c8dd; color: #ab3670; }
    .relation-badge--manual { background: #f1eefb; border-color: #ddd4f4; color: #6a51ba; }
    .relation-badge--reference { background: #eef1f6; border-color: #d9dfe8; color: #566274; }

    .relation-badge--empty {
        background: #f6f7f9;
        border-color: #e5e8ee;
        color: #97a1b0;
        font-weight: 500;
    }
</style>
