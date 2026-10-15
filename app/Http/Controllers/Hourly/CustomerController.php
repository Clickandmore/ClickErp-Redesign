<?php

namespace App\Http\Controllers\Hourly;

use App\Http\Controllers\Controller;
use App\Models\Hourly\{HourlyCustomerDocument, HourlyCustomerFavorite, HourlyCustomerGroup, HourlyCustomerProfile, HourlyRentalOrder, HourlyTeam, HourlyVisit, ServiceDistrict};
use App\Models\ingaz\{User, UserAddress};
use App\Services\Hourly\{CustomerDirectory, CustomerNotifier, MergeCustomersService};
use App\Services\ManagedFileStorage;
use App\Support\PhoneNumber;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\Rule;

/**
 * «عملاء الساعات»: قائمة وملف وإنشاء وتعديل للعملاء من داخل قسم الساعات.
 * نفس جدول العملاء لكل الأنظمة؛ الصفحات والتحقق هنا منفصلة عن شاشات العملاء الحالية (UserController) ولا تغيّرها.
 * كل إجراء حساس له صلاحيته في config/route_permissions.php ويُسجَّل في تبويب «السجلات».
 */
class CustomerController extends Controller
{
    public const TABS = ['profile', 'addresses', 'balance', 'calendar', 'orders', 'visits', 'documents', 'favorites', 'ratings', 'report', 'merge', 'logs'];

    public function __construct(private CustomerDirectory $directory) {}

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $query = $this->directory->query($filters);
        $balances = $this->directory->balances($query);
        $customers = $this->directory->sort($query, $filters)->paginate((int) ($filters['per_page'] ?? 25))->withQueryString();

