<?php

namespace App\Http\Controllers;

use App\Mcp\McpAccess;
use App\Models\auth\Admin;
use App\Models\McpAccessToken;
use App\Models\McpAuditLog;
use App\Models\McpOAuthClient;
use App\Models\McpOAuthCode;
use App\Support\McpSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * خادم تفويض OAuth 2.1 لخادم MCP، كما يطلبه claude.ai وChatGPT وClaude Code:
 * اكتشاف (RFC 9728 / RFC 8414)، تسجيل عميل ديناميكي (RFC 7591)، وتفويض بـ PKCE (S256).
 *
 * الموظف يسجّل دخوله بحسابه في لوحة التحكم ويوافق؛ التوكن الناتج مربوط به،
 * فتسري صلاحياته وعزل فرعه على الأدوات كما في التوكن اليدوي تماماً.
 */
class McpOAuthController extends Controller
{
    public const SCOPE = 'mcp';

    public const RETURN_SESSION_KEY = 'mcp_oauth_return';

    // ---------------------------------------------------------------- اكتشاف

    public function protectedResource(): JsonResponse
    {
        $this->ensureEnabled();

        return response()->json([
            'resource' => $this->resource(),
            'authorization_servers' => [$this->issuer()],
            'bearer_methods_supported' => ['header'],
            'scopes_supported' => [self::SCOPE],
            'resource_name' => 'ClickERP',
        ]);
    }

    public function authorizationServer(): JsonResponse
    {
        $this->ensureEnabled();

        return response()->json([
            'issuer' => $this->issuer(),
            'authorization_endpoint' => self::absoluteUrl('/oauth/authorize'),
            'token_endpoint' => self::absoluteUrl('/oauth/token'),
            'registration_endpoint' => self::absoluteUrl('/oauth/register'),
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'code_challenge_methods_supported' => ['S256'],
            'token_endpoint_auth_methods_supported' => ['none', 'client_secret_post', 'client_secret_basic'],
            'scopes_supported' => [self::SCOPE],
        ]);
    }

    // ---------------------------------------------------------------- تسجيل العميل

    public function register(Request $request): JsonResponse
    {
        $this->ensureEnabled();

        $redirectUris = $request->input('redirect_uris');
        if (!is_array($redirectUris) || $redirectUris === [] || count($redirectUris) > 10) {
            return $this->oauthError('invalid_redirect_uri', 'redirect_uris مطلوبة (1 إلى 10 روابط).');
        }

        foreach ($redirectUris as $uri) {
            if (!is_string($uri) || !$this->redirectAllowed($uri)) {
                return $this->oauthError('invalid_redirect_uri', 'رابط الرجوع غير مسموح: ' . Str::limit((string) $uri, 200));
            }
        }

        $method = (string) $request->input('token_endpoint_auth_method', 'client_secret_basic');
        if (!in_array($method, ['none', 'client_secret_post', 'client_secret_basic'], true)) {
            return $this->oauthError('invalid_client_metadata', 'token_endpoint_auth_method غير مدعوم.');
        }

        $grantTypes = (array) $request->input('grant_types', ['authorization_code', 'refresh_token']);
        if (array_diff($grantTypes, ['authorization_code', 'refresh_token']) !== []) {
            return $this->oauthError('invalid_client_metadata', 'grant_types غير مدعومة.');
        }

        $responseTypes = (array) $request->input('response_types', ['code']);
        if (array_diff($responseTypes, ['code']) !== []) {
            return $this->oauthError('invalid_client_metadata', 'response_types غير مدعومة.');
        }

        $secret = $method === 'none' ? null : Str::random(48);
        $client = McpOAuthClient::query()->create([
            'client_id' => 'cerp_client_' . Str::random(32),
            'client_secret_hash' => $secret ? hash('sha256', $secret) : null,
            'client_name' => Str::limit(strip_tags((string) $request->input('client_name', '')), 180, '') ?: null,
            'redirect_uris' => array_values($redirectUris),
            'token_endpoint_auth_method' => $method,
            'registration_ip' => $request->ip(),
        ]);

        return response()->json(array_filter([
            'client_id' => $client->client_id,
            'client_secret' => $secret,
            'client_id_issued_at' => $client->created_at->getTimestamp(),
            'client_secret_expires_at' => $secret ? 0 : null,
            'client_name' => $client->client_name,
            'redirect_uris' => $client->redirect_uris,
            'token_endpoint_auth_method' => $method,
            'grant_types' => array_values($grantTypes),
            'response_types' => ['code'],
        ], fn ($value) => $value !== null), 201)->header('Cache-Control', 'no-store');
    }

