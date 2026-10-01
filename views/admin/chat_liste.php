<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="ceps-section">
    <h2>Conversations clients</h2>

    <table class="ceps-table">
        <thead><tr><th>Client</th><th>Dernier message</th><th>Non lus</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($conversations as $conv): ?>
            <tr>
                <td><?= e($conv['prenom'] . ' ' . $conv['nom']) ?></td>
                <td><?= e(mb_strimwidth((string)$conv['dernier_message'], 0, 60, '...')) ?></td>
                <td><?= (int)$conv['non_lus'] > 0 ? '<span class="ceps-badge">' . (int)$conv['non_lus'] . '</span>' : '—' ?></td>
                <td><a href="<?= BASE_URL ?>/index.php?page=admin_chat_conversation&id=<?= (int)$conv['id_conversation'] ?>" class="ceps-btn ceps-btn-petit">Ouvrir</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($conversations)): ?>
            <tr><td colspan="4">Aucune conversation.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
