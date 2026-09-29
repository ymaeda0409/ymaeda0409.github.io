<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PhoneNumberTest extends TestCase
{
    #[DataProvider('numbers')]
    public function test_normalizes_to_e164(string $input, ?string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::normalize($input));
    }

    public static function numbers(): array
    {
        return [
            'local with leading zero' => ['0991234567', '+265991234567'],
            'national without zero' => ['991234567', '+265991234567'],
            'international without plus' => ['265991234567', '+265991234567'],
            'international with plus and spaces' => ['+265 99 123 4567', '+265991234567'],
            'double zero prefix' => ['00265991234567', '+265991234567'],
            'foreign number' => ['+81 90 1234 5678', '+819012345678'],
            'garbage' => ['abc', null],
            'too short' => ['12', null],
        ];
    }
}
