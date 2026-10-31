<?php

namespace Tests\Unit;

use App\Support\IranianMobile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class IranianMobileTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function sameNumberWrittenDifferently(): array
    {
        return [
            'canonical' => ['09121234567'],
            'persian digits' => ['۰۹۱۲۱۲۳۴۵۶۷'],
            'arabic digits' => ['٠٩١٢١٢٣٤٥٦٧'],
            'spaces' => ['0912 123 4567'],
            'dashes' => ['0912-123-4567'],
            'plus 98' => ['+989121234567'],
            'plus 98 with space' => ['+98 912 123 4567'],
            '0098' => ['00989121234567'],
            '98 without plus' => ['989121234567'],
            'missing leading zero' => ['9121234567'],
            'parentheses' => ['(0912) 1234567'],
        ];
    }

    #[DataProvider('sameNumberWrittenDifferently')]
    public function test_every_common_spelling_normalizes_to_the_canonical_form(string $input): void
    {
        $this->assertSame('09121234567', IranianMobile::normalize($input));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function notAMobileNumber(): array
    {
        return [
            'landline' => ['07143334444'],
            'too short' => ['0912123'],
            'too long' => ['091212345678'],
            'letters' => ['0912abc4567'],
        ];
    }

    #[DataProvider('notAMobileNumber')]
    public function test_non_mobile_input_does_not_match_the_pattern(string $input): void
    {
        $this->assertDoesNotMatchRegularExpression(IranianMobile::PATTERN, IranianMobile::normalize($input));
    }
}
