<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\BaseModel;
use PHPUnit\Framework\TestCase;

/**
 * Concrete test double for the abstract BaseModel.
 */
final class SampleModel extends BaseModel
{
    protected string $table = 'samples';

    protected array $fillable = ['name', 'active', 'meta'];

    protected array $hidden = ['secret'];

    protected array $casts = [
        'active' => 'bool',
        'meta'   => 'json',
        'score'  => 'int',
    ];
}

final class BaseModelTest extends TestCase
{
    public function testFillRespectsFillable(): void
    {
        $model = new SampleModel(['name' => 'A', 'not_allowed' => 'x']);

        self::assertSame('A', $model->get('name'));
        self::assertNull($model->get('not_allowed'));
    }

    public function testCastsAreApplied(): void
    {
        $model = SampleModel::fromRow([
            'active' => 1,
            'meta'   => '{"k":"v"}',
            'score'  => '10',
        ]);

        self::assertTrue($model->get('active'));
        self::assertSame(['k' => 'v'], $model->get('meta'));
        self::assertSame(10, $model->get('score'));
    }

    public function testHiddenFieldsRemovedFromArray(): void
    {
        $model = SampleModel::fromRow(['name' => 'A', 'secret' => 'hush']);

        self::assertArrayNotHasKey('secret', $model->toArray());
        self::assertArrayHasKey('name', $model->toArray());
    }

    public function testJsonSerializeMatchesToArray(): void
    {
        $model = SampleModel::fromRow(['name' => 'A', 'secret' => 'hush']);

        self::assertSame($model->toArray(), $model->jsonSerialize());
    }

    public function testTableAndKeyAccessors(): void
    {
        $model = new SampleModel();

        self::assertSame('samples', $model->getTable());
        self::assertSame('id', $model->getKeyName());
    }
}
