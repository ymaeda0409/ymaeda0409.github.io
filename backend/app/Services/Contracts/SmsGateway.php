<?php

namespace App\Services\Contracts;

interface SmsGateway
{
    /**
     * Sends a text message. Implementations throw on delivery failure so the job can retry.
     */
    public function send(string $to, string $message): void;
}
