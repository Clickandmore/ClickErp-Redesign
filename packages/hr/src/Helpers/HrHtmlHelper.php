<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Nami\Hr\Helpers\HrConstantHelper;

if (!function_exists('getHrEnumValue')) {
    function getHrEnumValue($array_name, $key)
    {
        $constantArray = HrConstantHelper::globalArray[$array_name];
        $locale = App::currentLocale();
        return (isset($constantArray[$key][$locale])) ? $constantArray[$key][$locale] : "undefined";
    }
}
if (!function_exists('updateSuccessEmployeeStatusButton')) {
    function updateSuccessEmployeeStatusButton($table, $id, $text)
    {
        return '<button class="btn btn-success btn-sm accept-button1" data-id="' . $id . '" data-table="' . $table . '">' . $text . '</button>';
    }
}
if (!function_exists('updateDangerEmployeeStatusButton')) {
    function updateDangerEmployeeStatusButton($table, $id, $text)
    {
        return '<button class="btn btn-danger btn-sm reject-button1" data-id="' . $id . '" data-table="' . $table . '">' . $text . '</button>';
    }
}


if (!function_exists('leaveButton')) {
    function leaveButton($route, $text = "")
    {
        $fullText = 'تسجيل الانصراف '. ' ' . $text;
        return '<button type="button" class="btn btn-outline-secondary editButton" data-bs-toggle="modal" data-bs-target="#createModal"
                       model-route="' . $route . '" model-title="' . $fullText . '">
                    تسجيل الانصراف
                </button>';
    }
}
if (!function_exists('addEmplyeeButton')) {
    function addEmplyeeButton($route, $text = "")
    {
        $fullText = ($text ===false) ? "" : __("buttons.add button") . ' ' . $text;
//        if (in_array(getCurrentRouteName() . '-create', getUserPermissions()) || auth('admin')->user()->admin_type == \App\Enums\AdminTypeisEnum::Developer->value) {
            return '
                <div class="d-flex mb-3 justify-content-end">
                    <a type="button" class="btn btn-success float-start" href="' . $route . '" >
                        <i class="fa fa-plus-circle"></i> ' . $fullText . '
                    </a>
                </div>
                ';
//        }
//        return '';
    }
}
if (!function_exists('getUserPermissions')) {
    function getUserPermissions()
    {
        if(auth('admin')->user()->roles->first() != null &&
            auth('admin')->user()->roles->first()->permissions->count() > 0){

            return auth('admin')->user()->roles->first()->permissions->pluck('name')->toArray();
        }
        return [];
    }
}
if (!function_exists('getCurrentRouteName')) {
    function getCurrentRouteName()
    {
        $currentRouteName = Route::currentRouteName();
        $segments = explode('.', $currentRouteName);
        // Remove the last segment
        array_pop($segments);
        // Rejoin the segments to form the base route name
        $baseRouteName = implode('.', $segments);
//        $baseRouteName = explode('.', $currentRouteName)[0];
        return $baseRouteName;
    }
}
if (!function_exists('updateStatusButton')) {
    function updateStatusButton($table,$status, $id,$text = null): string
    {
        $class = $status ? 'btn-success' : 'btn-secondary';
        if($text == null) {
            $text = $status ? 'نشط' : 'غير نشط';
        }
        return '<button class="btn ' . $class . ' btn-sm toggle-status" data-id="' . $id . '" data-table="' . $table . '">' . $text . '</button>';
    }
}

if (!function_exists('addUpButton')) {
    function addUpButton($route, $item_id = "", $text = "", $item_name_id = "", $notup_popup = false): string
    {
        $fullText = ($text === false) ? "" : __("buttons.add button") . ' ' . $text;

        return ' <i class="fa fa-plus-circle addUpButton fastCreate" data-bs-toggle="modal" data-bs-target="#upCreateModal"
                          item-id="' . $item_id . '" item-name-id="' . $item_name_id . '" item-popup-id="' . $notup_popup . '"
                          model-title="' . $fullText . '"  model-route="' . $route . '"
                          title="' . __("buttons.add button") . '" ></i> ';
    }
}
function showUserImage($fileFullPath)
{
    if ($fileFullPath && file_exists(public_path('/storage/' . $fileFullPath))) {
        return asset('/storage/' . $fileFullPath);
    }

    return asset(asset('/default/default-arab.png'));
}
if (!function_exists('customButton')) {
    function customButton($route,  $text = "", $color = 'primary', $icon = 'pencil', $type = 'addButton', $count = null)
    {
//        if(checkIfHasPermission(getRouteNameFromRoute($route) . '-create')){
            $count = $count == 0 || is_null($count) ? '' : '<span class="bg-danger text-white rounded px-1">' . $count . '</span>';
            return '<button type="button" class="btn btn-' . $color . ' ' . $type . '" data-bs-toggle="modal" data-bs-target="#createModal"
                        model-route="' . $route . '" model-title="' . $text . '">
                        <i class="fa fa-' . $icon . '"></i> '. $count .'
                    </button>';
//        }
//        return '';
    }
}
