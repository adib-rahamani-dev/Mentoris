<?php if (($pagination['pages'] ?? 1) > 1): ?>
<nav class="admin-pagination" aria-label="صفحه‌بندی">
<?php for ($pageNumber = max(1, $pagination['page'] - 2); $pageNumber <= min($pagination['pages'], $pagination['page'] + 2); $pageNumber++): $params = array_merge($_GET, ['page' => $pageNumber]); ?>
<a class="<?= $pageNumber === $pagination['page'] ? 'is-active' : '' ?>" href="<?= e((parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '') . '?' . http_build_query($params)) ?>" <?= $pageNumber === $pagination['page'] ? 'aria-current="page"' : '' ?>><?= number_format($pageNumber) ?></a>
<?php endfor; ?>
<span>صفحه <?= number_format($pagination['page']) ?> از <?= number_format($pagination['pages']) ?> · <?= number_format($pagination['total']) ?> مورد</span>
</nav>
<?php endif; ?>
