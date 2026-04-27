<?php

namespace Nami\Finance\Traits;

use Nami\Finance\Models\ZatcaSetting;
use DOMDocument;
use Exception;
use stdClass;
use Symfony\Component\Process\Process;
use ZATCA\ZATCASimplifiedTaxInvoice;

trait ZatcaHelperTrait
{
    use ZatcaTrait;

    public bool $production = false;

    public function egs_info() // بيانات الشركة
    {
        $setting = ZatcaSetting::first();
        return [
            'uuid' => $setting->uuid,
            'custom_id' => $setting->custom_id,
            'production' => $setting->production,
            'model' => $setting->model,
            'CRN_number' => $setting->CRN_number,
            'VAT_name' => $setting->VAT_name,
            'VAT_number' => $setting->VAT_number,
            'location' => [
                'city' => $setting->city,
                'city_subdivision' => $setting->city_subdivision,
                'street' => $setting->street,
                'plot_identification' => $setting->plot_identification,
                'building' => $setting->building,
                'postal_zone' => $setting->postal_zone,
            ],
            'branch_name' => $setting->branch_name,
            'branch_industry' => $setting->branch_industry,
            'cancelation' => [
                'cancelation_type' => 'INVOICE',
                'canceled_invoice_number' => '',
            ],
        ];
    }


    public function invoice($data) // بيانات الفاتورة
    {

        $line_item = [
            'id' => $data->id,
            'name' => getEnumValue("bill_sales_types", $data->bill_sale_type),
            'quantity' => 1,
            'tax_exclusive_price' => 0,
            'VAT_percent' => 0,
            'other_taxes' => [
//                ['percent_amount' => 1]
            ],
            'discounts' => [
//                ['amount' => 2, 'reason' => 'A discount'],
            ],
        ];

        $invoice = [
            'invoice_counter_number' => $data->id,
            'invoice_serial_number' => "{$data->id}",
            'issue_date' => $data->date,
            'issue_time' => '00:00:00',
            'previous_invoice_hash' => 'NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ==', // AdditionalDocumentReference/PIH
            'line_items' => [
                $line_item,
            ],
        ];
        return $invoice;
    }

    public function generateNewKeysAndCSR(string $solution_name) // توليد الشهادة وتتم عن طريق خطوتين
    {
        $private_key = $this->generateSecp256k1KeyPair();
        return [$private_key, $this->generateCSR($solution_name, $private_key)];
    }

    private function generateSecp256k1KeyPair() // انشاء برايفت كي علشان اقدر اطلب توقيع شهادة
    {
        // Run the OpenSSL command to generate the private key
        $output = PHP_OS_FAMILY === 'Windows'
            ? shell_exec('openssl ecparam -name secp256k1 -genkey')
            : $this->runOpenSslProcess();

        // Extract the private key from the OpenSSL output
        return $this->extractPrivateKey($output);
    }

    private function runOpenSslProcess(): string
    {
        $process = Process::fromShellCommandline('openssl ecparam -name secp256k1 -genkey');
        $process->run();

        // Check if the process was successful
        if (!$process->isSuccessful()) {
            throw new Exception('Failed to generate private key: ' . $process->getErrorOutput());
        }

        return $process->getOutput();
    }

    private function extractPrivateKey(string $output): string
    {
        $result = explode('-----BEGIN EC PRIVATE KEY-----', $output);
        if (!isset($result[1])) {
            throw new Exception('No private key found in OpenSSL output.');
        }

        return "-----BEGIN EC PRIVATE KEY-----\n" . trim($result[1]);
    }

    private function generateCSR(string $solution_name, $private_key)
    {
        if (!$private_key) {
            throw new Exception('EGS has no private key');
        }

        // Ensure the temporary directory exists
        $tmp_dir = base_path('public/tmp');
        if (!is_dir($tmp_dir)) {
            mkdir($tmp_dir, 0775, true); // Recursive directory creation
        }

        // Generate unique file names for private key and CSR config
        $private_key_file_name = $tmp_dir . '/' . $this->uuid() . '.pem';
        $csr_config_file_name = $tmp_dir . '/' . $this->uuid() . '.cnf';

        // Write the private key and CSR configuration to temporary files
        file_put_contents($private_key_file_name, $private_key);
        file_put_contents($csr_config_file_name, $this->defaultCSRConfig($solution_name));

        // Generate the CSR using OpenSSL
        $csr = PHP_OS_FAMILY === 'Windows'
            ? $this->generateCSRWindows($private_key_file_name, $csr_config_file_name)
            : $this->generateCSRProcess($private_key_file_name, $csr_config_file_name);

        // Directory where CSR will be stored
        $csr_dir = base_path('public/csr');
        if (!is_dir($csr_dir)) {
            mkdir($csr_dir, 0775, true); // Recursive directory creation
        }

        // Generate unique file name for the CSR
        $csr_file_name = $csr_dir . '/' . $this->uuid() . '.csr';

        // Save the CSR to the file
        file_put_contents($csr_file_name, $csr);

        return $csr;
    }

