<?php

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Languages;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Validation\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Exceptions\HttpResponseException;

function successResponse($message = null, $data = null, $status = 200, array $headers = [])
{
    $response = [
        // 'success' => true,
        'message' => $message,
        'data' => $data,
    ];

    return new JsonResponse($response, $status, $headers);
}

function errorResponse($message = null, $status = Response::HTTP_BAD_REQUEST, array $headers = [])
{
    return new JsonResponse($message ?? __('api.error'), $status, $headers);
}

function throwResponseExceptionJson(Validator $validator)
{
    throw new HttpResponseException(response()->json([
        'success' => false,
        'message' => 'Validation errors',
        'data' => null,
        'errors' => $validator->errors(),
    ]));
}

function removeSpaces($string)
{
    return str_replace(' ', '', $string);
}
function translationInputs($name, $model = null, $title = "", $type = 'input', $error = true)
{
    $html = "";

    if ($languages = Languages::getAvailableLanguages()) {
        $html .= '<div class="row mb-3">';

        foreach ($languages as $k => $language) {
            $inputId = "name-{$name}{$k}";
            $inputName = "{$name}[{$language}]";
            $inputValue = ($model ? $model->getTranslation($name, $language) : '');

            $html .= '<div class="col-md-4">';
            $html .= '<label for="' . $inputId . '" class="active my-2">' . $title . ' [ ' . __('admin.language') . ' : ' . $language . ' ]</label>';
            $html .= '<div class="mb-2">';

            if ($type == 'textarea') {
                $html .= '<textarea id="' . $inputId . '" name="' . $inputName . '" type="text" class="form-control cu-form-design" placeholder="' . $title . '">' . $inputValue . '</textarea>';
            } elseif ($type == 'file') {
                if ($model) {
                    $slidersSrc = url(App\Constants::PUBLIC_SLIDERS_DIR . $inputValue);
                    $html .= '<img class="mb-2" src="' . $slidersSrc . '" height="80" >';
                }
                $html .= '<input id="' . $inputId . '" name="' . $inputName . '" type="file" class="form-control cu-form-design" placeholder="' . $title . '" value="' . $inputValue . '">';
            } else {
                $html .= '<input id="' . $inputId . '" name="' . $inputName . '" type="text" class="form-control cu-form-design" placeholder="' . $title . '" value="' . $inputValue . '">';
            }

            $html .= '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';
    }

    return $html;
}

function RemoveSpacesSpecialCharNumbers($text)
{
    $newText = $text;
    try {
        $slug = str_replace(' ', '', $newText); // Replaces all spaces with hyphens.
        $slug = preg_replace('/[^A-Za-z0-9\-]/', '', $slug);
        $slug = preg_replace('/[0-9]+/', '', $slug);
        $newText = $slug;
    } catch (\Exception $e) {
        $newText = $text;
    }
    return $newText;
}


function CheckIfColumnIsUniqueInGivenModel($model, $column_name, $column_value, $model_primary_id_name, $primary_model_id_value = 0)
{
    $checkSlugUnique = $model::where($column_name, $column_value);
    if ($primary_model_id_value != 0) {
        $checkSlugUnique = $checkSlugUnique->where($model_primary_id_name, '!=', $primary_model_id_value);
    }
    $checkSlugUnique = $checkSlugUnique->first();
    if ($checkSlugUnique) {
        return true;
    }
    return false;
}

function generateRandomNumber($length)
{
    $min = pow(10, $length - 1); // Minimum value based on length
    $max = pow(10, $length) - 1; // Maximum value based on length
    return mt_rand($min, $max);
}

function checkIfUrlIsCurrent($url, $parameter = null)
{
    if (request()->routeIs($url) || request()->routeIs($url . '*')) {
        if ($parameter != null && request()->query('role') == $parameter) {
            return true;
        }
        if ($parameter == null) {
            return true;
        }
    }
    return false;
}

function checkIfUrlIsCurrentVersion2($url, $parameters = [])
{
    $routeIsCurrent = request()->routeIs($url) || request()->routeIs($url . '*');
    // Check if 'mainRoute' parameter is present
    if (request()->query('mainRoute') !== null) { // this is for routes with a lot of params but u also want a page for everything
        return false;
    }
    if (empty($parameters)) {
        return $routeIsCurrent;
    }
    if ($routeIsCurrent) {
        foreach ($parameters as $param => $value) {
            if (request()->query($param) != $value) {
                return false;
            }
        }
        return true;
    }
    return false;
}




function calculateDistance($lat1, $lng1, $lat2, $lng2)
{
    $apiKey = getenv('GOOGLE_API'); // Replace with your Google Maps API key

    $origin = "{$lat1},{$lng1}";
    $destination = "{$lat2},{$lng2}";

    $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins={$origin}&destinations={$destination}&units=metric&key={$apiKey}";

    $response = file_get_contents($url);
    $data = json_decode($response);

    if ($data->status == "OK" && isset($data->rows[0]->elements[0]->distance->value)) {
        $distance = $data->rows[0]->elements[0]->distance->value / 1000; // Convert meters to kilometers
        return $distance;
    } else {
        return null;
    }
}

function staticCalculateDistanceWithoutApi($lat1, $lng1, $lat2, $lng2)
{
    // Radius of the Earth in kilometers
    $earthRadius = 6371;

    // Convert latitude and longitude from degrees to radians
    $lat1 = deg2rad($lat1);
    $lng1 = deg2rad($lng1);
    $lat2 = deg2rad($lat2);
    $lng2 = deg2rad($lng2);

    // Haversine formula
    $dLat = $lat2 - $lat1;
    $dLng = $lng2 - $lng1;

    $a = sin($dLat / 2) * sin($dLat / 2) + cos($lat1) * cos($lat2) * sin($dLng / 2) * sin($dLng / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    // Calculate the distance
    $distance = $earthRadius * $c;

    return $distance;
}


function generateCode($length)
{
    $code = '';
    $characters = '0123456789';

    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }

    return $code;
}


function sendSmsOtp($number, $otp)
{
    if (config('app.env') == 'local')
        return true;
    $message = "$otp is your one-time password (OTP) for phone verification. - Tareen";

    $userName = config('app.sms_username', 'default_username');
    $apiKey = config('app.sms_password', 'default_api_key');
    $userSender = config('app.sms_sender', 'default_sender');

    $url = 'https://www.msegat.com/gw/sendsms.php';
    $headers = ['Content-Type' => 'application/json'];
    $body = [
        'userName' => $userName,
        'numbers' => $number,
        'userSender' => $userSender,
        'apiKey' => $apiKey,
        'msg' => $message,
    ];

    $response = Http::withHeaders($headers)->post($url, $body);

    if ($response->successful()) {
        Log::info("Response: {$response->body()}");
        return true;
    } else {
        Log::error("Request failed with status code: {$response->status()}");
        Log::error("Response: {$response->body()}");
        return false;
    }
}

function convertArabicToEnglishNumbers($arabicNumber)
{
    $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    return str_replace($arabic, $english, $arabicNumber);
}

function AdminGuardHasPerm($perm_name)
{
    return Auth::guard('admin')->user()->can($perm_name) ?? false;
}

function contractBackStatus($status)
{
    switch ($status) {
        case ContractStatusEnum::Tazker->value:
            return ContractStatusEnum::Taffez;
        case ContractStatusEnum::Taffez->value:
            return ContractStatusEnum::Tafwed;
        case ContractStatusEnum::Tafwed->value:
            return ContractStatusEnum::Msaned;
        case ContractStatusEnum::Msaned->value:
            return ContractStatusEnum::New;
        default:
            return ContractStatusEnum::New;
    }
}

function contractStatusConfirmBtnText($status)
{
    switch ($status) {
        case ContractStatusEnum::New ->value:
            return __('admin.contract_btn_new');
        case ContractStatusEnum::Msaned->value:
            return __('admin.contract_btn_msaned');
        case ContractStatusEnum::Tafwed->value:
            return __('admin.contract_btn_tafwed');
        case ContractStatusEnum::Taffez->value:
            return __('admin.contract_btn_taffez');
        case ContractStatusEnum::Tazker->value:
            return __('admin.contract_btn_tazker');
        default:
            return __('admin.update_status');
    }
}

