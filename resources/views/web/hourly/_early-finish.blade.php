{{-- الإنهاء المبكر: العاملة أنهت قبل اكتمال الوقت المحجوز، والعميل يؤكد أو يعترض خلال المهلة.
     يُرسل بنفس آلية نماذج المعالجة (data-visit-recovery في hourly-visit-recovery.js). يتطلب: $earlyFinishes و$order و$visit --}}
@foreach($earlyFinishes as $earlyFinish)
    <div class="hr-notice od-early" role="status">
        <p><b>انتهت الزيارة قبل وقتها.</b>
            {{ $earlyFinish['worker_name'] ? 'أنهت العاملة '.$earlyFinish['worker_name'] : 'أنهت العاملة' }} العمل بعد <b>{{ $earlyFinish['worked_label'] }}</b> من <b>{{ $earlyFinish['planned_label'] }}</b> المحجوزة.</p>
        @if($earlyFinish['can_respond'])
            <p>هل كان الإنهاء بطلبك؟ يمكنك الرد حتى <bdi>{{ \Carbon\Carbon::parse($earlyFinish['respond_until'])->format('H:i') }}</bdi>.</p>
            <div class="od-early-actions">
                <form data-visit-recovery method="post" action="{{ route('web.hourly.visit.early-finish', [$order->id, $visit->id, $earlyFinish['id']]) }}">
                    @csrf<input type="hidden" name="response" value="confirmed">
                    <button type="submit" class="hr-btn secondary sm"><i class="fa-light fa-circle-check"></i> نعم، كان بطلبي</button><p data-recovery-feedback role="status"></p>
                </form>
                <details>
                    <summary>لا، لم يكن بطلبي</summary>
                    <form data-visit-recovery method="post" action="{{ route('web.hourly.visit.early-finish', [$order->id, $visit->id, $earlyFinish['id']]) }}">
                        @csrf<input type="hidden" name="response" value="disputed">
                        <label>أخبرنا بما حدث (اختياري)<textarea class="form-control" name="note" maxlength="1000"></textarea></label>
                        <button type="submit" class="hr-btn danger sm">إرسال الاعتراض للإدارة</button><p data-recovery-feedback role="status"></p>
                    </form>
                </details>
            </div>
        @else
            <p class="hr-muted">{{ [
                'confirmed' => 'أكدتَ أن الإنهاء كان بطلبك.',
                'disputed' => 'وصل اعتراضك، وتراجع الإدارة الزيارة وستتواصل معك بالقرار.',
                'no_response' => 'انتهت مهلة الرد، وأُحيلت الزيارة لمراجعة الإدارة.',
                'resolved' => 'راجعت الإدارة الإنهاء المبكر واتخذت القرار.',
            ][$earlyFinish['status']] ?? '' }}</p>
        @endif
    </div>
@endforeach
