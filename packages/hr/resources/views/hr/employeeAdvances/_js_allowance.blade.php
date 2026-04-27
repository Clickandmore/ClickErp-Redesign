{{-- <script>
    function getTypeHtml() {
        var selectedOption = document.getElementById("payment_method").value;
        document.getElementById("day-field").style.display = (selectedOption == ) ? "block" : "none";
        document.getElementById("field").style.display = (selectedOption == 1 || selectedOption == 2 || selectedOption == 3) ? "block" : "none";
        document.getElementById("value-field").style.display = (selectedOption == 2) ? "block" : "none";
        document.getElementById("percentage-field").style.display = (selectedOption == 3) ? "block" : "none";
    }
</script> --}}
<script>
    $(document).ajaxComplete(function(){
        $('#payment_method').change(function(){
            var selectedValue = $(this).val();
            if(selectedValue === "monthly_installment"){
                $('#field').show();
                $('#table').hide();
            } else if(selectedValue === "debt_payments"){
                $('#field').hide();
                $('#table').show();
            } else {
                $('#field').hide();
                $('#table').hide();
            }
        });
        var selectedValue = $('#payment_method').val();
        if(selectedValue === "monthly_installment"){
            $('#field').show();
            $('#table').hide();
        } else if(selectedValue === "debt_payments"){
            $('#field').hide();
            $('#table').show();
        } else {
            $('#field').hide();
            $('#table').hide();
        }
    });
</script>