    // ---------------------------------------------------------------- التفويض

    public function authorizeForm(Request $request)
    {
        $this->ensureEnabled();

        [$client, $error] = $this->validateAuthorizationRequest($request);
        if ($error) {
            return $error;
        }

        $admin = $this->currentAdmin();
        if (!$admin) {
            $request->session()->put(self::RETURN_SESSION_KEY, $request->fullUrl());

            return redirect()->route('admin.form.login');
        }

        if (!\App\Support\McpSubscription::allowsRead()) {
            return response()->view('admin.mcp.oauth-error', ['message' => \App\Support\McpSubscription::message()], 403);
        }

        if (!app(McpAccess::class)->adminAllowed($admin)) {
            return $this->notEnabledView($admin);
        }

        $access = app(McpAccess::class)->recordFor($admin);

        return view('admin.mcp.oauth-consent', [
            'canWrite' => McpSettings::writeEnabled() && \App\Support\McpSubscription::allowsWrite() && $access?->canWrite(),
            'client' => $client,
            'admin' => $admin,
            'redirectHost' => parse_url($request->query('redirect_uri'), PHP_URL_HOST),
            'params' => $request->only([
                'response_type', 'client_id', 'redirect_uri', 'code_challenge',
                'code_challenge_method', 'state', 'scope', 'resource',
            ]),
        ]);
    }

    public function authorizeDecision(Request $request)
    {
        $this->ensureEnabled();

        [$client, $error] = $this->validateAuthorizationRequest($request);
        if ($error) {
            return $error;
        }

        $admin = $this->currentAdmin();
        if (!$admin) {
            return redirect()->route('admin.form.login');
        }

        if (!\App\Support\McpSubscription::allowsRead()) {
            return response()->view('admin.mcp.oauth-error', ['message' => \App\Support\McpSubscription::message()], 403);
        }

        if (!app(McpAccess::class)->adminAllowed($admin)) {
            return $this->notEnabledView($admin);
        }

        $redirectUri = (string) $request->input('redirect_uri');

        if ($request->input('decision') !== 'approve') {
            $this->audit($admin, 'oauth/authorize', $client, 'denied');

            return $this->redirectWithParams($redirectUri, ['error' => 'access_denied', 'state' => $request->input('state')]);
        }

        $code = Str::random(64);
        McpOAuthCode::query()->create([
            'code_hash' => hash('sha256', $code),
            'client_id' => $client->id,
            'admin_id' => $admin->id,
            'redirect_uri' => $redirectUri,
            'code_challenge' => (string) $request->input('code_challenge'),
            'scope' => self::SCOPE,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
        ]);

        $this->audit($admin, 'oauth/authorize', $client, 'ok');

        return $this->redirectWithParams($redirectUri, ['code' => $code, 'state' => $request->input('state')]);
    }

    // ---------------------------------------------------------------- التوكن

    public function token(Request $request): JsonResponse
    {
        $this->ensureEnabled();

        [$clientId, $clientSecret] = $this->clientCredentials($request);
        $client = $clientId
            ? McpOAuthClient::query()->where('client_id', $clientId)->whereNull('revoked_at')->first()
            : null;

        if (!$client || (!$client->isPublic() && !$client->verifySecret($clientSecret))) {
            return $this->oauthError('invalid_client', 'بيانات العميل غير صحيحة.', 401);
        }

        return match ($request->input('grant_type')) {
            'authorization_code' => $this->exchangeCode($request, $client),
            'refresh_token' => $this->refresh($request, $client),
            default => $this->oauthError('unsupported_grant_type', 'grant_type غير مدعوم.'),
        };
    }

