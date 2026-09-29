<?php

namespace App\Services\Contracts;

interface PushNotifier
{
    /**
     * @param  list<string>  $tokens
     * @param  array<string, string>  $data  delivered to the app (code, order_id) so it can re-translate
     * @return list<string> tokens the provider reported as no longer valid
     */
    public function send(array $tokens, string $title, string $body, array $data = []): array;
}
