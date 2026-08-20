<?php

namespace YasserElgammal\Tamara\Tests\Feature;

use Illuminate\Support\Facades\Event;
use YasserElgammal\Tamara\Events\TamaraOrderApproved;
use YasserElgammal\Tamara\Tests\TestCase;

final class WebhookTest extends TestCase
{
    /** @return array<string,mixed> */ private function payload(string $event='order_approved'): array{return ['order_id'=>'o-1','order_reference_id'=>'ORDER-1','order_number'=>'100','event_type'=>$event,'data'=>[]];}
    private function token(): string { $encode=fn(string $v)=>rtrim(strtr(base64_encode($v),'+/','-_'),'='); $header=$encode(json_encode(['typ'=>'JWT','alg'=>'HS256'],JSON_THROW_ON_ERROR)); $payload=$encode(json_encode(['iss'=>'tamara','iat'=>time()],JSON_THROW_ON_ERROR)); return $header.'.'.$payload.'.'.$encode(hash_hmac('sha256',$header.'.'.$payload,'notification-secret',true)); }
    public function test_valid_webhook_dispatches_typed_event(): void { Event::fake(); $this->postJson('/webhooks/tamara',$this->payload(),['Authorization'=>'Bearer '.$this->token()])->assertOk()->assertJson(['success'=>true,'duplicate'=>false]); Event::assertDispatched(TamaraOrderApproved::class,fn($e)=>$e->orderId()==='o-1'&&$e->referenceId()==='ORDER-1'); }
    public function test_query_token_is_supported(): void { $this->postJson('/webhooks/tamara?tamaraToken='.urlencode($this->token()),$this->payload())->assertOk(); }
    public function test_invalid_token_is_rejected(): void { $this->postJson('/webhooks/tamara',$this->payload(),['Authorization'=>'Bearer invalid'])->assertUnauthorized(); }
    public function test_missing_token_is_rejected(): void { $this->postJson('/webhooks/tamara',$this->payload())->assertUnauthorized(); }
    public function test_unsupported_event_and_malformed_payload_are_rejected(): void { $this->postJson('/webhooks/tamara',$this->payload('unknown'),['Authorization'=>'Bearer '.$this->token()])->assertStatus(422); $this->postJson('/webhooks/tamara',['event_type'=>'order_approved'],['Authorization'=>'Bearer '.$this->token()])->assertStatus(422); }
    public function test_duplicate_event_is_idempotent(): void { $headers=['Authorization'=>'Bearer '.$this->token()]; $this->postJson('/webhooks/tamara',$this->payload(),$headers)->assertOk(); $this->postJson('/webhooks/tamara',$this->payload(),$headers)->assertStatus(202)->assertJson(['duplicate'=>true]); }
}
