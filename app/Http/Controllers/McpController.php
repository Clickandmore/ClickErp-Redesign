<?php

namespace App\Http\Controllers;

use App\Mcp\McpServer;
use Illuminate\Http\Request;

class McpController extends Controller
{
    public function handle(Request $request, McpServer $server)
    {
        $message = json_decode($request->getContent(), true);

        if (!is_array($message)) {
            return response()->json($server->error(null, -32700, 'Parse error'), 400);
        }

        // الدفعات (JSON-RPC batch) أُلغيت من البروتوكول منذ 2025-06-18.
        if (array_is_list($message)) {
            return response()->json($server->error(null, -32600, 'Batch requests are not supported'), 400);
        }

        $response = $server->handle(
            $message,
            auth('admin')->user(),
            $request->attributes->get('mcp_token'),
            $request->ip()
        );

        if ($response === null) {
            return response('', 202);
        }

        return response()->json($response, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** لا يوجد بث SSE من الخادم؛ البروتوكول يطلب 405 في هذه الحالة. */
    public function methodNotAllowed()
    {
        return response('', 405)->header('Allow', 'POST');
    }
}
