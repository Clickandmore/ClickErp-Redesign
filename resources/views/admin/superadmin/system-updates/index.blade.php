@include('admin.superadmin.system-updates._styles')

@php
    $suSettings = $systemUpdatesSettings;
    $suState = $systemUpdatesState;
    $issuedToken = session('system_update_client_token');
    $formatDate = fn ($value) => $value ? \Carbon\Carbon::parse($value)->translatedFormat('j M Y - h:i a') : '—';
@endphp

<div class="su-how" aria-label="كيف تعمل التحديثات">
    <div><b><span>1</span> اكتب التحديث</b><p>من «تحديث جديد»: عنوان، شرح، صور أو فيديو أو PDF، ثم «منشور».</p></div>
    <div><b><span>2</span> يظهر للموظفين</b><p>شريط ملوّن أعلى اللوحة + أيقونة 📣 بعدد ما لم يقرؤوه.</p></div>
    <div><b><span>3</span> يؤكدون الاطلاع</b><p>زر «تم الاطلاع» يخرجه من الشريط. تابع النسبة من «من اطلع؟».</p></div>
    <div><b><span>4</span> يصل لنسخ العملاء</b><p>كل نسخة مرتبطة تسحبه خلال دقيقة وتعرضه لموظفيها.</p></div>
</div>

