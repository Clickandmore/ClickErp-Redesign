<!-- Modal: تحديث الحالة -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" role="dialog" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStatusModalLabel">تحديث الحالة</h5>
                <button type="button" class="close btn" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="rowId" name="rowId">
                <input type="hidden" id="status" name="status">
                <div class="form-group">
                    <label for="statusDate">تاريخ الحالة:</label>
                    <input type="date" id="statusDate" required name="statusDate" class="form-control" value="{{date('Y-m-d')}}">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                <button type="button" class="btn btn-primary" id="saveStatusBtn">حفظ</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: تأكيد الإرجاع -->
<div class="modal fade" id="confirmBackModal" tabindex="-1" role="dialog" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStatusModalLabel">ارجاع العقد داخل الإيواء</h5>
                <button type="button" class="close btn" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="contract_id" name="id">
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle"></i>
                    هل انت متأكد من ارجاع العقد داخل الإيواء ؟
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                <button type="button" class="btn btn-danger" id="confirmBackBtn">تأكيد الإرجاع</button>
            </div>
        </div>
    </div>
</div>