    private function exchangeCode(Request $request, McpOAuthClient $client): JsonResponse
    {
        $code = McpOAuthCode::query()
            ->where('code_hash', hash('sha256', (string) $request->input('code')))
            ->where('client_id', $client->id)
            ->first();

        if (!$code || $code->used_at || $code->expires_at->isPast()
            || $code->redirect_uri !== (string) $request->input('redirect_uri')) {
            return $this->oauthError('invalid_grant', 'رمز التفويض غير صالح أو منتهي.');
        }

        $verifier = (string) $request->input('code_verifier');
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        if (strlen($verifier) < 43 || !hash_equals($code->code_challenge, $challenge)) {
            return $this->oauthError('invalid_grant', 'code_verifier غير مطابق.');
        }

        // استخدام واحد: التحديث المشروط يمنع استبدال الرمز مرتين في طلبين متزامنين.
        $claimed = McpOAuthCode::query()->whereKey($code->id)->whereNull('used_at')->update(['used_at' => now()]);
        if ($claimed !== 1) {
            return $this->oauthError('invalid_grant', 'رمز التفويض مستخدم.');
        }

        $admin = Admin::query()->find($code->admin_id);
        if (!$admin || ($admin->status ?? 'active') === 'suspended' || !app(McpAccess::class)->adminAllowed($admin)) {
            return $this->oauthError('invalid_grant', 'حساب الموظف غير متاح.');
        }

        return $this->issueTokens($admin, $client);
    }

    private function refresh(Request $request, McpOAuthClient $client): JsonResponse
    {
        $old = McpAccessToken::findByRefreshToken((string) $request->input('refresh_token'));

        if (!$old || (int) $old->oauth_client_id !== (int) $client->id) {
            return $this->oauthError('invalid_grant', 'refresh_token غير صالح.');
        }

        $admin = $old->admin;
        if (!$admin || ($admin->status ?? 'active') === 'suspended' || !app(McpAccess::class)->adminAllowed($admin)) {
            return $this->oauthError('invalid_grant', 'حساب الموظف غير متاح.');
        }

        // تدوير: الـ refresh token يُستخدم مرة واحدة ثم يُلغى مع التوكن القديم.
        $old->forceFill(['revoked_at' => now()])->save();

        return $this->issueTokens($admin, $client);
    }

    private function issueTokens(Admin $admin, McpOAuthClient $client): JsonResponse
    {
        $minutes = (int) config('mcp.oauth.access_token_minutes', 60);
        $days = McpSettings::tokenDays() ?: (int) config('mcp.oauth.refresh_token_max_days', 365);

        [, $access, $refresh] = McpAccessToken::issueForOAuth($admin, $client, $minutes, $days);

        return response()->json([
            'access_token' => $access,
            'token_type' => 'Bearer',
            'expires_in' => $minutes * 60,
            'refresh_token' => $refresh,
            'scope' => self::SCOPE,
        ])->header('Cache-Control', 'no-store')->header('Pragma', 'no-cache');
    }

    // ---------------------------------------------------------------- مساعدات

    /**
     * @return array{0: ?McpOAuthClient, 1: mixed} العميل، أو رد خطأ جاهز
     */
    private function validateAuthorizationRequest(Request $request): array
    {
        $client = McpOAuthClient::query()
            ->where('client_id', (string) $request->input('client_id'))
            ->whereNull('revoked_at')
            ->first();
        $redirectUri = (string) $request->input('redirect_uri');

        // عميل أو رابط رجوع غير معروف: لا نعيد التوجيه إليه أبداً (RFC 6749 §4.1.2.1).
        if (!$client || !$client->allowsRedirect($redirectUri) || !$this->redirectAllowed($redirectUri)) {
            return [null, response()->view('admin.mcp.oauth-error', [
                'message' => 'طلب الربط غير صالح: التطبيق غير مسجّل أو رابط الرجوع غير مطابق.',
            ], 400)];
        }

        $fail = fn (string $error, string $description) => [null, $this->redirectWithParams($redirectUri, [
            'error' => $error,
            'error_description' => $description,
            'state' => $request->input('state'),
        ])];

        if ($request->input('response_type') !== 'code') {
            return $fail('unsupported_response_type', 'response_type must be code');
        }

        if ($request->input('code_challenge_method') !== 'S256' || strlen((string) $request->input('code_challenge')) < 43) {
            return $fail('invalid_request', 'PKCE with S256 is required');
        }

        $resource = $request->input('resource');
        if ($resource !== null && rtrim((string) $resource, '/') !== $this->resource()) {
            return $fail('invalid_target', 'unknown resource');
        }

        return [$client, null];
    }

