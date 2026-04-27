<div class="col d-flex justify-content-end no-print" id="print_excel">
    <button onclick="handlePrint()" class="btn btn-success ml-2"><i class="fa fa-print"></i></button>
</div>
<div id="print_section">
    @include('finance::finance.print-header')
    <div class="vat-box text-center w-100">
        <!-- التاريخ -->
        <div class="date-top">
            {{ now()->format('d/m/Y H:i') }}
        </div>

        <!-- عنوان التقرير -->
        <div class="text-center">
            {{ __("finance.VAT tax return") }}
        </div>

        <!-- البيانات يمين ويسار -->
        <div class="row">
            <!-- العمود الأيمن -->
            <div class="col" style="text-align: right">
                <div class="item">
                    <span class="label">{{ __('finance::finance.from_date') }} :</span>
                    <span>{{ request('from_date') ?? ($min_date ?? '-') }}</span>
                </div>
                <div class="item">
                    <span class="label">{{ __('finance::finance.name') }} :</span>
                    <span>{{ @$setting_web->website_name }}</span>
                </div>
                <div class="item">
                    <span class="label">{{ __('finance::finance.address') }} :</span>
                    <span>{{ @$setting_web->location }}</span>
                </div>
            </div>

            <!-- العمود الأيسر -->
            <div class="col" style="text-align: left">
                <div class="item">
                    <span class="label">{{ __('finance::finance.to_date') }} :</span>
                    <span>{{ request('to_date') ?? ($max_date ?? '-') }}</span>
                </div>
                <div class="item">
                    <span class="label">{{ __('finance::finance.Tax number') }} :</span>
                    <span>{{ @$setting_web->tax_number }}</span>
                </div>
            </div>
        </div>
    </div>
    <table  class="table table-hover table-bordered table-striped" id="toExcelTable" style="width:100%">
        <thead >
            <tr class="header" >
                <th   scope="col" >{{ __("finance::finance.type") }} </th>
                <th   scope="col" >{{ __("finance::finance.VAT category") }} </th>
                <th   scope="col" >{{ __("finance::finance.Amount") }} </th>
                <th   scope="col" >{{ __("finance::finance.Value Added Tax") }} </th>
            </tr>
        </thead>
        <tbody >
        @php
            $saleCount = count($sales);
            $purchaseCount = count($purchases);
        @endphp

        @foreach($sales as $key => $sale)
            <tr style="background-color: #cbfff4">
                @if($key == 0)
                    <td rowspan="{{ $saleCount }}">{{ __('finance::finance.sales') }}</td>
                @endif
                <td> {{isset($sale->tax_per) ? $sale->tax_per : 0}} % </td>
                <td>{{ isset($sale->sum_value) ? number_format($sale->sum_value, 2) : 0 }}</td>
                <td>{{ isset($sale->sum_total_tax_value) ? number_format($sale->sum_total_tax_value, 2) : 0 }}</td>
            </tr>
        @endforeach

        @foreach($purchases as $key => $purchase)
            <tr style="background-color: #ffc3c3">
                @if($key == 0)
                    <td rowspan="{{ $purchaseCount }}">{{ __('finance::finance.purchases') }}</td>
                @endif
                <td>{{ isset($purchase->discount_per) ? $purchase->discount_per : 0 }} %</td>
                <td>{{ isset($purchase->sum_value) ? number_format($purchase->sum_value, 2) : 0 }}</td>
                <td>{{ isset($purchase->sum_total_discount_value) ? number_format($purchase->sum_total_discount_value, 2) : 0 }}</td>
            </tr>
        @endforeach



        </tbody>
    </table>
    <table  class="table table-hover table-bordered table-striped" id="toExcelTable" style="width:100%">
        <tbody>
            <tr style="background-color: #ffffc3">
                <td colspan="4">{{ __('finance::finance.Net tax due') }}</td>
                <td> {{  number_format($sales->sum('sum_total_tax_value') - $purchases->sum('sum_total_discount_value'), 2) }} </td>
            </tr>
        </tbody>
    </table>
</div>

<script>
    let mediaRecorder;
    let audioChunks = [];

    // Start Recording
    document.getElementById('start-record').addEventListener('click', async () => {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);

        mediaRecorder.ondataavailable = (event) => {
            audioChunks.push(event.data);
        };

        mediaRecorder.onstop = async () => {
            const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
            const audioUrl = URL.createObjectURL(audioBlob);
            document.getElementById('audio-playback').src = audioUrl;
            uploadAudio(audioBlob); // Call upload function
            audioChunks = []; // Reset chunks
        };

        mediaRecorder.start();
        document.getElementById('start-record').disabled = true;
        document.getElementById('stop-record').disabled = false;
    });

    // Stop Recording
    document.getElementById('stop-record').addEventListener('click', () => {
        mediaRecorder.stop();
        document.getElementById('start-record').disabled = false;
        document.getElementById('stop-record').disabled = true;
    });

    // Function to upload audio
    async function uploadAudio(audioBlob) {
        const formData = new FormData();
        formData.append('audio', audioBlob, 'recording.wav');

        try {
            const response = await fetch('/upload-audio', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                alert('Audio uploaded successfully!');
            }
        } catch (error) {
            console.error('Error uploading audio:', error);
        }
    }
</script>
<script>
    function handlePrint() {
        const printContent = document.getElementById('print_section').innerHTML;
        const styleSheet = `
            <link rel="stylesheet" href="{{ asset('admin') }}/assets/css/bootstrap.rtl.min.css">
            <style>
                body {
                    direction: rtl;
                    font-family: "Arial", sans-serif;
                    padding: 20px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                th, td {
                    border: 1px solid #000;
                    padding: 6px 8px;
                    text-align: center;
                    vertical-align: middle;
                    font-size: 12px;
                }
                h5 {
                    text-align: center;
                    margin-bottom: 20px;
                }
                @media print {
                    html, body {
                        width: 95%;
                        height: 100%;
                        margin: auto;
                        padding: 0;
                        overflow: visible;
                    }
                }
            </style>
        `;

        const printWindow = window.open('', '_blank');

        printWindow.document.open();
        printWindow.document.write(`
            <html>
                <head>
                    <title>{{ __("finance::finance.sales-analysis") }}</title>
                    ${styleSheet}
                </head>
                <body>
                    ${printContent}
                    <script>
                        window.onload = function() {
                            window.print();
                        };
                        window.onafterprint = function() {
                            window.close();
                        };
                    <\/script>
                </body>
            </html>
        `);
        printWindow.document.close();
    }
</script>
