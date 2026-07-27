<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\ValidationException;
use App\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testPassesWithValidData(): void
    {
        $validator = new Validator(
            ['email' => 'a@b.com', 'age' => 21],
            ['email' => 'required|email', 'age' => 'required|integer|min:18']
        );

        self::assertTrue($validator->validate());
        self::assertSame([], $validator->errors());
    }

    public function testRequiredFieldFails(): void
    {
        $validator = new Validator([], ['name' => 'required']);

        self::assertFalse($validator->validate());
        self::assertArrayHasKey('name', $validator->errors());
    }

    public function testEmailRuleFails(): void
    {
        $validator = new Validator(['email' => 'not-an-email'], ['email' => 'required|email']);

        self::assertFalse($validator->validate());
    }

    public function testMinAndMaxOnStrings(): void
    {
        $validator = new Validator(['pw' => 'short'], ['pw' => 'min:8']);

        self::assertFalse($validator->validate());
    }

    public function testNullableSkipsRules(): void
    {
        $validator = new Validator(['nick' => null], ['nick' => 'nullable|string|min:3']);

        self::assertTrue($validator->validate());
    }

    public function testInRule(): void
    {
        $ok  = new Validator(['role' => 'admin'], ['role' => 'in:admin,user']);
        $bad = new Validator(['role' => 'root'], ['role' => 'in:admin,user']);

        self::assertTrue($ok->validate());
        self::assertFalse($bad->validate());
    }

    public function testValidateOrFailThrows(): void
    {
        $this->expectException(ValidationException::class);

        (new Validator([], ['x' => 'required']))->validateOrFail();
    }

    public function testValidatedReturnsOnlyRuleFields(): void
    {
        $validator = new Validator(
            ['a' => 1, 'b' => 2, 'extra' => 3],
            ['a' => 'integer', 'b' => 'integer']
        );
        $validator->validate();

        self::assertSame(['a' => 1, 'b' => 2], $validator->validated());
    }
}
