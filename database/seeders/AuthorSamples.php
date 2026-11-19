<?php

namespace Database\Seeders;

class AuthorSamples
{
    /**
     * Common English author names.
     *
     * @return array<int, array{name: string, email: string}>
     */
    public static function all(): array
    {
        return [
            ['name' => 'James Smith', 'email' => 'james.smith@example.com'],
            ['name' => 'Mary Johnson', 'email' => 'mary.johnson@example.com'],
            ['name' => 'John Williams', 'email' => 'john.williams@example.com'],
            ['name' => 'Sarah Brown', 'email' => 'sarah.brown@example.com'],
            ['name' => 'Michael Davis', 'email' => 'michael.davis@example.com'],
            ['name' => 'Emily Wilson', 'email' => 'emily.wilson@example.com'],
        ];
    }
}
