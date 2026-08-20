<?php

namespace YasserElgammal\Tamara\Webhooks;

final class WebhookSignature
{
    public function verify(?string $token): bool
    {
        if (! config('tamara.webhook.verify',true)) return true;
        $secret=(string)config('tamara.notification_token');
        if ($secret==='' || !is_string($token) || $token==='') return false;
        $parts=explode('.',$token);
        if (count($parts)!==3) return false;
        [$encodedHeader,$encodedPayload,$encodedSignature]=$parts;
        $header=json_decode($this->decode($encodedHeader),true);
        $payload=json_decode($this->decode($encodedPayload),true);
        if (!is_array($header)||($header['alg']??null)!=='HS256'||!is_array($payload)) return false;
        $expected=$this->encode(hash_hmac('sha256',$encodedHeader.'.'.$encodedPayload,$secret,true));
        return hash_equals($expected,$encodedSignature);
    }
    private function decode(string $value): string|false { return base64_decode(strtr($value,'-_','+/').str_repeat('=',(4-strlen($value)%4)%4),true); }
    private function encode(string $value): string { return rtrim(strtr(base64_encode($value),'+/','-_'),'='); }
}