        return view('admin.hourly_customers.index', $this->lookups() + [
            'customers' => $customers, 'filters' => $filters, 'balances' => $balances,
            'canCreate' => AdminGuardHasPerm('hourly_customers_create'), 'canEdit' => AdminGuardHasPerm('hourly_customers_edit'),
            'canExport' => AdminGuardHasPerm('hourly_customers_export'), 'canRestore' => AdminGuardHasPerm('hourly_customers_restore'),
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->filters($request);
        $query = $this->directory->sort($this->directory->query($filters), $filters);
        abort_if((clone $query)->count() > 20000, 422, __('hourly_customers.messages.export_limit'));
        $sources = $this->sources();
        $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setRightToLeft(app()->getLocale() === 'ar');
        $sheet->fromArray(__('hourly_customers.export_headers'), null, 'A1');
        $row = 2;
        foreach ($query->lazy(500) as $c) {
            $values = [$c->id, $c->created_at?->format('Y-m-d H:i'), $sources[$c->register_from] ?? $c->register_from, $c->name, $c->email, $c->phone,
                __('hourly_customers.types.'.($c->customer_type ?: 'individual')), $c->group_name, $c->marketing_source ? __('hourly_customers.marketing.'.$c->marketing_source) : '',
                number_format((float) $c->hourly_wallet_balance, 2, '.', ''), __($c->verified_at ? 'hourly_customers.verified' : 'hourly_customers.unverified'),
                $c->orders_count, number_format((float) $c->orders_total, 2, '.', '')];
            foreach ($values as $i => $value) $sheet->setCellValueExplicit(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1).$row, (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $row++;
        }
        foreach (range(1, 13) as $i) $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        $sheet->freezePane('A2');

        return $this->xlsx($book, 'hourly-customers.xlsx');
    }

    public function create()
    {
        return view('admin.hourly_customers.create', $this->lookups() + ['customer' => null, 'profile' => new HourlyCustomerProfile(['customer_type' => 'individual', 'language' => 'ar'])]);
    }

    public function store(Request $request)
    {
        $user = $this->directory->save(null, $this->validated($request, null), auth('admin')->id());

        return redirect()->route('admin.hourly-customers.show', $user->id)->with('success', __('hourly_customers.messages.created'));
    }

    public function show(Request $request, int $customer, string $tab = 'profile')
    {
        abort_unless(in_array($tab, self::TABS, true), 404);
        $user = User::findOrFail($customer);
        $profile = $this->directory->profile($user) ?? new HourlyCustomerProfile(['user_id' => $user->id, 'customer_type' => 'individual', 'language' => 'ar']);
        if (!$profile->exists) {
            // عميل من نظام آخر بلا ملف ساعات بعد: نقترح الاسم الأول والأخير من اسمه الحالي.
            [$first, $last] = array_pad(preg_split('/\s+/u', trim((string) $user->name), 2), 2, '');
            $profile->fill(['first_name' => $first, 'last_name' => $last]);
        }
        $deleted = $profile->exists && $profile->trashed();
        $can = fn (string $perm) => !$deleted && AdminGuardHasPerm($perm);

        return view('admin.hourly_customers.show', $this->lookups() + [
            'customer' => $user, 'profile' => $profile, 'tab' => $tab, 'deleted' => $deleted,
            'stats' => $this->directory->stats($user), 'buckets' => $this->directory->buckets($user), 'tabData' => $this->tabData($request, $user, $tab),
            'sourceLabel' => $this->sources()[$user->register_from] ?? $user->register_from, 'deleteBlockers' => $deleted ? [] : $this->directory->deleteBlockers($user),
            'canEdit' => $can('hourly_customers_edit'), 'canBalance' => $can('hourly_customers_balance'), 'canAdjust' => $can('hourly_customers_balance_adjust'),
            'canNotify' => $can('hourly_customers_notify'), 'canDelete' => $can('hourly_customers_delete'), 'canMerge' => $can('hourly_customer_duplicates_merge'),
            'canRestore' => $deleted && AdminGuardHasPerm('hourly_customers_restore'),
            'payableOrders' => $can('hourly_customers_balance') ? $this->payableOrders($user) : collect(),
            'collectors' => $can('hourly_customers_balance') ? $this->collectors() : [],
        ]);
    }

    public function update(Request $request, int $customer)
    {
        $user = User::findOrFail($customer);
        abort_if(HourlyCustomerProfile::onlyTrashed()->where('user_id', $user->id)->exists(), 422, __('hourly_customers.delete.readonly'));
        $this->directory->save($user, $this->validated($request, $user), auth('admin')->id());

        return redirect()->route('admin.hourly-customers.show', $user->id)->with('success', __('hourly_customers.messages.updated'));
    }

    public function verify(Request $request, int $customer)
    {
        $data = $request->validate(['verified' => 'required|boolean']);
        $profile = $this->directory->setVerified(User::findOrFail($customer), (bool) $data['verified'], auth('admin')->id());

        return response()->json(['message' => __('hourly_customers.messages.verified_saved'), 'verified' => $profile->verified_at !== null]);
    }

    /** «ADD BALANCE»: إيداع في المحفظة أو دفعة على طلب. */
    public function deposit(Request $request, int $customer)
    {
        $user = User::findOrFail($customer);
        $data = $request->validate([
            'mode' => 'required|in:wallet,order',
            'order_id' => ['required_if:mode,order', 'nullable', 'integer', Rule::exists('hourly_rental_orders', 'id')->where('user_id', $user->id)],
            'collected_by_type' => 'required|in:admin,maid',
            'collected_by_id' => ['required', 'integer', $request->input('collected_by_type') === 'maid' ? Rule::exists('maids', 'id') : Rule::exists('admins', 'id')],
            'amount' => 'required|numeric|min:0.01|max:9999999',
            'vat_rate' => 'required|numeric|min:0|max:100',
            'payment_method' => ['required', Rule::in(CustomerDirectory::PAYMENT_METHODS)],
            'paid_at' => 'required|date_format:Y-m-d\TH:i|before_or_equal:now',
            'description' => 'nullable|string|max:500',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);
        if ($request->hasFile('attachment')) $data['attachment_path'] = $request->file('attachment')->store('hourly-customer-payments/'.$user->id, 'local');
        unset($data['attachment']);
        $result = $this->directory->deposit($user, $data, auth('admin')->id());
        $message = $result['type'] === 'order'
            ? __('hourly_customers.messages.order_paid', ['amount' => number_format($result['amount'], 2), 'order' => $result['order_number']])
            : __('hourly_customers.messages.deposited', ['amount' => number_format($result['amount'], 2), 'balance' => number_format($result['balance'], 2)]);

        if ($result['type'] === 'order') {
            $result['receipt_url'] = route('admin.hourly-orders.receipt', [$result['order_id'], $result['payment_id']]);
        }

        return $request->expectsJson()
            ? response()->json(['message' => $message] + $result)
            : back()->with('success', $message)->with('receipt_url', $result['receipt_url'] ?? null);
    }

    /** «تحرير» الرصيد: زيادة أو خصم بسبب إلزامي. */
    public function adjustBalance(Request $request, int $customer)
    {
        $user = User::findOrFail($customer);
        $data = $request->validate(['direction' => 'required|in:increase,decrease', 'amount' => 'required|numeric|min:0.01|max:9999999', 'reason' => 'required|string|min:3|max:500']);
        $balance = $this->directory->adjust($user, $data['direction'], (float) $data['amount'], $data['reason'], auth('admin')->id());
        $message = __('hourly_customers.messages.adjusted', ['balance' => number_format($balance, 2)]);

        return $request->expectsJson() ? response()->json(['message' => $message, 'balance' => $balance]) : redirect()->route('admin.hourly-customers.show', [$user->id, 'balance'])->with('success', $message);
    }

    public function notify(Request $request, int $customer, CustomerNotifier $notifier)
    {
        $user = User::findOrFail($customer);
        $data = $request->validate(['channel' => ['required', Rule::in(CustomerNotifier::CHANNELS)], 'title' => 'required|string|max:150', 'body' => 'required|string|max:1000']);
        $result = $notifier->send($user, $data['channel'], trim(strip_tags($data['title'])), trim(strip_tags($data['body'])), auth('admin')->user());

        return $request->expectsJson()
            ? response()->json($result, $result['status'] === 'sent' ? 200 : 422)
            : back()->with($result['status'] === 'sent' ? 'success' : 'error', $result['message']);
    }

    public function destroy(Request $request, int $customer)
    {
        $data = $request->validate(['reason' => 'nullable|string|max:500']);
        $this->directory->delete(User::findOrFail($customer), auth('admin')->id(), $data['reason'] ?? null);

        return redirect()->route('admin.hourly-customers.index')->with('success', __('hourly_customers.messages.deleted'));
    }

    public function restore(int $customer)
    {
        $this->directory->restore(User::findOrFail($customer), auth('admin')->id());

        return redirect()->route('admin.hourly-customers.show', $customer)->with('success', __('hourly_customers.messages.restored'));
    }

    public function storeAddress(Request $request, int $customer)
    {
        $user = User::findOrFail($customer);
        UserAddress::create($this->addressData($request) + ['user_id' => $user->id]);

        return redirect()->route('admin.hourly-customers.show', [$user->id, 'addresses'])->with('success', __('hourly_customers.messages.address_saved'));
    }

    public function updateAddress(Request $request, int $customer, int $address)
    {
        $model = UserAddress::where('user_id', $customer)->findOrFail($address);
        $model->update($this->addressData($request));

        return redirect()->route('admin.hourly-customers.show', [$customer, 'addresses'])->with('success', __('hourly_customers.messages.address_saved'));
    }

    /** بطاقة هوية: وجه إلزامي وخلف اختياري، وحالة «موثّق» تغيّر توثيق العميل. */
    public function storeDocument(Request $request, int $customer, ManagedFileStorage $storage)
    {
        $user = User::findOrFail($customer);
        $data = $request->validate([
            'doc_type' => ['required', Rule::in(HourlyCustomerDocument::TYPES)], 'number' => 'nullable|string|max:50', 'expires_at' => 'nullable|date',
            'front' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', 'back' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);
        $document = DB::transaction(fn () => HourlyCustomerDocument::create([
            'user_id' => $user->id, 'doc_type' => $data['doc_type'], 'number' => $data['number'] ?? null, 'expires_at' => $data['expires_at'] ?? null,
            'front_path' => $storage->uploadFile($request->file('front'), 'hourly-customer-documents/'.$user->id),
            'back_path' => $request->hasFile('back') ? $storage->uploadFile($request->file('back'), 'hourly-customer-documents/'.$user->id) : null,
            'uploaded_by' => auth('admin')->id(),
        ]));
        $this->directory->log($user, 'document_uploaded', ['document_id' => $document->id, 'doc_type' => $document->doc_type], auth('admin')->id());

        return redirect()->route('admin.hourly-customers.show', [$user->id, 'documents'])->with('success', __('hourly_customers.messages.document_saved'));
    }

    public function reviewDocument(Request $request, int $customer, int $document)
    {
        $user = User::findOrFail($customer);
        $data = $request->validate(['status' => ['required', Rule::in(HourlyCustomerDocument::STATUSES)], 'note' => 'nullable|string|max:500']);
        DB::transaction(function () use ($user, $document, $data) {
            $model = HourlyCustomerDocument::where('user_id', $user->id)->lockForUpdate()->findOrFail($document);
            $model->update(['status' => $data['status'], 'note' => $data['note'] ?? null, 'reviewed_by' => auth('admin')->id(), 'reviewed_at' => now()]);
            // العميل موثّق ما دامت لديه بطاقة موثّقة سارية.
            $verified = HourlyCustomerDocument::where('user_id', $user->id)->where('status', 'verified')
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', today()))->exists();
            $this->directory->setVerified($user, $verified, auth('admin')->id());
            $this->directory->log($user, 'document_reviewed', ['document_id' => $model->id, 'status' => $data['status']], auth('admin')->id());
        });

        return redirect()->route('admin.hourly-customers.show', [$user->id, 'documents'])->with('success', __('hourly_customers.messages.document_reviewed'));
    }

    public function documentFile(int $customer, int $document, string $side, ManagedFileStorage $storage)
    {
        $model = HourlyCustomerDocument::where('user_id', $customer)->findOrFail($document);
        $path = $side === 'back' ? $model->back_path : $model->front_path;
        abort_unless($path && $storage->exists($path), 404);

        return $storage->inlineResponse($path, $model->doc_type.'-'.$side.'.'.pathinfo($path, PATHINFO_EXTENSION), $storage->mimeType($path));
    }

    public function storeFavorite(Request $request, int $customer)
    {
        $user = User::findOrFail($customer);
        $data = $request->validate(['favorable_type' => ['required', Rule::in(HourlyCustomerFavorite::TYPES)],
            'favorable_id' => ['required', 'integer', $request->input('favorable_type') === 'team' ? Rule::exists('hourly_teams', 'id') : Rule::exists('maids', 'id')->where('rental_mode', 'hourly')]]);
        HourlyCustomerFavorite::firstOrCreate(['user_id' => $user->id] + $data, ['created_by' => auth('admin')->id()]);
        $this->directory->log($user, 'favorite_added', $data, auth('admin')->id());

        return redirect()->route('admin.hourly-customers.show', [$user->id, 'favorites'])->with('success', __('hourly_customers.messages.favorite_saved'));
    }

    public function destroyFavorite(int $customer, int $favorite)
    {
        $user = User::findOrFail($customer);
        $model = HourlyCustomerFavorite::where('user_id', $user->id)->findOrFail($favorite);
        $model->delete();
        $this->directory->log($user, 'favorite_removed', $model->only(['favorable_type', 'favorable_id']), auth('admin')->id());

        return redirect()->route('admin.hourly-customers.show', [$user->id, 'favorites'])->with('success', __('hourly_customers.messages.favorite_removed'));
    }

    /** كشف الحساب: PDF أو Excel بين تاريخين. */
    public function statement(Request $request, int $customer)
    {
        $user = User::findOrFail($customer);
        [$from, $to] = $this->statementRange($request);
        $format = $request->validate(['format' => 'required|in:pdf,xlsx'])['format'];
        $statement = $this->directory->statement($user, $from, $to);
        $name = 'statement-'.$user->id.'-'.$from->format('Ymd').'-'.$to->format('Ymd');

        if ($format === 'pdf') {
            $pdf = new \Mpdf\Mpdf(array_replace(\App\Services\Hourly\HourlyPdf::mpdfConfig(), ['format' => 'A4', 'margin_left' => 12, 'margin_right' => 12, 'margin_top' => 12, 'margin_bottom' => 12,
                'directionality' => app()->getLocale() === 'ar' ? 'rtl' : 'ltr']));
            $pdf->WriteHTML(view('admin.hourly_customers.statement_pdf', ['customer' => $user, 'statement' => $statement])->render());

            return response($pdf->Output('', \Mpdf\Output\Destination::STRING_RETURN), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="'.$name.'.pdf"']);
        }

        $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setRightToLeft(app()->getLocale() === 'ar');
        $c = fn (string $key, array $r = []) => __('hourly_customers.report.'.$key, $r);
        $rows = [[$c('title'), $user->name, '#'.$user->id], [$c('period'), $from->toDateString(), $to->toDateString()], [$c('opening'), $statement['opening']], [],
            [$c('date'), $c('kind'), $c('ref'), $c('debit'), $c('credit'), $c('balance')]];
        foreach ($statement['lines'] as $line) $rows[] = [$line['at']->format('Y-m-d H:i'), $c('kinds.'.$line['kind']), $line['ref'], $line['debit'], $line['credit'], $line['balance']];
        $rows[] = [];
        $rows[] = [$c('closing'), $statement['closing']];
        $rows[] = [$c('wallet_opening'), $statement['wallet']['opening'], $c('wallet_closing'), $statement['wallet']['closing']];
        $sheet->fromArray($rows, null, 'A1');
        foreach (range('A', 'F') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

        return $this->xlsx($book, $name.'.xlsx');
    }

    /** «الحسابات المكررة»: مجموعات حسب الجوال المطبّع أو البريد أو الهوية. */
    public function duplicates(Request $request, MergeCustomersService $merger)
    {
        $groups = $merger->groups();
        $page = max(1, (int) $request->input('page', 1));
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator($groups->forPage($page, 20)->values(), $groups->count(), 20, $page, ['path' => $request->url(), 'query' => $request->query()]);

        return view('admin.hourly_customers.duplicates', ['groups' => $paginator, 'sources' => $this->sources(), 'canMerge' => AdminGuardHasPerm('hourly_customer_duplicates_merge')]);
    }

    /** شاشة الدمج: اختيار الحساب الأساسي من الحسابات المحددة. */
    public function mergeForm(Request $request)
    {
        $ids = $request->validate(['ids' => 'required|array|min:2|max:10', 'ids.*' => 'integer|distinct'])['ids'];
        $users = DB::table('users')->whereIn('id', $ids)->orderBy('id')->get(['id', 'name', 'phone', 'email', 'national_num', 'created_at', 'register_from', 'hourly_wallet_balance']);
        abort_if($users->count() < 2, 404);
        $orders = HourlyRentalOrder::whereIn('user_id', $ids)->selectRaw('user_id, COUNT(*) as n')->groupBy('user_id')->pluck('n', 'user_id');

        return view('admin.hourly_customers.merge', ['users' => $users, 'orders' => $orders, 'sources' => $this->sources(), 'back' => $request->input('back')]);
    }

    public function merge(Request $request, MergeCustomersService $merger)
    {
        $data = $request->validate([
            'primary_id' => 'required|integer|exists:users,id', 'merge_ids' => 'required|array|min:1|max:9', 'merge_ids.*' => 'integer|distinct|exists:users,id|different:primary_id',
            'reason' => 'nullable|string|max:500',
        ]);
        $merger->merge(User::findOrFail($data['primary_id']), $data['merge_ids'], auth('admin')->id(), $data['reason'] ?? null);

        return redirect()->route('admin.hourly-customers.show', [$data['primary_id'], 'merge'])->with('success', __('hourly_customers.messages.merged', ['count' => count($data['merge_ids'])]));
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'search' => 'nullable|string|max:150', 'balance' => ['nullable', Rule::in(CustomerDirectory::BALANCE_STATES)],
            'type' => ['nullable', Rule::in(HourlyCustomerProfile::TYPES)], 'activity' => ['nullable', Rule::in(CustomerDirectory::ACTIVITY)],
            'group_id' => ['nullable', 'regex:/^(none|\d+)$/'],
            'marketing_source' => ['nullable', Rule::in(array_merge(HourlyCustomerProfile::MARKETING_SOURCES, ['none']))],
            'register_from' => 'nullable|string|max:100', 'city_id' => 'nullable|integer', 'district_id' => 'nullable|integer',
            'verified' => 'nullable|in:0,1', 'show_deleted' => 'nullable|boolean', 'per_page' => 'nullable|integer|in:25,50,100,250',
            'sort' => ['nullable', Rule::in(array_keys(CustomerDirectory::SORTS))], 'direction' => 'nullable|in:asc,desc',
        ]);
    }

    /**
     * قواعد الإنشاء والتعديل. الجوال يُقبل بأي صيغة تُطبَّع إلى 5XXXXXXXX (تطبيق العميل يدخل بها)،
     * وجوال العميل القديم بصيغة غير سعودية يبقى مقبولًا ما لم يتغير.
     */
    private function validated(Request $request, ?User $user): array
    {
        $latinDigits = fn ($value) => strtr((string) $value, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
        $request->merge([
            'iban' => $request->filled('iban') ? strtoupper(preg_replace('/\s+/u', '', $latinDigits($request->input('iban')))) : null,
            'phone' => trim((string) $request->input('phone')),
            'first_name' => preg_replace('/\s+/u', ' ', trim((string) $request->input('first_name'))),
            'last_name' => preg_replace('/\s+/u', ' ', trim((string) $request->input('last_name'))),
            'national_num' => $request->filled('national_num') ? strtoupper(preg_replace('/\s+/u', '', $latinDigits($request->input('national_num')))) : null,
        ]);
        $keepsPhone = $user && $request->input('phone') === (string) $user->phone;
        // أحرف عربية/لاتينية فقط مع مسافة مفردة بين المقاطع: لا أرقام ولا رموز.
        $personName = ['regex:/^[\p{L}\p{M}]+(?: [\p{L}\p{M}]+)*$/u'];

        return $request->validate([
            'customer_type' => ['required', Rule::in(HourlyCustomerProfile::TYPES)],
            'group_id' => ['nullable', 'integer', Rule::exists('hourly_customer_groups', 'id')->where('is_active', true)],
            'payment_term' => ['nullable', Rule::in(HourlyCustomerProfile::PAYMENT_TERMS)],
            'first_name' => ['exclude_unless:customer_type,individual', 'required', 'string', 'max:100', ...$personName],
            'last_name' => ['exclude_unless:customer_type,individual', 'required', 'string', 'max:100', ...$personName],
            'company_name' => 'exclude_unless:customer_type,company|required|string|max:200',
            'company_id' => 'exclude_unless:customer_type,company|nullable|string|max:100',
            'phone' => ['bail', 'required', 'string', 'max:40',
                // أرقام فقط (مع + اختيارية لرمز الدولة)، ثم 9 أرقام تبدأ بـ 5 بعد رمز السعودية.
                function ($attribute, $value, $fail) use ($keepsPhone) { if (!$keepsPhone && (!preg_match('/^\+?[0-9٠-٩]+$/u', $value) || !PhoneNumber::saudiMobile($value))) $fail(__('hourly_customers.messages.phone_format')); },
                function ($attribute, $value, $fail) use ($user) { if ($this->directory->phoneTaken($value, $user?->id)) $fail(__('hourly_customers.messages.phone_taken')); }],
            'phone2' => 'nullable|string|max:50',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'identity_type' => ['nullable', Rule::in(HourlyCustomerProfile::IDENTITY_TYPES)],
            'national_num' => ['nullable', 'string', 'max:50', function ($attribute, $value, $fail) use ($request, $user) {
                if ($user && $value === strtoupper((string) $user->national_num)) return; // رقم قديم لم يُعدَّل
                // الهوية الوطنية تبدأ بـ 1 والإقامة بـ 2 (10 أرقام)؛ الجواز حروف لاتينية وأرقام فقط.
                $valid = match ($request->input('identity_type')) {
                    'national_id' => preg_match('/^1[0-9]{9}$/', $value),
                    'iqama' => preg_match('/^2[0-9]{9}$/', $value),
                    'passport' => preg_match('/^[A-Z0-9]{6,20}$/', $value),
                    default => preg_match('/^[A-Z0-9]{6,20}$/', $value),
                };
                if (!$valid) $fail(__('hourly_customers.messages.national_format_'.($request->input('identity_type') ?: 'any')));
            }],
            // تاريخ ميلاد قديم لم يتغير يبقى مقبولًا في التعديل؛ الجديد يجب أن يعطي عمرًا بين الحدين.
            'birthdate' => $user && $user->birthdate && $request->input('birthdate') === \Carbon\Carbon::parse($user->birthdate)->toDateString() ? 'nullable|date' : ['nullable', 'date',
                'before_or_equal:'.now()->subYears(HourlyCustomerProfile::MIN_AGE)->toDateString(), 'after_or_equal:'.now()->subYears(HourlyCustomerProfile::MAX_AGE)->toDateString()],
            'language' => ['required', Rule::in(HourlyCustomerProfile::LANGUAGES)],
            'iban' => ['nullable', 'regex:/^SA[0-9]{22}$/'],
            'note' => 'nullable|string|max:2000',
            'marketing_source' => ['nullable', Rule::in(HourlyCustomerProfile::MARKETING_SOURCES)],
            // الإنشاء: كلمة مرور اختيارية؛ التعديل: «كلمة مرور جديدة» تُحدَّث فقط إن مُلئت.
            'password' => $user ? 'prohibited' : 'nullable|string|min:8|max:100',
            'new_password' => $user ? 'nullable|string|min:8|max:100|confirmed' : 'prohibited',
        ], ['iban.regex' => __('hourly_customers.messages.iban_format'), 'first_name.regex' => __('hourly_customers.messages.name_format'), 'last_name.regex' => __('hourly_customers.messages.name_format'),
            'birthdate.before_or_equal' => __('hourly_customers.messages.age_min', ['age' => HourlyCustomerProfile::MIN_AGE]), 'birthdate.after_or_equal' => __('hourly_customers.messages.age_max', ['age' => HourlyCustomerProfile::MAX_AGE])]);
    }

    /** نفس حقول العنوان في معالج الحجز، مع ملء أعمدة العنوان القديمة (الاسم والهاتف والعنوان المختصر) للأنظمة الأخرى. */
    private function addressData(Request $request): array
    {
        $data = $request->validate([
            'city_id' => 'required|integer|exists:cities,id', 'hourly_district_id' => 'nullable|integer|exists:hourly_service_districts,id',
            'address' => 'required|string|max:500', 'building' => 'nullable|string|max:255', 'unit' => 'nullable|string|max:255',
            'contact_first_name' => 'nullable|string|max:100', 'contact_last_name' => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:30', 'contact_phone2' => 'nullable|string|max:30',
            'latitude' => 'required|numeric|between:-90,90', 'longitude' => 'required|numeric|between:-180,180',
        ]);
        $district = !empty($data['hourly_district_id']) ? ServiceDistrict::where('city_id', $data['city_id'])->find($data['hourly_district_id']) : null;
        if (!empty($data['hourly_district_id']) && !$district) throw \Illuminate\Validation\ValidationException::withMessages(['hourly_district_id' => __('hourly_customers.messages.district_city')]);

        return $data + ['district' => $district?->name, 'name' => trim(($data['contact_first_name'] ?? '').' '.($data['contact_last_name'] ?? '')) ?: ' ',
            'phone' => $data['contact_phone'] ?? ' ', 'title' => $data['building'] ?? ' '];
    }

    private function tabData(Request $request, User $user, string $tab): array
    {
        return match ($tab) {
            'addresses' => ['addresses' => UserAddress::where('user_id', $user->id)->orderByDesc('id')->get(),
                'districts' => ServiceDistrict::where('is_active', true)->orderBy('name')->get(['id', 'city_id', 'name', 'name_en'])],
            'balance' => [
                'wallet' => DB::table('hourly_wallet_transactions as t')->leftJoin('hourly_rental_orders as o', 'o.id', '=', DB::raw('COALESCE(t.hourly_rental_order_id, t.linked_order_id)'))
                    ->where('t.user_id', $user->id)->orderByDesc('t.id')->select('t.*', 'o.order_number')->paginate(20, ['*'], 'wallet_page')->withQueryString(),
                'payments' => DB::table('hourly_order_payments as p')->join('hourly_rental_orders as o', 'o.id', '=', 'p.hourly_rental_order_id')
                    ->where('p.user_id', $user->id)->orderByDesc('p.id')->select('p.*', 'o.order_number')->paginate(20, ['*'], 'payments_page')->withQueryString(),
                'people' => $this->people(),
            ],
            'calendar' => $this->calendar($request, $user),
            'orders' => ['orders' => HourlyRentalOrder::where('user_id', $user->id)->withCount('visits')->orderByDesc('id')->paginate(20)->withQueryString()],
            'visits' => ['visits' => HourlyVisit::whereHas('order', fn ($o) => $o->where('user_id', $user->id))->with(['workers:id,name', 'order:id,order_number'])
                ->orderByDesc('scheduled_start_at')->paginate(20)->withQueryString()],
            'documents' => ['documents' => HourlyCustomerDocument::where('user_id', $user->id)->orderByDesc('id')->get(), 'people' => $this->people()],
            'favorites' => $this->favorites($user),
            'ratings' => ['reviews' => DB::table('hourly_worker_reviews as r')->leftJoin('maids as m', 'm.id', '=', 'r.maid_id')
                ->leftJoin('hourly_visits as v', 'v.id', '=', 'r.visit_id')->leftJoin('hourly_rental_orders as o', 'o.id', '=', 'r.hourly_rental_order_id')
                ->where('r.user_id', $user->id)->orderByDesc('r.id')->select('r.*', 'm.name as maid_name', 'v.visit_number', 'v.scheduled_start_at', 'o.order_number')->paginate(20)->withQueryString()],
            'report' => (function () use ($request, $user) { [$from, $to] = $this->statementRange($request); return ['statement' => $this->directory->statement($user, $from, $to)]; })(),
            'merge' => ['groups' => app(MergeCustomersService::class)->groups($user->id),
                'history' => DB::table('hourly_customer_merges')->where('primary_user_id', $user->id)->orWhere('merged_user_id', $user->id)->orderByDesc('id')->get(),
                'people' => $this->people()],
            'logs' => ['logs' => $this->logs($user)],
            default => [],
        };
    }

    /** التقويم الشهري/الأسبوعي لزيارات العميل. */
    private function calendar(Request $request, User $user): array
    {
        $data = $request->validate(['view' => 'nullable|in:month,week', 'date' => 'nullable|date_format:Y-m-d']);
        $view = $data['view'] ?? 'month';
        $anchor = Carbon::parse($data['date'] ?? today()->toDateString());
        [$from, $to] = $view === 'week'
            ? [$anchor->copy()->startOfWeek(Carbon::SATURDAY), $anchor->copy()->startOfWeek(Carbon::SATURDAY)->addDays(6)->endOfDay()]
            : [$anchor->copy()->startOfMonth()->startOfWeek(Carbon::SATURDAY), $anchor->copy()->endOfMonth()->endOfWeek(Carbon::FRIDAY)];
        $visits = HourlyVisit::whereHas('order', fn ($o) => $o->where('user_id', $user->id))->whereBetween('scheduled_start_at', [$from, $to])
            ->with(['workers:id,name'])->orderBy('scheduled_start_at')->get();
        $teams = HourlyTeam::whereIn('id', $visits->pluck('hourly_team_id')->filter()->unique())->pluck('name', 'id');

        return ['view' => $view, 'anchor' => $anchor, 'from' => $from, 'to' => $to, 'teams' => $teams,
            'byDay' => $visits->groupBy(fn ($v) => $v->scheduled_start_at->toDateString())];
    }

    private function favorites(User $user): array
    {
        $rows = HourlyCustomerFavorite::where('user_id', $user->id)->orderByDesc('id')->get();
        $maids = DB::table('maids')->whereIn('id', $rows->where('favorable_type', 'maid')->pluck('favorable_id')->all() ?: [0])->pluck('name', 'id');
        $teams = HourlyTeam::whereIn('id', $rows->where('favorable_type', 'team')->pluck('favorable_id')->all() ?: [0])->pluck('name', 'id');

        return ['favorites' => $rows, 'maidNames' => $maids, 'teamNames' => $teams,
            'maidOptions' => DB::table('maids')->where('rental_mode', 'hourly')->orderBy('name')->pluck('name', 'id'),
            'teamOptions' => HourlyTeam::where('is_active', true)->orderBy('name')->pluck('name', 'id')];
    }

    /** @return array{0:Carbon,1:Carbon} افتراضيًا من بداية الشهر حتى اليوم */
    private function statementRange(Request $request): array
    {
        $data = $request->validate(['from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from']);

        return [Carbon::parse($data['from'] ?? today()->startOfMonth()->toDateString()), Carbon::parse($data['to'] ?? today()->toDateString())];
    }

    /** سجل التعديلات والأحداث: بيانات العميل المشتركة وملف الساعات، والحذف والاستعادة والدمج وتعديل الرصيد وكلمة المرور. */
    private function logs(User $user)
    {
        if (!Schema::hasTable('activity_log')) return collect();
        $profileId = HourlyCustomerProfile::withTrashed()->where('user_id', $user->id)->value('id');
        $logs = DB::table('activity_log')->where(fn ($q) => $q->where(fn ($w) => $w->where('subject_type', User::class)->where('subject_id', $user->id))
            ->when($profileId, fn ($w) => $w->orWhere(fn ($x) => $x->where('subject_type', HourlyCustomerProfile::class)->where('subject_id', $profileId))))
            ->orderByDesc('id')->limit(150)->get();
        $admins = DB::table('admins')->whereIn('id', $logs->where('causer_type', \App\Models\auth\Admin::class)->pluck('causer_id')->unique()->all() ?: [0])->pluck('name', 'id');

        return $logs->map(function ($log) use ($admins) {
            $props = json_decode((string) $log->properties, true) ?: [];
            $changes = isset($props['attributes'])
                ? collect($props['attributes'])->except(['updated_at', 'created_at', 'password', 'remember_token'])->map(fn ($new, $field) => ['field' => $field, 'old' => $props['old'][$field] ?? null, 'new' => $new])->values()
                : collect($props)->except(['route', 'url', 'ip'])->map(fn ($value, $field) => ['field' => $field, 'old' => null, 'new' => $value])->values();

            return ['at' => $log->created_at, 'event' => $log->event ?? $log->description, 'by' => $admins[$log->causer_id] ?? null, 'changes' => $changes];
        });
    }

    /** الطلبات التي تقبل دفعة الآن مع المتبقي عليها. */
    private function payableOrders(User $user)
    {
        return HourlyRentalOrder::where('user_id', $user->id)->whereIn('status', ['pending_confirmation', 'active'])->where('payment_status', '!=', 'paid')
            ->where('payment_method', '!=', 'moyasar')->orderByDesc('id')->get(['id', 'order_number', 'total_amount', 'paid_amount'])
            ->map(fn ($o) => ['id' => $o->id, 'label' => '#'.$o->order_number, 'remaining' => round($o->total_amount - $o->paid_amount, 2)]);
    }

    /** «PAYMENT COLLECTED BY»: الموظفون وعاملات الساعات. */
    private function collectors(): array
    {
        return [
            'admin' => DB::table('admins')->orderBy('name')->pluck('name', 'id')->all(),
            'maid' => DB::table('maids')->where('rental_mode', 'hourly')->orderBy('name')->pluck('name', 'id')->all(),
        ];
    }

    private function people(): array
    {
        return ['admin' => DB::table('admins')->pluck('name', 'id')->all(), 'maid' => DB::table('maids')->where('rental_mode', 'hourly')->pluck('name', 'id')->all()];
    }

    private function sources(): array
    {
        return Schema::hasTable('hourly_customer_sources') ? DB::table('hourly_customer_sources')->pluck('name', 'code')->all() : [];
    }

    private function lookups(): array
    {
        return [
            'sources' => $this->sources(),
            'cities' => \App\Models\City::saudi()->active()->get(),
            'districtOptions' => ServiceDistrict::where('is_active', true)->orderBy('name')->get(['id', 'city_id', 'name', 'name_en']),
            'groups' => HourlyCustomerGroup::orderBy('name')->get(['id', 'name', 'is_active', 'discount_percent']),
        ];
    }

    private function xlsx(\PhpOffice\PhpSpreadsheet\Spreadsheet $book, string $name)
    {
        return response()->streamDownload(function () use ($book) {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save('php://output');
            $book->disconnectWorksheets();
        }, $name, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
