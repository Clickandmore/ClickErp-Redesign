<form class="from-submit-global" method="POST" action="{{ route('offices.users.update', $user->id) }}">
    @csrf
    @method('PUT')

    <div class="alert alert-info">
        هذه هي بيانات دخول مستخدم الوكيل. لن يتم تغيير بقية بيانات الحساب أو صلاحياته.
    </div>

    <div class="row g-3">
        <div class="col-12">
            <label for="agent_username" class="form-label">اسم المستخدم</label>
            <input type="text" id="agent_username" name="username" class="form-control"
                value="{{ old('username', $user->username) }}" autocomplete="username" required>
            <div class="text-danger small mt-1" data-field="username"></div>
        </div>

        <div class="col-12">
            <label for="agent_email" class="form-label">البريد الإلكتروني</label>
            <input type="email" id="agent_email" name="email" class="form-control"
                value="{{ old('email', $user->email) }}" autocomplete="email" required>
            <div class="text-danger small mt-1" data-field="email"></div>
        </div>

        <div class="col-12">
            <label for="agent_password" class="form-label">كلمة المرور</label>
            <input type="password" id="agent_password" name="password" class="form-control"
                autocomplete="new-password" placeholder="اتركها فارغة للإبقاء على كلمة المرور الحالية">
            <div class="form-text">6 أحرف على الأقل عند إدخال كلمة مرور جديدة.</div>
            <div class="text-danger small mt-1" data-field="password"></div>
        </div>
    </div>

    <div class="modal-footer px-0 pb-0 mt-4">
        <button class="btn btn-primary" type="submit">
            <i class="fa fa-save"></i>
            حفظ
        </button>
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">إغلاق</button>
    </div>
</form>
