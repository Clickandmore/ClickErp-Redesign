function exportFullStatement(url, button) {
    button.disabled = true;
    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'html',
        success: function(html) {
            // Parse without executing the print page's scripts or opening a print dialog.
            const document = new DOMParser().parseFromString(html, 'text/html');
            const table = document.querySelector('#simpleTable4');
            if (table) {
                TableToExcel.convert(table);
            } else {
                validToster('تعذر تجهيز كشف الحساب للتصدير');
            }
        },
        error: function(error) {
            handleErrorResponce(error);
        },
        complete: function() {
            button.disabled = false;
        }
    });
}