    /**
     * https لنطاقات العملاء المعروفة فقط، أو http/https على localhost لعملاء سطح المكتب.
     */
    private function redirectAllowed(string $uri): bool
    {
        $parts = parse_url($uri);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower($parts['host'] ?? '');

        if ($host === '' || isset($parts['fragment'])) {
            return false;
        }

        if (in_array($host, ['localhost', '127.0.0.1', '[::1]'], true)) {
            return in_array($scheme, ['http', 'https'], true);
        }

        if ($scheme !== 'https') {
            return false;
        }

        foreach ((array) config('mcp.oauth.redirect_hosts', []) as $allowed) {
            $allowed = strtolower((string) $allowed);
            if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
                return true;
            }
        }

        return false;
    }

    private function clientCredentials(Request $request): array
    {
        $header = (string) $request->header('Authorization');
        if (str_starts_with($header, 'Basic ')) {
            $decoded = base64_decode(substr($header, 6), true) ?: '';
            [$id, $secret] = array_pad(explode(':', $decoded, 2), 2, null);

            return [urldecode((string) $id), $secret !== null ? urldecode($secret) : null];
        }

        return [$request->input('client_id'), $request->input('client_secret')];
    }

    /** لا رمز ولا إعادة توجيه: الموظف يرى السبب، والعميل يبقى بلا ربط حتى يُفعَّل. */
    private function notEnabledView(Admin $admin)
    {
        return response()->view('admin.mcp.oauth-error', [
            'message' => "لم تفعّل الإدارة استخدام المساعد الذكي لحساب {$admin->name}. اطلب من مدير النظام تفعيله من شاشة Super Admin ← MCP ← الموظفون، ثم أعد محاولة الربط.",
        ], 403);
    }

    private function currentAdmin(): ?Admin
    {
        $admin = Auth::guard('admin')->user();

        return $admin && ($admin->status ?? 'active') !== 'suspended' ? $admin : null;
    }

    private function redirectWithParams(string $uri, array $params)
    {
        $query = http_build_query(array_filter($params, fn ($value) => $value !== null && $value !== ''));

        return redirect()->away($uri . (str_contains($uri, '?') ? '&' : '?') . $query);
    }

    private function oauthError(string $error, string $description, int $status = 400): JsonResponse
    {
        return response()->json(['error' => $error, 'error_description' => $description], $status)
            ->header('Cache-Control', 'no-store');
    }

    private function audit(Admin $admin, string $method, McpOAuthClient $client, string $status): void
    {
        McpAuditLog::query()->create([
            'admin_id' => $admin->id,
            'method' => $method,
            'tool' => Str::limit($client->client_name ?: $client->client_id, 117),
            'arguments' => ['client_id' => $client->client_id],
            'status' => $status,
            'ip' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    private function ensureEnabled(): void
    {
        abort_unless(McpSettings::enabled() && McpSettings::oauthEnabled(), 404);
    }

    private function issuer(): string
    {
        return self::absoluteUrl('/');
    }

    private function resource(): string
    {
        return self::absoluteUrl('/mcp');
    }

    /**
     * رابط مطلق للاكتشاف. خلف Cloudflare/بروكسي قد يرى Laravel الطلب http فيولّد
     * روابط http يرفضها claude.ai وChatGPT؛ إذا كان APP_URL على https نلتزم به.
     */
    public static function absoluteUrl(string $path): string
    {
        $url = rtrim(url($path), '/');

        if (str_starts_with((string) config('app.url'), 'https://') && str_starts_with($url, 'http://')) {
            $url = 'https://' . substr($url, 7);
        }

        return $url;
    }
}
