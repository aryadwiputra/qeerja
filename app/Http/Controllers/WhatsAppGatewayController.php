<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsAppGatewayController extends Controller
{
    public function status(Request $request, string $workspaceSlug, WhatsAppGatewayService $gateway): JsonResponse
    {
        return response()->json($gateway->status());
    }

    public function connect(Request $request, string $workspaceSlug, WhatsAppGatewayService $gateway): JsonResponse
    {
        return response()->json($gateway->status());
    }

    public function disconnect(Request $request, string $workspaceSlug, WhatsAppGatewayService $gateway): JsonResponse
    {
        $gateway->disconnect();

        return response()->json(['ok' => true]);
    }
}
