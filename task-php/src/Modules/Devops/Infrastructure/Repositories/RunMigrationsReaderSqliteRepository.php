<?php
declare(strict_types=1);

namespace App\Modules\Devops\Infrastructure\Repositories;

use App\Modules\Shared\Infrastructure\Repositories\AbstractSqliteRepository;

final class RunMigrationsReaderSqliteRepository extends AbstractSqliteRepository
{
    public static function getInstance(): self
    {
        return new self();
    }

    public function doesMigrationsTableExist(string $tableName): bool
    {
        return $this->doesTableExist($tableName);
    }

    public function getRanMigrationsFiles(): array
    {
        $sql = "
        -- getRanMigrationsFiles
        SELECT
            id, migration_file
        FROM `migrations`
        WHERE 1=1
        ORDER BY migration_file
        ";
        return $this->query($sql);
    }

    public function getMaxBatch(): int
    {
        $sql = "
        -- getMaxBatch
        SELECT
            MAX(batch) AS max_batch
        FROM `migrations`
        WHERE 1=1
        ";
        $rows = $this->query($sql);
        return (int) ($rows[0]["max_batch"] ?? 0);
    }

}
