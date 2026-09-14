<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class ContentRepository
{
    public function __construct(private readonly ?PDO $database = null) {}

    public function published(string $type, string $locale): array
    {
        $statement = $this->pdo()->prepare($this->selectSql() . ' WHERE ce.entity_type=:type AND ce.status=:status ORDER BY ce.sort_order,ce.published_at DESC,ce.created_at DESC');
        $statement->execute(['type' => $type, 'status' => 'published', 'locale' => $locale]);
        return array_map([$this, 'hydrate'], $statement->fetchAll() ?: []);
    }

    public function findPublished(string $type, string $slug, string $locale): ?array
    {
        $statement = $this->pdo()->prepare($this->selectSql() . ' WHERE ce.entity_type=:type AND ce.slug=:slug AND ce.status=:status LIMIT 1');
        $statement->execute(['type' => $type, 'slug' => $slug, 'status' => 'published', 'locale' => $locale]);
        $row = $statement->fetch();
        return is_array($row) ? $this->hydrate($row) : null;
    }

    private function selectSql(): string
    {
        return <<<'SQL'
SELECT ce.id,ce.entity_type,ce.slug,ce.status,ce.sort_order,ce.author_id,ce.published_at,ce.created_at,ce.updated_at,
       COALESCE(NULLIF(current_translation.title,''),fa_translation.title,'') title,
       COALESCE(NULLIF(current_translation.subtitle,''),fa_translation.subtitle,'') subtitle,
       COALESCE(NULLIF(current_translation.excerpt,''),fa_translation.excerpt,'') excerpt,
       COALESCE(NULLIF(current_translation.body,''),fa_translation.body,'') body,
       COALESCE(current_translation.metadata,fa_translation.metadata,JSON_OBJECT()) metadata,
       CASE WHEN current_translation.id IS NULL THEN 'fa' ELSE current_translation.locale END resolved_locale,
       author.name author_name
FROM content_entities ce
LEFT JOIN content_translations current_translation ON current_translation.entity_id=ce.id AND current_translation.locale=:locale
LEFT JOIN content_translations fa_translation ON fa_translation.entity_id=ce.id AND fa_translation.locale='fa'
LEFT JOIN users author ON author.id=ce.author_id
SQL;
    }

    private function hydrate(array $row): array
    {
        $metadata = json_decode((string) ($row['metadata'] ?? '{}'), true);
        $row['metadata'] = is_array($metadata) ? $metadata : [];
        $row['sort_order'] = (int) ($row['sort_order'] ?? 0);
        return $row;
    }

    private function pdo(): PDO
    {
        return $this->database ?? Database::connection();
    }
}
