<?php

declare(strict_types=1);

it('usa o database isolado spa_base_test', function (): void {
    expect(config('database.default'))->toBe('pgsql');
    expect(config('database.connections.pgsql.database'))->toBe('spa_base_test');
});
