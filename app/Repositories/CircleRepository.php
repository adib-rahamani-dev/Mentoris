<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\Crypto;
use App\Core\Security;
use PDO;
use PDOException;
use RuntimeException;

final class CircleRepository
{
    public function __construct(private readonly ?PDO $database = null) {}

    public function signup(string $slug, array $data, ?string $userId): array
    {
        $phone = self::phone((string) $data['phone']);
        $now = Database::now();
        try {
            $statement = $this->pdo()->prepare('INSERT INTO event_signups (id,user_id,event_slug,name,phone,city,status,created_at,updated_at) VALUES (:id,:user_id,:slug,:name,:phone,:city,:status,:created_at,:updated_at)');
            $statement->execute(['id' => Security::randomToken(16), 'user_id' => $userId, 'slug' => $slug, 'name' => trim((string) $data['name']), 'phone' => $phone, 'city' => trim((string) $data['city']), 'status' => 'requested', 'created_at' => $now, 'updated_at' => $now]);
        } catch (PDOException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '19'], true)) throw new RuntimeException('این شماره برای این رویداد قبلاً ثبت شده است.');
            throw $exception;
        }
        return $this->findSignup($slug, $phone) ?? throw new RuntimeException('درخواست ثبت نشد.');
    }

    public function findSignup(string $slug, string $phone): ?array
    {
        $statement = $this->pdo()->prepare('SELECT * FROM event_signups WHERE event_slug=:slug AND phone=:phone LIMIT 1');
        $statement->execute(['slug' => $slug, 'phone' => self::phone($phone)]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function findSignupById(string $slug, string $id): ?array
    {
        $statement = $this->pdo()->prepare('SELECT * FROM event_signups WHERE event_slug=:slug AND id=:id LIMIT 1');
        $statement->execute(['slug' => $slug, 'id' => $id]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function claimSignup(string $id, string $userId): void
    {
        $statement = $this->pdo()->prepare('UPDATE event_signups SET user_id=:user_id,updated_at=:updated_at WHERE id=:id AND user_id IS NULL');
        $statement->execute(['user_id' => $userId, 'updated_at' => Database::now(), 'id' => $id]);
    }

    public function profileComplete(string $userId): bool
    {
        $profile = $this->profile($userId);
        foreach (['latin_name','national_id','education_level','specialization','experience'] as $field) if (empty($profile[$field])) return false;
        return !empty($profile['approaches']) && !empty($profile['practice_areas']);
    }

    public function issueEligibleCertificates(string $userId): array
    {
        if (!$this->tableExists('event_certificates')) return [];
        if ($this->profileComplete($userId)) {
            $statement = $this->pdo()->prepare("SELECT s.id FROM event_signups s JOIN event_feedback f ON f.signup_id=s.id LEFT JOIN event_certificates c ON c.signup_id=s.id WHERE s.user_id=:user_id AND s.status='attended' AND s.attended_at IS NOT NULL AND c.id IS NULL");
            $statement->execute(['user_id' => $userId]);
            foreach ($statement->fetchAll(PDO::FETCH_COLUMN) ?: [] as $signupId) {
                try {
                    $this->pdo()->prepare('INSERT INTO event_certificates (id,signup_id,user_id,certificate_number,issued_at) VALUES (:id,:signup_id,:user_id,:number,:issued_at)')
                        ->execute(['id' => Security::randomToken(16), 'signup_id' => $signupId, 'user_id' => $userId, 'number' => 'MTR-' . strtoupper(substr(Security::randomToken(10), 0, 16)), 'issued_at' => Database::now()]);
                } catch (PDOException $exception) {
                    if (!in_array((string) $exception->getCode(), ['23000', '19'], true)) throw $exception;
                }
            }
        }
        $statement = $this->pdo()->prepare('SELECT c.certificate_number,c.issued_at,s.name,s.event_slug,p.latin_name FROM event_certificates c JOIN event_signups s ON s.id=c.signup_id JOIN therapist_profiles p ON p.user_id=c.user_id WHERE c.user_id=:user_id ORDER BY c.issued_at DESC');
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll() ?: [];
    }

    public function certificate(string $number): ?array
    {
        if (!$this->tableExists('event_certificates')) return null;
        $statement = $this->pdo()->prepare('SELECT c.certificate_number,c.issued_at,s.name,s.event_slug,p.latin_name FROM event_certificates c JOIN event_signups s ON s.id=c.signup_id JOIN therapist_profiles p ON p.user_id=c.user_id WHERE c.certificate_number=:number LIMIT 1');
        $statement->execute(['number' => $number]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function userSignups(string $userId): array
    {
        $statement = $this->pdo()->prepare('SELECT event_slug,status,created_at FROM event_signups WHERE user_id=:user_id ORDER BY created_at DESC');
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll() ?: [];
    }

    public function profile(string $userId): array
    {
        try {
            $statement = $this->pdo()->prepare('SELECT * FROM therapist_profiles WHERE user_id=:user_id LIMIT 1');
            $statement->execute(['user_id' => $userId]);
        } catch (PDOException $exception) {
            if (!MemberProfileRepository::missingTable($exception)) throw $exception;
            return [];
        }
        $row = $statement->fetch() ?: [];
        $row['national_id'] = !empty($row['national_id_ciphertext']) ? (Crypto::decrypt((string) $row['national_id_ciphertext'], 'mentoris-national-id-v1') ?? '') : '';
        unset($row['national_id_ciphertext']);
        foreach (['approaches', 'practice_areas'] as $field) $row[$field] = json_decode((string) ($row[$field] ?? '[]'), true) ?: [];
        return $row;
    }

    public function saveProfile(string $userId, array $data): void
    {
        $columns = ['latin_name','national_id_ciphertext','professional_number','education_level','specialization','university','approaches','practice_areas','experience','social_link'];
        $values = ['user_id' => $userId, 'updated_at' => Database::now()];
        foreach ($columns as $column) {
            if ($column === 'national_id_ciphertext') $values[$column] = ($data['national_id'] ?? '') !== '' ? Crypto::encrypt((string) $data['national_id'], 'mentoris-national-id-v1') : null;
            else $values[$column] = in_array($column, ['approaches','practice_areas'], true) ? json_encode(array_values($data[$column] ?? []), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) : trim((string) ($data[$column] ?? ''));
        }
        $updates = implode(', ', array_map(static fn (string $column): string => "$column=VALUES($column)", [...$columns, 'updated_at']));
        $sql = 'INSERT INTO therapist_profiles (user_id,' . implode(',', $columns) . ',updated_at) VALUES (:' . implode(',:', ['user_id', ...$columns, 'updated_at']) . ') ON DUPLICATE KEY UPDATE ' . $updates;
        $this->pdo()->prepare($sql)->execute($values);
    }

    public function feedback(string $signupId): ?array
    {
        $statement = $this->pdo()->prepare('SELECT * FROM event_feedback WHERE signup_id=:signup_id LIMIT 1');
        $statement->execute(['signup_id' => $signupId]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function saveFeedback(string $signupId, array $data): void
    {
        try {
            $this->pdo()->prepare('INSERT INTO event_feedback (id,signup_id,content_rating,hosting_rating,challenge,comment,created_at) VALUES (:id,:signup_id,:content_rating,:hosting_rating,:challenge,:comment,:created_at)')
                ->execute(['id' => Security::randomToken(16), 'signup_id' => $signupId, 'content_rating' => (int) $data['content_rating'], 'hosting_rating' => (int) $data['hosting_rating'], 'challenge' => $data['challenge'], 'comment' => trim((string) ($data['comment'] ?? '')), 'created_at' => Database::now()]);
        } catch (PDOException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '19'], true)) throw new RuntimeException('بازخورد شما قبلاً ثبت شده است.');
            throw $exception;
        }
    }

    public static function phone(string $phone): string
    {
        $phone = strtr(trim($phone), ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
        if (str_starts_with($phone, '+98')) $phone = '0' . substr($phone, 3);
        if (str_starts_with($phone, '98') && strlen($phone) === 12) $phone = '0' . substr($phone, 2);
        return $phone;
    }

    private function pdo(): PDO { return $this->database ?? Database::connection(); }

    public function available(): bool { return $this->tableExists('event_signups'); }

    public function feedbackAvailable(): bool { return $this->tableExists('event_signups') && $this->tableExists('event_feedback'); }

    public function therapistAvailable(): bool { return $this->tableExists('therapist_profiles'); }

    private function tableExists(string $table): bool
    {
        try { $this->pdo()->query('SELECT 1 FROM ' . $table . ' LIMIT 0'); return true; }
        catch (PDOException $exception) { if (MemberProfileRepository::missingTable($exception)) return false; throw $exception; }
    }
}
