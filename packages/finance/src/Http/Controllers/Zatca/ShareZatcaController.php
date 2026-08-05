<?php

namespace Nami\Finance\Http\Controllers\Zatca;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Services\ShareInvoiceZatcaService;

class ShareZatcaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request,ShareInvoiceZatcaService $service)
    {
        $validated = $request->validate(['id' => ['required', 'integer', 'exists:fn_sales,id']]);

        try {
            return response()->json([
                'message' => 'تمت مشاركة الفاتورة مع هيئة الزكاة بنجاح.',
                'invoice' => $service->shareInvoice($validated['id']),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
