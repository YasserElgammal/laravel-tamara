<?php

namespace YasserElgammal\Tamara\Webhooks;

use Illuminate\Http\{JsonResponse,Request};
use YasserElgammal\Tamara\DTOs\WebhookData;
use YasserElgammal\Tamara\Exceptions\ValidationException;

final class WebhookController
{
    public function __invoke(Request $request,WebhookSignature $signature,WebhookHandler $handler): JsonResponse
    {
        $token=$request->bearerToken() ?: $request->query('tamaraToken');
        if (! $signature->verify(is_string($token)?$token:null)) return response()->json(['error'=>'Invalid Tamara notification token.'],401);
        try { $data=WebhookData::fromArray($request->all()); } catch (ValidationException $e) { return response()->json(['error'=>$e->getMessage()],422); }
        $processed=$handler->handle($data);
        return response()->json(['success'=>true,'duplicate'=>!$processed,'order_id'=>$data->orderId],$processed?200:202);
    }
}
