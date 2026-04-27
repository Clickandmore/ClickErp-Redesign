<?php

namespace Nami\Hr\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Nami\Finance\Services\YearService;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveYear
{
    protected YearService $yearService;

    public function __construct(YearService $yearService)
    {
        $this->yearService = $yearService;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $activeYear = $this->yearService->getActiveYear();
        if (!$activeYear) {
            if ($request->ajax()) {
               return  redirect()->route('finance.years.create', ['year' => 'true']);
            }
            return redirect()->route('finance.years.create', ['year' => 'true']);
        }

        return $next($request);
    }
}
