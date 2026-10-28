<?php

use App\Http\Controllers\McpController;
use Illuminate\Support\Facades\Route;

/*
 * خادم MCP — خارج مجموعة web عمداً: لا جلسة ولا CSRF ولا ترجمة للمخرجات.
 * الحماية: توكن mcp.auth + صلاحيات كل أداة. انظر docs/MCP_INTEGRATION.md.
 */
Route::middleware(['throttle:mcp', 'mcp.auth'])->group(function () {
    Route::post('mcp', [McpController::class, 'handle'])->name('mcp.handle');
    Route::match(['get', 'delete'], 'mcp', [McpController::class, 'methodNotAllowed'])->name('mcp.not-allowed');
});
