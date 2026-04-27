<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Services\StockStatusService;

class ItemAmountInStockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(request $request,StockStatusService $stockStatusService)
    {
        $stockId = $request->get('stock_id');
        $itemId = $request->get('item_id');

        // Fetch the total amount for the item in the stock
        $amount = $stockStatusService->getWhere([
            ['item_id', $itemId],
            ['fn_stock_id', $stockId]
        ])->sum('amount');

        if ($amount) {
            return response()->json(['amount' => $amount]);
        } else {
            return response()->json(['amount' => 0]); // Return 0 if no record found
        }
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