    private function generateCSRWindows(string $private_key_file_name, string $csr_config_file_name): string
    {
        $output = shell_exec("openssl req -new -sha256 -key $private_key_file_name -config $csr_config_file_name 2>&1");
        return $this->extractCSR($output);
    }

    private function generateCSRProcess(string $private_key_file_name, string $csr_config_file_name): string
    {
        $process = Process::fromShellCommandline("openssl req -new -sha256 -key $private_key_file_name -config $csr_config_file_name");
        $process->run();

        // Check if the process was successful
        if (!$process->isSuccessful()) {
            throw new Exception('Failed to generate CSR: ' . $process->getErrorOutput());
        }

        return $this->extractCSR($process->getOutput());
    }

    private function extractCSR(string $output): string
    {
        $result = explode('-----BEGIN CERTIFICATE REQUEST-----', $output);
        if (!isset($result[1])) {
            throw new Exception('No CSR found in OpenSSL output.');
        }

        return "-----BEGIN CERTIFICATE REQUEST-----" . $result[1];
    }


    public static function uuid(): string // انشاء رقم فريد لكل عملية
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            random_int(0, 0xffff), random_int(0, 0xffff),

            // 16 bits for "time_mid"
            random_int(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            random_int(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            random_int(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
        );
    }

    private function defaultCSRConfig(string $solution_name)
    {
        $egs_unit = $this->egs_info();
        $config = [
            'egs_model' => $egs_unit['model'] ?? 'IOS',
            'egs_serial_number' => $egs_unit['uuid'] ?? $this->uuid(),
            'solution_name' => $solution_name,
            'vat_number' => $egs_unit['VAT_number'],
            'branch_location' => $egs_unit['location']['building'] . ' ' . $egs_unit['location']['street'] . ', ' . $egs_unit['location']['city'],
            'branch_industry' => $egs_unit['branch_industry'],
            'branch_name' => $egs_unit['branch_name'],
            'taxpayer_name' => $egs_unit['VAT_name'],
            'taxpayer_provided_id' => $egs_unit['custom_id'] ?? 'EGS1-886431145',
            'production' => $egs_unit['production']
        ];

        $template_csr = require base_path('public/ZATCA/templates/csr_template.php');;
        if ($config['production'] == 1){
            $sign = "ZATCA-Code-Signing";
        }
        else if ($config['production'] == 0){
            $sign = "TSTZATCA-Code-Signing";
        }
        else{
            $sign = "PREZATCA-Code-Signing";
        }
//        dd($sign);
        $template_csr = str_replace('SET_PRIVATE_KEY_PASS', ($config['private_key_pass'] ?? 'SET_PRIVATE_KEY_PASS'), $template_csr);
        $template_csr = str_replace('SET_PRODUCTION_VALUE', $sign, $template_csr);
        $template_csr = str_replace('SET_EGS_SERIAL_NUMBER', "1-{$config['solution_name']}|2-{$config['egs_model']}|3-{$config['egs_serial_number']}", $template_csr);
        $template_csr = str_replace('SET_VAT_REGISTRATION_NUMBER', $config['vat_number'], $template_csr);
        $template_csr = str_replace('SET_BRANCH_LOCATION', $config['branch_location'], $template_csr);
        $template_csr = str_replace('SET_BRANCH_INDUSTRY', $config['branch_industry'], $template_csr);
        $template_csr = str_replace('SET_COMMON_NAME', $config['taxpayer_provided_id'] ?? $config['vat_number'], $template_csr);
        $template_csr = str_replace('SET_BRANCH_NAME', $config['branch_name'], $template_csr);
        $template_csr = str_replace('SET_TAXPAYER_NAME', $config['taxpayer_name'], $template_csr);

        return $template_csr;
    }

    public function issueComplianceCertificate(string $otp, $csr) // اصدار شهادة التوقيع
    {
        if (!$csr) throw new Exception('EGS needs to generate a CSR first.');
        list($issueCertificate, $checkInvoiceCompliance) = $this->compliance();
        $issued_data = $issueCertificate($csr, $otp);
        return [$issued_data->requestID, $issued_data->binarySecurityToken, $issued_data->secret];
    }

    public function signInvoice(array $invoice, array $egs_unit, string $certificate, string $private_key) // توقيع الشهادة
    {
        require base_path('public/ZATCA/ZATCASimplifiedTaxInvoice.php');
        $zatca_simplified_tax_invoice = new ZATCASimplifiedTaxInvoice();
        $invoice_xml = $zatca_simplified_tax_invoice->simplifiedTaxInvoice($invoice, $egs_unit);
        $invoice_hash = $zatca_simplified_tax_invoice->getInvoiceHash($invoice_xml);
        list($hash, $issuer, $serialNumber, $public_key, $signature)
            = $zatca_simplified_tax_invoice->getCertificateInfo($certificate);
        $digital_signature = $zatca_simplified_tax_invoice->createInvoiceDigitalSignature($invoice_hash, $private_key);

        $qr = $zatca_simplified_tax_invoice->generateQR(
            $invoice_xml,
            $digital_signature,
            $public_key,
            $signature,
            $invoice_hash
        );

        $signed_properties_props = [
            'sign_timestamp' => date('Y-m-d\TH:i:s\Z'),
            'certificate_hash' => $hash, // SignedSignatureProperties/SigningCertificate/CertDigest/<ds:DigestValue>SET_CERTIFICATE_HASH</ds:DigestValue>
            'certificate_issuer' => $issuer,
            'certificate_serial_number' => $serialNumber
        ];
        $ubl_signature_signed_properties_xml_string_for_signing = $zatca_simplified_tax_invoice->defaultUBLExtensionsSignedPropertiesForSigning($signed_properties_props);
        $ubl_signature_signed_properties_xml_string = $zatca_simplified_tax_invoice->defaultUBLExtensionsSignedProperties($signed_properties_props);

        $signed_properties_hash = base64_encode(openssl_digest($ubl_signature_signed_properties_xml_string_for_signing, 'sha256'));
        // UBL Extensions



        $ubl_signature_xml_string = $zatca_simplified_tax_invoice->defaultUBLExtensions(
            $invoice_hash, // <ds:DigestValue>SET_INVOICE_HASH</ds:DigestValue>
            $signed_properties_hash, // SignatureInformation/Signature/SignedInfo/Reference/<ds:DigestValue>SET_SIGNED_PROPERTIES_HASH</ds:DigestValue>
            $digital_signature,
            $certificate,
            $ubl_signature_signed_properties_xml_string
        );

        // Set signing elements
        $unsigned_invoice_str = $invoice_xml->saveXML();

        $unsigned_invoice_str = str_replace('SET_UBL_EXTENSIONS_STRING', $ubl_signature_xml_string, $unsigned_invoice_str);
        $unsigned_invoice_str = str_replace('SET_QR_CODE_DATA', $qr, $unsigned_invoice_str);

        $signed_invoice = new DOMDocument();
        $signed_invoice->loadXML($unsigned_invoice_str);

        $signed_invoice_string = $signed_invoice->saveXML();
        //$signed_invoice_string = $zatca_simplified_tax_invoice->signedPropertiesIndentationFix($signed_invoice_string);

        // Define the directory and file path
        $directory_path = storage_path('invoices');
        $name = $invoice['invoice_serial_number'];
        $file_path = $directory_path . "/invoice{$name}.xml";

        // Create the directory if it doesn't exist
        if (!file_exists($directory_path)) {
            mkdir($directory_path, 0777, true);
        }

        // Save the signed invoice XML to a file
        file_put_contents($file_path, $signed_invoice_string);


        return [$signed_invoice_string, $invoice_hash, $qr,"/invoice{$name}.xml"];
    }

    public function checkInvoiceCompliance(string $signed_invoice_string, string $invoice_hash, string $certificate, string $secret): string
    {
        if (!$certificate || !$secret)
            throw new Exception('EGS is missing a certificate/private key/api secret to check the invoice compliance.');

        list($issueCertificate, $checkInvoiceCompliance) = $this->compliance($certificate, $secret);
        $issued_data = $checkInvoiceCompliance($signed_invoice_string, $invoice_hash, $this->egs_info()['uuid']);

        return json_encode($issued_data);
    }


}
