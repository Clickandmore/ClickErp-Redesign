<?php

namespace Nami\Finance\Services;

use Nami\Finance\Models\ZatcaInvoice;
use App\Services\MainService;
use Nami\Finance\Services\ZatcaSettingService;
use Nami\Finance\Models\Sale;
use Nami\Finance\Traits\ZatcaHelperTrait;
use Nami\Finance\Traits\ZatcaInvoicesTrait;
use DOMDocument;
use DB;
use Salla\ZATCA\Helpers\Certificate;

class ShareInvoiceZatcaService extends MainService
{
    use ZatcaInvoicesTrait;

    function __construct(Sale $model, protected ZatcaSettingService $zatcaSettingService)
    {
        $this->model = $model;
    }


    public function getLastHash()
    {
        // Query to get the last invoice record with a hash
        $lastInvoice = ZatcaInvoice::latest('id')->first();

        // If no invoice is found, return base64-encoded "0" for the first invoice PIH
        if (!$lastInvoice || !$lastInvoice->invoice_hash) {
            return base64_encode('0');
        }

        // Return the previous invoice's hash if found
        return $lastInvoice->invoice_hash;
    }


    public function shareInvoice($invoice_id)
    {
        $invoice = $this->find($invoice_id);

        // Set temporary attributes (do not save them to the database)
        $invoice->setAttribute('is_B2B', (bool) $invoice->client->is_company);
        $invoice->setAttribute('is_invoice', true);

        $customer = $invoice->client;
        $items = $invoice->items;

        $resultOfShare = $this->sendToZatca($invoice, $customer, $items);
        $resultArray = json_decode($resultOfShare['result'], true);

        $status = $resultArray['reportingStatus'] ?? $resultArray['clearanceStatus'];

        // Use updateOrCreate to handle both update and create in a single call
        $zatcaInvoice = ZatcaInvoice::updateOrCreate(
            ['sale_id' => $invoice->id],
            [
                'invoice_hash'            => $resultOfShare['invoice_hash'] ?? null,
                'invoice_zatca_category'  => $resultOfShare['invoice_zatca_category'] ?? null,
                'invoice_zatca_type_code' => $resultOfShare['invoice_zatca_type_code'] ?? null,
                'zatca_response'          => json_encode($resultArray) ?? null,
                'qr'                      => $resultOfShare['qr'] ?? null,
                'xml_file'                => $resultOfShare['file_path'] ?? null,
                'zatca_status'            => $status,
                'uuid'                    => $resultOfShare['uuid'] ?? null,
                'pih'                     => $this->getLastHash() ?? null,
            ]
        );

        // Remove temporary attributes before updating
        $invoice->unsetRelation('client');
        $invoice->unsetRelation('items');
        unset($invoice->is_B2B, $invoice->is_invoice);

        $invoice->update(['zatca_invoice_id' => $zatcaInvoice->id]);

        return $zatcaInvoice;
    }
}
