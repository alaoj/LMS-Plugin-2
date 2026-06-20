<?php

declare(strict_types=1);

namespace Zadora\Lms\Support;

use RuntimeException;

final class Container
{
    /** @var array<string, callable> */
    private array $bindings = [];

    /** @var array<string, object> */
    private array $instances = [];

    public function singleton(string $id, callable $factory): void
    {
        $this->bindings[$id] = $factory;
    }

    public function instance(string $id, object $instance): void
    {
        $this->instances[$id] = $instance;
    }

    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (! isset($this->bindings[$id])) {
            throw new RuntimeException(sprintf('Service [%s] is not registered.', $id));
        }

        $this->instances[$id] = ($this->bindings[$id])($this);

        return $this->instances[$id];
    }
}
