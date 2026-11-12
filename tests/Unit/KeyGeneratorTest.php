<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\KeyGenerator;
use PHPUnit\Framework\TestCase;

final class KeyGeneratorTest extends TestCase
{
    public function test_it_builds_prefixed_groups_without_ambiguous_characters(): void
    {
        $key = (new KeyGenerator())->make('AALC', 4, 4);

        $this->assertMatchesRegularExpression('/^AALC(-[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{4}){4}$/', $key);
    }

    public function test_generated_keys_are_not_repeated(): void
    {
        $generator = new KeyGenerator();
        $keys = array_map(static fn (): string => $generator->make('LIC', 3, 4), range(1, 200));

        $this->assertCount(200, array_unique($keys));
    }
}
