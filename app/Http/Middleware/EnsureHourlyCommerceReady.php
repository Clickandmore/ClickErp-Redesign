<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\Schema;
class EnsureHourlyCommerceReady
{
    public function handle($request, Closure $next)
    {
        foreach (['hourly_packages','hourly_package_translations','hourly_package_city','hourly_package_nationality','hourly_rental_orders','hourly_visits','hourly_order_payments','hourly_order_status_logs'] as $table) {
            abort_unless(Schema::hasTable($table),503,'لم تكتمل تهيئة جداول التأجير بالساعة بعد.');
        }
        return $next($request);
    }
}
