<?php

declare(strict_types=1);

namespace Core;

use Core\Contracts\ContainerInterface;
use Core\Exceptions\ContainerException;

/**
 * Lightweight dependency-injection container.
 *
 * Supports factory bindings, shared singletons, pre-built instances, and
 * best-effort autowiring of concrete classes via reflection. Kept deliberately
 * small so it runs comfortably on shared hosting with no external dependencies.
 */
final class Container implements ContainerInterface
{
    /** @var array<string, callable(ContainerInterface): mixed> */
    private array $bindings = [];

    /** @var array<string, bool> */
    private array $shared = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    public function bind(string $id, callable $factory): void
    {
        $this->bindings[$id] = $factory;
        $this->shared[$id]   = false;
        unset($this->instances[$id]);
    }

    public function singleton(string $id, callable $factory): void
    {
        $this->bindings[$id] = $factory;
        $this->shared[$id]   = true;
        unset($this->instances[$id]);
    }

    public function instance(string $id, mixed $instance): void
    {
        $this->instances[$id] = $instance;
        $this->shared[$id]    = true;
    }

    public function has(string $id): bool
    {
        return isset($this->bindings[$id])
            || array_key_exists($id, $this->instances)
            || class_exists($id);
    }

    public function get(string $id): mixed
    {
        // Already-resolved shared instance.
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        // Explicit binding.
        if (isset($this->bindings[$id])) {
            $object = ($this->bindings[$id])($this);

            if (!empty($this->shared[$id])) {
                $this->instances[$id] = $object;
            }

            return $object;
        }

        // Fall back to autowiring a concrete class.
        if (class_exists($id)) {
            return $this->build($id);
        }

        throw new ContainerException(sprintf('No container binding found for "%s".', $id));
    }

    /**
     * Autowire a concrete class by resolving its constructor dependencies.
     *
     * @param class-string $class
     */
    private function build(string $class): object
    {
        // $class is a verified class-string (callers guard with class_exists),
        // so ReflectionClass never throws here.
        $reflector = new \ReflectionClass($class);

        if (!$reflector->isInstantiable()) {
            throw new ContainerException(sprintf('Class "%s" is not instantiable.', $class));
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $arguments[] = $this->resolveParameter($class, $parameter);
        }

        return $reflector->newInstanceArgs($arguments);
    }

    /**
     * Resolve a single constructor parameter.
     */
    private function resolveParameter(string $class, \ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
            /** @var class-string $dependency */
            $dependency = $type->getName();

            return $this->get($dependency);
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($type instanceof \ReflectionNamedType && $type->allowsNull()) {
            return null;
        }

        throw new ContainerException(sprintf(
            'Unable to resolve parameter "$%s" of "%s".',
            $parameter->getName(),
            $class
        ));
    }
}
