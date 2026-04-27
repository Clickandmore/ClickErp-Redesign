<?php

namespace Nami\Finance\Traits;

use Nami\Finance\Models\ZatcaSetting;
use Exception;
use stdClass;

trait ZatcaTrait
{

    public string $sandbox_url = 'https://gw-fatoora.zatca.gov.sa/e-invoicing/developer-portal';
    public string $version = 'V2';


    public function getZatcaUrl()
    {
        $setting = ZatcaSetting::firstOrCreate([]);
        if ($setting->production == 1) {
            $this->sandbox_url = 'https://gw-fatoora.zatca.gov.sa/e-invoicing/core';
        } elseif ($setting->production == 2) {
            $this->sandbox_url = 'https://gw-fatoora.zatca.gov.sa/e-invoicing/simulation';
        } else {
            $this->sandbox_url = 'https://gw-fatoora.zatca.gov.sa/e-invoicing/developer-portal';
        }
        return $this->sandbox_url;
    }

    private function getAuthHeaders($certificate, $secret): array
    {
        if ($certificate && $secret) {

            $certificate_stripped = $this->cleanUpCertificateString($certificate);
            $certificate_stripped = base64_encode($certificate_stripped);
            $basic = base64_encode($certificate_stripped . ':' . $secret);

            return [
                "Authorization: Basic $basic",
            ];
        }
        return [];
    }

    public function compliance($certificate = NULL, $secret = NULL)
    {
        $auth_headers = $this->getAuthHeaders($certificate, $secret);
        $issueCertificate = function (string $csr, string $otp): stdClass {
            $headers = [
                'Accept-Version: ' . $this->version,
                'OTP: ' . $otp,
                'Content-Type: application/json'
            ];
            $curl = curl_init($this->getZatcaUrl() . '/compliance');

            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode(['csr' => base64_encode($csr)]),
                CURLOPT_HTTPHEADER => $headers,
            ));
            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            $response = json_decode($response);
            if ($http_code != 200) throw new Exception('خطأ في إصدار الشهادة حاول مرة اخري.' . @$response->errors[0]->message ?? @$response->errors[0]);
            $issued_certificate = base64_decode($response->binarySecurityToken);
            $response->binarySecurityToken = "-----BEGIN CERTIFICATE-----\n{$issued_certificate}\n-----END CERTIFICATE-----";

            return $response;
        };

        $checkInvoiceCompliance = function (string $signed_invoice_string, string $invoice_hash, string $uuid) use ($auth_headers) {

            $headers = [
                'Accept-Version: ' . $this->version,
                'Accept-Language: en',
                'Content-Type: application/json',
            ];

            $curl = curl_init($this->getZatcaUrl() . '/compliance/invoices');

            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode([
                    'invoiceHash' => $invoice_hash,
                    'uuid' => $uuid,
                    'invoice' => base64_encode($signed_invoice_string),
                ]),
                CURLOPT_HTTPHEADER => [...$headers, ...$auth_headers],
            ));

            $response = curl_exec($curl);
            dd(json_decode($response));
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            $response = json_decode($response);


            if ($http_code != 200) return $response;
            return $response;
        };

        return [$issueCertificate, $checkInvoiceCompliance];
    }

    public static function cleanUpCertificateString(string $certificate): string
    {
        $certificate = str_replace('-----BEGIN CERTIFICATE-----', '', $certificate);
        $certificate = str_replace('-----END CERTIFICATE-----', '', $certificate);

        return trim($certificate);
    }
}
