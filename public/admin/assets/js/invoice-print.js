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

    function waitForImage(image) {
        if (!image || image.complete) {
            return Promise.resolve();
        }

        return new Promise(function (resolve) {
            image.addEventListener("load", resolve, { once: true });
            image.addEventListener("error", resolve, { once: true });
        });
    }

    function waitForPrintableAssets() {
        var images = Array.prototype.slice.call(document.querySelectorAll("img"));
        var imagePromises = images.map(waitForImage);
        var fontPromise = document.fonts && typeof document.fonts.ready === "object"
            ? document.fonts.ready
            : Promise.resolve();

        var timeoutPromise = new Promise(function (resolve) {
            setTimeout(resolve, 3000);
        });

        return Promise.race([
            Promise.all(imagePromises.concat([fontPromise])),
            timeoutPromise
        ]);
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
            printButton.addEventListener("click", function (event) {
                if (event && typeof event.preventDefault === "function") {
                    event.preventDefault();
                }
                triggerPrint();
            });
        }

        var savePdfButton = document.querySelector('[data-action="save-pdf"]');
        if (savePdfButton) {
            savePdfButton.addEventListener("click", function (event) {
                if (event && typeof event.preventDefault === "function") {
                    event.preventDefault();
                }
                triggerPrint();
            });
        }

        var query = parseQuery();
        if (query && query.get("autoprint") === "1") {
            waitForPrintableAssets().then(triggerPrint);
        }
    });
})();
