<?php

use App\Http\Controllers\McpController;
use App\Http\Controllers\McpOAuthController;
use Illuminate\Support\Facades\Route;

/*
 * خادم MCP — خارج مجموعة web عمداً: لا جلسة ولا CSRF ولا ترجمة للمخرجات.
 * الحماية: توكن mcp.auth + صلاحيات كل أداة. انظر docs/MCP_INTEGRATION.md.
 */
Route::middleware(['throttle:mcp', 'mcp.auth'])->group(function () {
    Route::post('mcp', [McpController::class, 'handle'])->name('mcp.handle');
    Route::match(['get', 'delete'], 'mcp', [McpController::class, 'methodNotAllowed'])->name('mcp.not-allowed');
});

/*
 * OAuth لربط claude.ai وChatGPT وClaude Code. الاكتشاف والتسجيل والتوكن تستدعيها
 * خوادم تلك البرامج مباشرة (بلا جلسة)، أما شاشة الموافقة فتحتاج جلسة الموظف.
 */
Route::middleware('throttle:mcp-oauth')->group(function () {
    Route::get('.well-known/oauth-protected-resource', [McpOAuthController::class, 'protectedResource'])->name('mcp.oauth.resource-metadata');
    Route::get('.well-known/oauth-protected-resource/mcp', [McpOAuthController::class, 'protectedResource']);
    Route::get('.well-known/oauth-authorization-server', [McpOAuthController::class, 'authorizationServer'])->name('mcp.oauth.server-metadata');
    Route::post('oauth/register', [McpOAuthController::class, 'register'])->name('mcp.oauth.register');
    Route::post('oauth/token', [McpOAuthController::class, 'token'])->name('mcp.oauth.token');
});

Route::middleware(['web', 'throttle:mcp-oauth'])->group(function () {
    Route::get('oauth/authorize', [McpOAuthController::class, 'authorizeForm'])->name('mcp.oauth.authorize');
    Route::post('oauth/authorize', [McpOAuthController::class, 'authorizeDecision'])->name('mcp.oauth.decision');
});
