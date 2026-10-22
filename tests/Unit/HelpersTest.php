<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    public static function amounts(): array
    {
        return [
            ['1250', 125000], ['1,250.50', 125050], ['₱ 99', 9900], ['0.5', 50], ['0.05', 5],
            ['-12.30', -1230], ['', null], ['abc', null], ['1.234', null], ['12..3', null],
        ];
    }

    #[DataProvider('amounts')]
    public function test_to_cents_parses_user_input(string $input, ?int $expected): void
    {
        $this->assertSame($expected, to_cents($input));
    }

    public function test_money_formats_centavos(): void
    {
        $this->assertSame('₱11,950.00', money(1195000));
        $this->assertSame('-₱7.60', money(-760));
        $this->assertSame('30,240.00', money(3024000, false));
    }

    public function test_escape_helper_escapes_quotes_and_tags(): void
    {
        $this->assertSame('&lt;b&gt; &quot;x&quot; &#039;y&#039;', e('<b> "x" \'y\''));
    }
}
