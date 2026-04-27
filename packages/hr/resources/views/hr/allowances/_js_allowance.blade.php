<script>
    function getTypeHtml() {
        var selectedOption = document.getElementById("allowance-type").value;
        document.getElementById("day-field").style.display = (selectedOption == 1) ? "block" : "none";
        document.getElementById("field").style.display = (selectedOption == 1 || selectedOption == 2 || selectedOption == 3) ? "block" : "none";
        document.getElementById("value-field").style.display = (selectedOption == 2) ? "block" : "none";
        document.getElementById("percentage-field").style.display = (selectedOption == 3) ? "block" : "none";

         // Set max value for day-input when selectedOption is 3
        var dayInput = document.getElementById("day-input");
        if (selectedOption == 3) {
            dayInput.setAttribute("data-validation-allowing", "range[0;100]");
        } else {
            dayInput.removeAttribute("data-validation-allowing");
        }
    }
</script>