<section class="panel" id="system-updates-list">
    <div class="panel-head su-toolbar">
        <div>
            <h2 class="panel-title">التحديثات</h2>
            <p class="panel-subtitle">كل تحديث منشور يظهر للموظفين في شريط أعلى لوحة التحكم حتى يضغطوا «تم الاطلاع»، ويبقى دائمًا في صفحة تحديثات النظام.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('dev-superadmin.system-updates.create') }}">تحديث جديد</a>
    </div>
    <div class="panel-body">
        @if ($systemUpdates->isEmpty())
            <div class="su-empty">لا توجد تحديثات بعد. ابدأ بإضافة أول تحديث، أو اربط هذه النسخة بمصدر تحديثات من الإعدادات أدناه.</div>
        @else
            <div class="table-wrap" style="margin-top:0">
                <table>
                    <thead>
                        <tr>
                            <th>التحديث</th>
                            <th>التصنيف</th>
                            <th>الحالة</th>
                            <th>النشر</th>
                            <th>الاطلاع</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($systemUpdates as $update)
                            @php
                                $meta = $update->categoryMeta();
                                $ratio = $activeEmployeesCount > 0 ? min(100, round($update->acknowledged_count / $activeEmployeesCount * 100)) : 0;
                                $isScheduled = $update->isPublished() && $update->published_at && $update->published_at->isFuture();
                            @endphp
                            <tr>
                                <td>
                                    <div class="su-title">{{ $update->title }}</div>
                                    <div class="su-sub">
                                        @if ($update->version) <span class="su-code">v{{ $update->version }}</span> · @endif
                                        @if ($update->media_count) {{ $update->media_count }} وسائط · @endif
                                        @if ($update->module_key) {{ $systemUpdateModules[$update->module_key]['name'] ?? $update->module_key }} · @endif
                                        @if ($update->isRemote())
                                            من المصدر <span class="su-code">{{ $update->source_host }}</span>
                                        @else
                                            {{ $update->is_shared ? 'يُشارك مع نسخ العملاء' : 'لهذه النسخة فقط' }}
                                        @endif
                                    </div>
                                </td>
                                <td><span class="su-cat su-cat-{{ $update->category }}">{{ $meta['label'] }}</span></td>
                                <td>
                                    @if (! $update->isPublished())
                                        <span class="su-pill">مسودة</span>
                                    @elseif ($isScheduled)
                                        <span class="su-pill is-info">مجدول</span>
                                    @else
                                        <span class="su-pill is-good">منشور</span>
                                    @endif
                                    @if ($update->isRemote()) <span class="su-pill is-warn">مصدر خارجي</span> @endif
                                </td>
                                <td>
                                    <div style="font-size:.82rem">{{ $formatDate($update->published_at) }}</div>
                                    @if ($update->banner_until)
                                        <div class="su-sub">في الشريط حتى {{ $update->banner_until->translatedFormat('j M Y') }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($update->isPublished())
                                        <div style="font-size:.82rem"><b>{{ $update->acknowledged_count }}</b> من {{ $activeEmployeesCount }} أكدوا</div>
                                        <div class="su-progress"><span style="width: {{ $ratio }}%"></span></div>
                                        <div class="su-sub">شاهده {{ $update->seen_count }}</div>
                                    @else
                                        <span class="su-sub">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="su-actions">
                                        @unless ($update->isRemote())
                                            <a class="btn btn-small" href="{{ route('dev-superadmin.system-updates.edit', $update) }}">تعديل</a>
                                        @endunless
                                        <a class="btn btn-small" href="{{ route('dev-superadmin.system-updates.report', $update) }}">من اطلع؟</a>
                                        <form method="POST" action="{{ route('dev-superadmin.system-updates.destroy', $update) }}" onsubmit="return confirm('حذف هذا التحديث نهائيًا من صفحة التحديثات لدى كل الموظفين؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-small btn-danger" type="submit">حذف</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($systemUpdates->hasPages())
                <div style="margin-top:14px">{{ $systemUpdates->links() }}</div>
            @endif
        @endif
    </div>
</section>

<div class="su-grid-2" style="margin-top:18px">
    <section class="panel" id="system-updates-settings" style="margin-top:0">
        <div class="panel-head">
            <h2 class="panel-title">العرض ومصدر التحديثات</h2>
            <p class="panel-subtitle">حدد من أي نسخة تُسحب التحديثات تلقائيًا. التحديثات المسحوبة تظهر هنا بعلامة «مصدر خارجي» وتُعدَّل من نسخة المصدر فقط.</p>
        </div>
        <div class="panel-body">
            <form method="POST" action="{{ route('dev-superadmin.system-updates.settings') }}">
                @csrf
                @method('PUT')
                <h4 class="su-subhead">ما يراه الموظفون في هذه النسخة</h4>
                <div class="su-field">
                    <label class="switch-row su-switch" for="suBannerEnabled">
                        <span>إظهار شريط التحديثات أعلى لوحة التحكم</span>
                        <span class="switch">
                            <input id="suBannerEnabled" name="banner_enabled" type="checkbox" value="1" {{ old('banner_enabled', $suSettings['banner_enabled']) ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </span>
                    </label>
                    <p class="su-hint">إن أوقفته تبقى صفحة التحديثات وأيقونة 📣 متاحتين، لكن بدون الشريط الملوّن.</p>
                </div>
                <div class="su-field">
                    <label for="suBannerDays">مدة بقاء التحديث في الشريط (بالأيام)</label>
                    <p class="su-hint">بعدها يختفي التحديث من الشريط حتى لمن لم يضغط «تم الاطلاع»، ويبقى في صفحة التحديثات. تُطبَّق على التحديثات الجديدة.</p>
                    <input id="suBannerDays" name="banner_days" type="number" min="1" max="365" required value="{{ old('banner_days', $suSettings['banner_days']) }}">
                </div>

                <h4 class="su-subhead">سحب التحديثات من نسخة أخرى</h4>
                <p class="su-hint" style="margin:-4px 0 12px">اتركه مغلقًا في نسختكم الرئيسية التي تكتبون فيها التحديثات. فعّله في نسخ العملاء.</p>
                <div class="su-field">
                    <label class="switch-row su-switch" for="suSyncEnabled">
                        <span>تفعيل السحب التلقائي</span>
                        <span class="switch">
                            <input id="suSyncEnabled" name="sync_enabled" type="checkbox" value="1" {{ old('sync_enabled', $suSettings['sync_enabled']) ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </span>
                    </label>
                </div>
                <div class="su-field">
                    <label for="suSourceUrl">رابط نسخة المصدر</label>
                    <p class="su-hint">دومين النسخة التي تُكتب فيها التحديثات فقط، مثل <span class="su-code">https://erp.example.com</span> — بدون /api أو /dashboard.</p>
                    <input id="suSourceUrl" name="source_url" type="url" dir="ltr" placeholder="https://erp.example.com" value="{{ old('source_url', $suSettings['source_url']) }}">
                    @error('source_url')<p class="su-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="su-field">
                    <label for="suSourceKey">مفتاح الربط</label>
                    <p class="su-hint">يبدأ بـ <span class="su-code">su_</span> ويُنشأ من قسم «النسخ المرتبطة» في نسخة المصدر. يُحفظ هنا مشفّرًا ولا يظهر مرة أخرى.</p>
                    <input id="suSourceKey" name="source_key" type="password" dir="ltr" autocomplete="new-password" placeholder="{{ $suSettings['has_source_key'] ? '•••••••• محفوظ — اتركه فارغًا للإبقاء عليه' : 'su_...' }}">
                    @if ($suSettings['has_source_key'])
                        <label style="display:flex; gap:6px; align-items:center; font-size:.78rem; color:var(--danger); font-weight:600">
                            <input type="checkbox" name="clear_source_key" value="1"> حذف المفتاح المحفوظ
                        </label>
                    @endif
                </div>
                <div class="su-field">
                    <label for="suInterval">فحص وجود تحديثات جديدة كل</label>
                    <p class="su-hint">كل دقيقة = يصل التحديث للعميل خلال دقيقة من نشره. الطلب خفيف جدًا عندما لا يوجد جديد. يتطلب أن يكون Scheduler يعمل على السيرفر، ومع ذلك يُسحب احتياطيًا عند فتح الموظفين للوحة.</p>
                    <select id="suInterval" name="sync_interval_minutes">
                        @foreach (\App\Support\SystemUpdatesSettings::INTERVAL_OPTIONS as $minutes)
                            <option value="{{ $minutes }}" {{ (int) old('sync_interval_minutes', $suSettings['sync_interval_minutes']) === $minutes ? 'selected' : '' }}>
                                {{ match (true) { $minutes === 1 => 'دقيقة (موصى به)', $minutes < 60 => $minutes . ' دقيقة', $minutes === 60 => 'ساعة', $minutes === 1440 => 'يوم', default => ($minutes / 60) . ' ساعات' } }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-actions" style="justify-content:space-between; flex-wrap:wrap; margin-top:18px">
                    <button class="btn" type="submit" formaction="{{ route('dev-superadmin.system-updates.test-connection') }}" formmethod="POST">اختبار الاتصال</button>
                    <button class="btn btn-primary" type="submit">حفظ وسحب الآن</button>
                </div>
            </form>

            <div class="su-state">
                <div><span>آخر مزامنة ناجحة</span><strong>{{ $formatDate($suState['last_synced_at']) }}</strong></div>
                <div><span>آخر محاولة</span><strong>{{ $formatDate($suState['last_attempt_at']) }}</strong></div>
                <div><span>وصل في آخر مزامنة</span><strong>{{ (int) $suState['last_received'] }}</strong></div>
            </div>
            @if ($suState['last_error'])
                <div class="su-error"><b>آخر خطأ:</b> {{ $suState['last_error'] }}</div>
            @endif

            @if ($suSettings['source_url'] !== '' && $suSettings['has_source_key'])
                <div class="form-actions" style="margin-top:14px; justify-content:flex-start; flex-wrap:wrap">
                    <form method="POST" action="{{ route('dev-superadmin.system-updates.sync') }}">
                        @csrf
                        <button class="btn btn-primary btn-small" type="submit">مزامنة الآن</button>
                    </form>
                    <form method="POST" action="{{ route('dev-superadmin.system-updates.sync') }}">
                        @csrf
                        <input type="hidden" name="full" value="1">
                        <button class="btn btn-small" type="submit" title="يتجاهل آخر مزامنة ويسحب كل التحديثات المنشورة من جديد">مزامنة كاملة</button>
                    </form>
                </div>
            @endif
        </div>
    </section>

    <section class="panel" id="system-updates-clients" style="margin-top:0">
        <div class="panel-head">
            <h2 class="panel-title">النسخ المرتبطة بهذه النسخة</h2>
            <p class="panel-subtitle">لتصبح هذه النسخة مصدرًا: أنشئ مفتاحًا لكل عميل، وضع في نسخته رابط <span class="su-code">{{ rtrim((string) config('app.url'), '/') }}</span> مع المفتاح. التحديثات التي تختار مشاركتها فقط هي ما يصل لهم.</p>
        </div>
        <div class="panel-body">
            @if ($issuedToken)
                <div class="su-error" style="background:var(--success-soft); border-color:var(--success-line); color:var(--success)">
                    <b>مفتاح «{{ session('system_update_client_name') }}»</b> — انسخه الآن، لن يظهر مرة أخرى:
                    <div class="su-token">
                        <code id="suIssuedToken">{{ $issuedToken }}</code>
                        <button class="btn btn-small" type="button" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('suIssuedToken').textContent).then(() => { this.textContent = 'تم النسخ'; })">نسخ</button>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('dev-superadmin.system-updates.clients.store') }}" style="display:flex; gap:8px; align-items:end; margin-top:{{ $issuedToken ? '14px' : '0' }}">
                @csrf
                <div class="su-field" style="flex:1">
                    <label for="suClientName">اسم العميل / النسخة</label>
                    <p class="su-hint">اسم تتعرف به على العميل في هذه القائمة. بعد الإنشاء انسخ المفتاح وضعه في إعدادات نسخة العميل.</p>
                    <input id="suClientName" name="name" type="text" required maxlength="160" placeholder="مثال: مكتب الرافدين">
                </div>
                <button class="btn btn-primary" type="submit">إنشاء مفتاح</button>
            </form>

            @if ($systemUpdateClients->isEmpty())
                <div class="su-empty" style="margin-top:14px">لا توجد نسخ مرتبطة. لن يستطيع أحد سحب تحديثات هذه النسخة قبل إنشاء مفتاح.</div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>النسخة</th><th>آخر سحب</th><th>الحالة</th><th></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($systemUpdateClients as $client)
                                <tr>
                                    <td>
                                        <div class="su-title">{{ $client->name }}</div>
                                        <div class="su-sub su-code">{{ $client->token_prefix }}…</div>
                                    </td>
                                    <td>
                                        <div style="font-size:.8rem">{{ $client->last_synced_at ? $client->last_synced_at->diffForHumans() : 'لم يسحب بعد' }}</div>
                                        @if ($client->last_ip)<div class="su-sub su-code">{{ $client->last_ip }}</div>@endif
                                    </td>
                                    <td><span class="su-pill {{ $client->is_active ? 'is-good' : '' }}">{{ $client->is_active ? 'مفعّل' : 'موقوف' }}</span></td>
                                    <td>
                                        <div class="su-actions">
                                            <form method="POST" action="{{ route('dev-superadmin.system-updates.clients.toggle', $client) }}">
                                                @csrf
                                                @method('PUT')
                                                <button class="btn btn-small" type="submit">{{ $client->is_active ? 'إيقاف' : 'تفعيل' }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('dev-superadmin.system-updates.clients.destroy', $client) }}" onsubmit="return confirm('حذف المفتاح؟ لن تستطيع هذه النسخة سحب التحديثات.');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-small btn-danger" type="submit">حذف</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
</div>
