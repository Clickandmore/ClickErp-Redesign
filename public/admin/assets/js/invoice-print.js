(function () {
    function parseQuery() {
        try {
            return new URLSearchParams(window.location.search);
        } catch (e) {
            return null;
        }
    }

    function triggerPrint() {
        updatePrintedAt();
        if (typeof window.print === "function") {
            window.print();
        }
    }

    function pad(number) {
        return number < 10 ? "0" + number : String(number);
    }

    function updatePrintedAt() {
        var printedAt = document.getElementById("printed-at");
        if (!printedAt) {
            return;
        }

        var now = new Date();
        var formatted = now.getFullYear()
            + "-"
            + pad(now.getMonth() + 1)
            + "-"
            + pad(now.getDate())
            + " "
            + pad(now.getHours())
            + ":"
            + pad(now.getMinutes())
            + ":"
            + pad(now.getSeconds());

        printedAt.textContent = formatted;
    }

    document.addEventListener("DOMContentLoaded", function () {
        var printButton = document.querySelector('[data-action="print"]');
        if (printButton) {
            printButton.addEventListener("click", function () {
                triggerPrint();
            });
        }

        var savePdfButton = document.querySelector('[data-action="save-pdf"]');
        if (savePdfButton) {
            savePdfButton.addEventListener("click", function () {
                triggerPrint();
            });
        }

        var query = parseQuery();
        if (query && query.get("autoprint") === "1") {
            setTimeout(triggerPrint, 250);
        }
    });
})();
