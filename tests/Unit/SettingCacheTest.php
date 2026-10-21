<?php

namespace Tests\Unit;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The testing environment defaults CACHE_STORE to "array", which never
        // actually serializes — it wouldn't have caught the original bug (caching
        // a Collection broke specifically on the "database" driver's real
        // serialize/unserialize round-trip). Force the real driver here.
        config(['cache.default' => 'database']);
    }

    public function test_cached_value_survives_a_second_read_from_a_fresh_cache_entry(): void
    {
        Setting::create(['key' => 'contact_mobile', 'value' => '09174287445']);

        // First call populates the cache; the second reads it back — this is
        // exactly what broke when the cache stored a Collection object instead
        // of a plain array (serialization boundary issue).
        $this->assertSame('09174287445', Setting::get('contact_mobile'));
        $this->assertSame('09174287445', Setting::get('contact_mobile'));
    }

    public function test_missing_key_returns_the_given_default(): void
    {
        $this->assertSame('fallback', Setting::get('does_not_exist', 'fallback'));
    }

    public function test_saving_a_setting_invalidates_the_cache(): void
    {
        $setting = Setting::create(['key' => 'contact_mobile', 'value' => 'old']);
        Setting::get('contact_mobile'); // warm the cache

        $setting->update(['value' => 'new']);

        $this->assertSame('new', Setting::get('contact_mobile'));
    }
}
