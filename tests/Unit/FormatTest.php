<?php

namespace Tests\Unit;

use App\Support\Format;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FormatTest extends TestCase
{
    public function test_numbers_use_indonesian_separators(): void
    {
        $this->assertSame('12.480', Format::number(12480));
        $this->assertSame('8,9', Format::number(8.94, 1));
        $this->assertSame('+8,9%', Format::delta(8.94));
        $this->assertSame('−3%', Format::delta(-3.0));
        $this->assertSame('1,5 rb', Format::compact(1500));
        $this->assertSame('950', Format::compact(950));
        $this->assertSame('08.00', Format::clock(480));
    }

    public function test_whatsapp_numbers_become_international_links(): void
    {
        $this->assertSame('https://wa.me/6281200000000', Format::whatsappLink('0812-0000-0000'));
        $this->assertSame('https://wa.me/6281200000000', Format::whatsappLink('+62 812 0000 0000'));
        $this->assertNull(Format::whatsappLink(''));
    }

    #[DataProvider('names')]
    public function test_given_names_keep_honorifics(string $full, string $given): void
    {
        $this->assertSame($given, Format::givenName($full));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function names(): array
    {
        return [
            'buton noble title' => ['Wa Ode Sitti Rahmawati', 'Wa Ode Sitti'],
            'la ode' => ['La Ode Fajar', 'La Ode Fajar'],
            'bugis' => ['Andi Pratama Putra', 'Andi Pratama'],
            'abbreviated' => ['Muh. Rizal', 'Muh. Rizal'],
            'plain' => ['Nur Aisyah', 'Nur'],
            'single word' => ['Hasrul', 'Hasrul'],
        ];
    }
}
