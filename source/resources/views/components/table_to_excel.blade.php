@push('js')
<script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
<script>
    function htmlTableToExcel(type, fileName) {
            var data = document.getElementById('tblToExcl');
            var excelFile = XLSX.utils.table_to_book(data, {
                sheet: "sheet1"
            });
            XLSX.write(excelFile, {
                bookType: type,
                bookSST: true,
                type: 'base64'
            });

            // Get the current date and time
            const now = new Date();
            const currentDate = now.toLocaleDateString('en-GB');
            const currentTime = now.toLocaleTimeString('en-US', {
                hour12: true,
                hour: 'numeric',
                minute: '2-digit'
            });

            // Append the date and time to the file name
            const fileNameWithDateTime = `${fileName} [${currentDate}] [${currentTime}]`;

            // Write the Excel file
            XLSX.writeFile(excelFile, fileNameWithDateTime + '.' + type);
        }
</script>
@endpush
