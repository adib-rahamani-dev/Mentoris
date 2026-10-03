# Phone-first public registration

- Mobile is the first required signup field and the normal login identifier. Name, email (password recovery), password, member type, and terms retain their existing requirements.
- No SMS or email verification gate was introduced. Successful registration signs in immediately and opens `/profile?welcome=1`.
- Signup and login use `PhoneNumber::normalize`: Persian/Arabic digits, spaces, hyphens, parentheses, `+98`, `98`, and `0098` normalize to local `09…`. Invalid country codes, letters, and wrong lengths are rejected server-side.
- The repository validates and normalizes phone independently of the form. Public signup refuses existing numbers, including historically formatted records. It never signs into an existing account merely because its phone matches.
- Concurrent public signups serialize on one mutex row in the existing `rate_limits` table, inside the account transaction. MySQL uses row locking; SQLite acquires a write lock before reading users. Existing accounts are neither merged nor deleted. Managed account creation remains available for admins; historical duplicate numbers retain the existing safe email-login fallback.
- No schema change or new SQL is required for this release. Deploy the latest GitHub commit to apply it.

Verification: `php tests/Feature/member-flow.php` covers formatted signup, malformed and array inputs, duplicates across email/format variants, historical formatted records, immediate profile redirect, login ownership, and eight simultaneous signup workers. Concurrency was tested with isolated SQLite; MySQL uses `INSERT IGNORE` and `SELECT … FOR UPDATE` and still needs a hosting smoke test after deployment.
