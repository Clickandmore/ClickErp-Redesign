<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPendingOrder
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // التحقق من وجود معامل redirect في URL
        if ($request->has('redirect') && $request->get('redirect') === 'order_confirmation') {
            // حفظ معامل redirect في الجلسة
            session(['redirect_after_login' => 'order_confirmation']);
        }

        // التحقق من وجود بيانات طلب محفوظة في الجلسة
        if (session()->has('pending_order_data')) {
            $pendingOrder = session('pending_order_data');

            // إزالة البيانات من الجلسة
            session()->forget('pending_order_data');

            // إعادة التوجيه إلى صفحة تأكيد الطلب
            return redirect()->route('web.maids.show', [
                'maid' => $pendingOrder['maid_id'],
                'order_type' => $pendingOrder['order_type']
            ])->with('restore_order_data', $pendingOrder);
        }

        return $next($request);
    }
}
