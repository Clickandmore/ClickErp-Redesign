<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

if(!function_exists('printButton')){
    function printButton($route ,$text = "", $permissions = null): string
    {
        $perm = $permissions ?? getCurrentRouteName($route) . '-read';
        if (in_array( $perm, getUserPermissions()) || auth('admin')->user()->admin_type == \App\Enums\AdminTypeisEnum::Developer->value) {
            return "<a class='btn btn-success' target='_blank' id='print_button' href=" . $route ." class='mr-2'><i class='fa fa-print'></i>" . $text . "</a>";
        }
        return '';
    }
}

if(!function_exists('returnButton')){
    function returnButton($route ,$text = "", $permissions = null): string
    {
        $perm = $permissions ?? getCurrentRouteName($route) . '-read';
        if (in_array( $perm, getUserPermissions()) || auth('admin')->user()->admin_type == \App\Enums\AdminTypeisEnum::Developer->value) {
            return "<a class='btn btn-danger' target='_blank' id='print_button' href=" . $route ." class='mr-2'><i class='fa fa-sync'></i></a>";
        }
        return '';
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
    function getCurrentRouteName(): string
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

if (!function_exists('confirmButton')) {
    function confirmButton($route, $id): string
    {
        $routeName = getRouteNameFromUrl($route);
        //  if (checkPermissionOffAdmin($routeName)) {
        return '<button type="button" class="btn btn-warning confirmButton"  confirm-id="' . $id . '" confirm-route="' . $route . '">
                      <i class="fa fa-check-square-o"></i>
                </button>';
        //}
        // return "-";
    }
}

if (!function_exists('getMonthName')) {

    function getMonthName($monthNumber): \Illuminate\Foundation\Application|array|string|\Illuminate\Contracts\Translation\Translator|\Illuminate\Contracts\Foundation\Application|null
    {
        // Get the current locale
        $locale = App::getLocale();

        // Load month names from the language file based on the locale
        $months = trans('months.' . $monthNumber);

        // Return the month name for the given month number
        return $months; // Return month number if not found
    }
}

if (!function_exists('addHyperButton')) {
    function addHyperButton($route, $text = "")
    {
        $fullText = ($text ===false) ? "" : __("buttons.add button") . ' ' . $text;
        return '
                <div class="d-flex mb-3 justify-content-end">
                    <a href="'.$route.'" class="btn btn-success float-start mx-1">
                       <i class="fa fa-plus-circle"></i>  ' . $fullText . '
                    </a>
                </div>
                ';
    }
}

if (!function_exists('addButtonInTable')) {
    function addButtonInTable($route, $text = "")
    {
        $fullText = ($text ===false) ? "" : __("buttons.add button") . ' ' . $text;
        return '

                    <button type="button" class="btn btn-info addButton mx-1" data-bs-toggle="modal" data-bs-target="#createModal"
                           model-title="' . $fullText . '"  model-route="' . $route . '" title="' . __("buttons.add button") . '" >
                       <i class="fa fa-plus-circle"></i>  ' . $fullText . '
                    </button>

                ';
    }
}

if (!function_exists('confirmBtn')) {
    function confirmBtn($route)
    {
        if (in_array(getCurrentRouteName() . '-update', getUserPermissions()) || auth('admin')->user()->admin_type == \App\Enums\AdminTypeisEnum::Developer->value) {
            return '<button type="button" class="btn btn-primary confirmBtn" title="تأكيد الفاتورة" confirm-route="' . $route . '">
                      <i class="fa fa-check"></i>
                    </button>';
        }
        return '';
    }
}

if (!function_exists('downloadFileBtn')) {
    function downloadFileBtn($filePath, $fileName = null)
    {
        // Use the file name from the parameter, or extract the file name from the file path if not provided
        $fileName = $fileName ?? basename($filePath);

        // Generate the download button HTML
        return '<a href="' . $filePath . '" class="btn btn-outline-primary" download="' . $fileName . '" title="' . __("zatca.Download XML") . '">
                   <i class="fa fa-download"></i> ' . __("zatca.Download XML") . '
                </a>';
    }
}

if (!function_exists('editHyperButton')) {
    function editHyperButton($route, $text = "",$font_icon="pencil",$show=false)
    {
        $show ?  $fullText = __("buttons.show") . ' ' . $text :  $fullText = __("buttons.edit") . ' ' . $text;
        $color = $show ?  "info"  : "secondary";
        return '<a href='.$route.'  class="btn btn-outline-' . $color . '">
                   <i class="fa fa-'.$font_icon.'"></i>
                </a>';
    }
}

if (!function_exists('resendButton')) {
    function resendButton($route, $text = "",$font_icon="pencil",$show=false)
    {
        return '<button type="button" class="btn btn-outline-secondary editButton" model-route="' . $route . '">
                <i class="fa fa-repeat"></i>
        </button>';
    }
}

if (!function_exists('viewButton')) {
    function viewButton($route, $text = "",$font_icon="pencil",$name ="",$model_name="",$target="_blank",$title = "")
    {
        $model_name = $model_name ?? __("buttons.edit");
        $fullText = $model_name . ' ' . $text;
        return '<a  class="editButtonStyle "
                       title="'.$title.'"
                       href="' . $route . '"  target="'.$target.'">
                     <i class="fa fa-'.$font_icon.'"></i> '.$name.'
                </a>';
    }
}

/**
 * @param $route
 * @param $text
 * @return string
 */
if (!function_exists('editButtonLink')) {
    function editButtonLink($route, $text = "")
    {
        $fullText = __("buttons.edit") . ' ' . $text;
        return '<a href="' . $route . '">
                    <button type="button" class="btn btn-primary">
                       <i class="fa fa-pencil"></i>
                    </button>
                </a>';
    }
}
if (!function_exists('submitNewButton')) {
    function submitNewButton($route = "" ,$text = "" , $class = "success")
    {
        $fullText = ($text != "")? $text :__("buttons.save_new_add");
        return '<button class="btn btn-'.$class. ' mx-2" type="submit" id="save_new_add">'.$fullText.'</button>';
    }
}
if (!function_exists('deleteActionButton')) {
    function deleteActionButton($route, $icon = "trash-o",$class='outline-danger')
    {
        return '<button type="button" class="btn btn-'.$class.' deleteActionButton" delete-route="' . $route . '">
                      <i class="fa fa-'.$icon.'"></i>
                </button>';
    }
}

