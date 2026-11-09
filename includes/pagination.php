<?php
/**
 * Pagination partial — expects $pagination array with keys:
 * current, total_pages, base_url (with existing query string ready for page=)
 */
declare(strict_types=1);

if (empty($pagination) || (int) ($pagination['total_pages'] ?? 0) <= 1) {
    return;
}

$current = (int) $pagination['current'];
$total = (int) $pagination['total_pages'];
$base = $pagination['base_url'];
?>
<nav class="pagination" aria-label="Pagination">
    <ul class="pagination__list">
        <?php if ($current > 1): ?>
            <li>
                <a href="<?= e($base . ($current - 1)) ?>" rel="prev">Previous</a>
            </li>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total; $i++): ?>
            <li>
                <?php if ($i === $current): ?>
                    <span aria-current="page"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= e($base . $i) ?>"><?= $i ?></a>
                <?php endif; ?>
            </li>
        <?php endfor; ?>

        <?php if ($current < $total): ?>
            <li>
                <a href="<?= e($base . ($current + 1)) ?>" rel="next">Next</a>
            </li>
        <?php endif; ?>
    </ul>
</nav>
