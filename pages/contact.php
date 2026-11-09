<?php
/**
 * Art on Denim — Contact
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$errors = [];
$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors['form'] = 'Invalid security token. Please try again.';
    } else {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));

        if ($name === '' || mb_strlen($name) > 120) {
            $errors['name'] = 'Please enter your name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email address.';
        }
        if ($subject === '' || mb_strlen($subject) > 200) {
            $errors['subject'] = 'Please enter a subject.';
        }
        if ($message === '' || mb_strlen($message) < 10) {
            $errors['message'] = 'Please enter a message (at least 10 characters).';
        }

        if ($errors === []) {
            try {
                $stmt = db()->prepare(
                    'INSERT INTO contact_messages (name, email, subject, message, ip_address)
                     VALUES (:name, :email, :subject, :message, :ip)'
                );
                $stmt->execute([
                    ':name'    => $name,
                    ':email'   => $email,
                    ':subject' => $subject,
                    ':message' => $message,
                    ':ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
                ]);
                $sent = true;
                flash('success', 'Your message has been sent. We will respond shortly.');
                redirect('pages/contact.php');
            } catch (Throwable $e) {
                error_log('AOD contact: ' . $e->getMessage());
                $errors['form'] = 'Unable to send your message right now. Please try again later.';
                store_old($_POST);
            }
        } else {
            store_old($_POST);
        }
    }
}

$pageTitle = 'Contact';
$pageDescription = 'Contact Art on Denim.';
$bodyClass = 'page-contact';

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/navigation.php';
require dirname(__DIR__) . '/includes/alerts.php';
?>

<main id="main-content" class="section">
    <div class="container auth-layout">
        <header class="auth-intro">
            <p class="eyebrow">Support</p>
            <h1>Contact</h1>
            <p>Questions about orders, sizing, or availability — send us a message.</p>
        </header>

        <form class="auth-form" method="post" action="<?= e(url('pages/contact.php')) ?>" novalidate>
            <?= csrf_field() ?>

            <?php if (!empty($errors['form'])): ?>
                <div class="form-errors" role="alert"><p><?= e($errors['form']) ?></p></div>
            <?php endif; ?>

            <div class="form-field">
                <label for="name">Full name</label>
                <input type="text" id="name" name="name" value="<?= old('name') ?>" required maxlength="120" autocomplete="name">
                <?php if (!empty($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>
            </div>

            <div class="form-field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= old('email', (string) ($_GET['email'] ?? '')) ?>" required maxlength="190" autocomplete="email">
                <?php if (!empty($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
            </div>

            <div class="form-field">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" value="<?= old('subject') ?>" required maxlength="200">
                <?php if (!empty($errors['subject'])): ?><p class="field-error"><?= e($errors['subject']) ?></p><?php endif; ?>
            </div>

            <div class="form-field">
                <label for="message">Message</label>
                <textarea id="message" name="message" rows="6" required><?= old('message') ?></textarea>
                <?php if (!empty($errors['message'])): ?><p class="field-error"><?= e($errors['message']) ?></p><?php endif; ?>
            </div>

            <button type="submit" class="btn btn--inverse btn--block">Send message</button>
        </form>
    </div>
</main>

<?php
clear_old();
require dirname(__DIR__) . '/includes/footer.php';
?>
