<?php

namespace Tests\Unit;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_and_reads_typed_values(): void
    {
        Setting::setValue('a.string', 'hello');
        Setting::setValue('a.int', 123);
        Setting::setValue('a.bool', true);
        Setting::setValue('a.array', ['x' => 1]);

        $this->assertSame('hello', Setting::getValue('a.string'));
        $this->assertSame(123, Setting::getValue('a.int'));
        $this->assertTrue(Setting::getValue('a.bool'));
        $this->assertSame(['x' => 1], Setting::getValue('a.array'));
    }

    public function test_it_encrypts_values_when_requested(): void
    {
        Setting::setValue('secret', ['token' => 'abc'], encrypt: true);

        $row = Setting::query()->where('key', 'secret')->firstOrFail();
        $this->assertTrue($row->is_encrypted);
        $this->assertNotSame(json_encode(['token' => 'abc']), $row->value);

        $this->assertSame(['token' => 'abc'], Setting::getValue('secret'));
    }
}

