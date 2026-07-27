<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\PostbackRepositoryInterface;

/**
 * Postback pipeline repository. Owns `offer_conversions` and writes
 * `offer_clicks` and `postback_logs`. All inserts are parameterised and column
 * identifiers are validated via the inherited guard.
 */
final class PostbackRepository extends BaseRepository implements PostbackRepositoryInterface
{
    protected string $table = 'offer_conversions';

    public function createClick(array $data): string
    {
        return $this->insertInto('offer_clicks', $data);
    }

    public function findClickByToken(string $token): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM `offer_clicks` WHERE `click_token` = ? LIMIT 1',
            [$token]
        );
    }

    public function findConversion(int $providerId, string $transactionIdExt): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM `offer_conversions` WHERE `provider_id` = ? AND `transaction_id_ext` = ? LIMIT 1',
            [$providerId, $transactionIdExt]
        );
    }

    public function createConversion(array $data): string
    {
        return $this->create($data);
    }

    public function updateConversion(int $id, array $data): int
    {
        return $this->update($id, $data);
    }

    public function logPostback(array $data): string
    {
        return $this->insertInto('postback_logs', $data);
    }

    /**
     * Parameterised INSERT into a whitelisted table with validated identifiers.
     *
     * @param array<string, mixed> $data
     */
    private function insertInto(string $table, array $data): string
    {
        $this->assertIdentifier($table);

        $columns = array_keys($data);
        foreach ($columns as $column) {
            $this->assertIdentifier($column);
        }

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $columnList   = implode(', ', array_map(static fn(string $c): string => "`$c`", $columns));

        return $this->db->insert(
            sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $table, $columnList, $placeholders),
            array_values($data)
        );
    }
}
