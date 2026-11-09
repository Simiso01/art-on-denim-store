<?php
/**
 * Art on Denim — Flash alerts
 */
declare(strict_types=1);

$flashes = get_flashes();
if ($flashes === []) {
    return;
}
?>
<div class="alerts" role="region" aria-label="Notifications" aria-live="polite">
    <?php foreach ($flashes as $flash): ?>
        <?php
        $type = in_array($flash['type'], ['success', 'error', 'warning', 'info'], true)
            ? $flash['type']
            : 'info';
        ?>
        <div class="alert alert--<?= e($type) ?>" role="alert">
            <p><?= e($flash['message']) ?></p>
            <button type="button" class="alert__close" aria-label="Dismiss notification">&times;</button>
        </div>
    <?php endforeach; ?>
</div>
