<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Container;
use Core\Exceptions\ContainerException;
use PHPUnit\Framework\TestCase;

final class ContainerTest extends TestCase
{
    public function testBindResolvesFreshInstances(): void
    {
        $container = new Container();
        $container->bind('now', static fn(): object => new \stdClass());

        self::assertNotSame($container->get('now'), $container->get('now'));
    }

    public function testSingletonReturnsSameInstance(): void
    {
        $container = new Container();
        $container->singleton('shared', static fn(): object => new \stdClass());

        self::assertSame($container->get('shared'), $container->get('shared'));
    }

    public function testInstanceIsStored(): void
    {
        $container = new Container();
        $object    = new \stdClass();
        $container->instance('obj', $object);

        self::assertSame($object, $container->get('obj'));
    }

    public function testAutowiresConcreteClass(): void
    {
        $container = new Container();

        $resolved = $container->get(AutowireTarget::class);

        self::assertInstanceOf(AutowireTarget::class, $resolved);
        self::assertInstanceOf(AutowireDependency::class, $resolved->dependency);
    }

    public function testThrowsForUnresolvableId(): void
    {
        $this->expectException(ContainerException::class);

        (new Container())->get('does_not_exist');
    }
}

final class AutowireDependency
{
}

final class AutowireTarget
{
    public function __construct(public AutowireDependency $dependency)
    {
    }
}
