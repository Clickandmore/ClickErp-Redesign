<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
    function exportTable(btnId, tableId, excelFileName) {
        // Export to Excel functionality
        document.getElementById(btnId).addEventListener('click', function() {
            var wb = XLSX.utils.book_new();
            var table = document.getElementById(tableId);
            var ws = XLSX.utils.table_to_sheet(table);
            XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');
            XLSX.writeFile(wb, excelFileName);
        });
    }
</script>
