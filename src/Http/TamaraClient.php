<?php

namespace YasserElgammal\Tamara\Http;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;
use YasserElgammal\Tamara\Exceptions\ApiException;

final class TamaraClient
{
    public function __construct(private readonly Factory $http) {}
    /** @param array<string,mixed> $query @return array<string,mixed> */
    public function get(string $path,array $query=[]): array { return $this->send('get',$path,$query); }
    /** @param array<string,mixed> $payload @return array<string,mixed> */
    public function post(string $path,array $payload=[]): array { return $this->send('post',$path,$payload); }
    /** @param array<string,mixed> $data @return array<string,mixed> */
    private function send(string $method,string $path,array $data): array
    {
        $base=(string)config('tamara.api_url') ?: (config('tamara.environment')==='production' ? 'https://api.tamara.co' : 'https://api-sandbox.tamara.co');
        $request=$this->http->withToken(trim((string)config('tamara.api_token')," \t\n\r\0\x0B\"'"))->acceptJson()->asJson()->timeout((int)config('tamara.timeout',15));
        /** @var Response $response */ $response=$request->{$method}(rtrim($base,'/').'/'.ltrim($path,'/'),$data);
        if (! $response->successful()) { $body=$response->json() ?: ['body'=>$response->body()]; throw new ApiException((string)($body['message'] ?? 'Tamara API request failed.'),$response->status(),$body); }
        return $response->json() ?: [];
    }
}
