<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ExternalSiteCacheSettings;

/**
 * إعدادات الكاش التي تقرؤها المواقع الخارجية كل دقيقة تقريبًا.
 * طلب خفيف عمدًا: قراءة صف واحد من settings بلا أي علاقات.
 */
class ApiSiteCacheSettingsController extends Controller
{
    public function show()
    {
        return apiResponse(true, 'site cache settings', ExternalSiteCacheSettings::apiPayload());
    }
}
