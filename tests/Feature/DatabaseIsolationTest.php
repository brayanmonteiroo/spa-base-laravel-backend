<?php

declare(strict_types=1);

it('uses the isolated spa_base_test database', function (): void {
    expect(config('database.default'))->toBe('pgsql');
    expect(config('database.connections.pgsql.database'))->toBe('spa_base_test');
});
