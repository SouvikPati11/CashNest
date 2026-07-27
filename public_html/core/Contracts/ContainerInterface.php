<?php

declare(strict_types=1);

namespace Core\Contracts;

/**
 * Minimal service-container contract.
 *
 * Intentionally close to PSR-11 (`get`/`has`) plus binding helpers so the rest
 * of the framework can depend on the abstraction rather than the concrete
 * implementation (Dependency Inversion).
 */
interface ContainerInterface
{
    /**
     * Register a factory binding. The factory receives the container.
     *
     * @param callable(ContainerInterface): mixed $factory
     */
    public function bind(string $id, callable $factory): void;

    /**
     * Register a shared (singleton) binding, resolved once and cached.
     *
     * @param callable(ContainerInterface): mixed $factory
     */
    public function singleton(string $id, callable $factory): void;

    /**
     * Store an already-created instance under an id.
     */
    public function instance(string $id, mixed $instance): void;

    /**
     * Resolve an entry from the container.
     */
    public function get(string $id): mixed;

    /**
     * Whether an entry is registered.
     */
    public function has(string $id): bool;
}
