<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="description" content=""/>
    <title>{{$print_settings->title ?? 'شركة الفيصل للنقل'}}</title>
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"/>
    @include('erp.crm.price_offers.css')
    <style>
        .paper {
            page-break-after: always;
            page-break-inside: avoid;
        }
        .page-section {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
@if($offer->desc)
    <section class="paper">
        @include('erp.crm.price_offers.header')
        <main class="main">
            <div class="info">
                <p>التاريخ: {{$offer->date}}</p>
                <p>السادة شركة / {{$print_settings->title ?? 'شركة الفيصل للنقل'}} الموقرين</p>
                <p>تحية طيبة وبعد...</p>
            </div>
            <div id="offer-desc-container" class="show_price">
                {!! $offer->desc !!}
            </div>
        </main>
        @include('crm::crm.price_offers.section')
        @include('crm::crm.price_offers.footer')
    </section>
@endif

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        const maxHeight = 650; // Max height for each page
        const $offerDescContainer = $('#offer-desc-container');
        const offerDescHtml = $offerDescContainer.html();

        // Clear the initial content
        $offerDescContainer.html('');

        // Function to split content into pages
        function splitContentIntoPages(content, $container, maxHeight) {
            const $tempDiv = $('<div>').html(content);
            let currentPage = 1;

            // Function to create a new page section
            function createNewPage() {
                const $newSection = $('<section>').addClass('paper');
                $newSection.append($('.paper header').first().clone());
                $newSection.append($('<main class="main">').append('<div class="show_price" id="offer-desc-container"></div>'));
                $newSection.append($('.paper footer').first().clone());
                $('body').append($newSection);
                $container = $newSection.find('#offer-desc-container');
                return $container;
            }

            let $pageSection = $container; // Start with the initial container

            while ($tempDiv.contents().length) {
                // Fill the page section with content until maxHeight is reached
                while ($tempDiv.contents().length && $pageSection.height() <= maxHeight) {
                    $pageSection.append($tempDiv.contents().first());
                }

                // If the page section exceeds maxHeight, create a new page
                if ($pageSection.height() > maxHeight) {
                    const lastElement = $pageSection.contents().last();
                    lastElement.remove();
                    $pageSection = createNewPage();
                    $pageSection.append(lastElement);
                }

                // If content still remains, increment page counter
                if ($tempDiv.contents().length) {
                    currentPage++;
                }
            }
        }

        // Call the function to split the content
        splitContentIntoPages(offerDescHtml, $offerDescContainer, maxHeight);
    });
</script>
<script>
    // Function to print the current page
    function printAndRedirect(url) {
        window.print(); // Print the current page

        // Redirect to the specified URL after a short delay (adjust milliseconds as needed)
        setTimeout(function() {
            window.location.href = url;
        }, 500); // Redirect after 1 second (1000 milliseconds)
    }

    // Call printAndRedirect function when the page is loaded
    window.onload = function() {
        var redirectUrl = "{{ route('crm.price-offers.index') }}"; // Replace with your route name
        printAndRedirect(redirectUrl);
    };
</script>
</body>

</html>
