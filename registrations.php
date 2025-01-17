<?php
require_once 'includes/bootstrap.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'includes/Event.php';

$event = new Event();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registrationId = filter_input(INPUT_POST, 'registration_id', FILTER_VALIDATE_INT);
    $newStatus = $_POST['new_status'] ?? '';

    if ($registrationId && in_array($newStatus, ['confirmed', 'waitlisted', 'cancelled'])) {
        if ($event->updateRegistrationStatus($registrationId, $newStatus, $_SESSION['user_id'])) {
            $_SESSION['success'] = 'Registration status updated successfully';
        } else {
            $_SESSION['error'] = implode(' ', $event->getErrors());
        }
    } else {
        $_SESSION['error'] = 'Invalid request';
    }

    header('Location: registrations.php');
    exit();
}

$registrations = $event->getEventRegistrations($_SESSION['user_id']);
$errors = $event->getErrors();

require_once 'includes/nav-auth.php';
?>

<main class="py-12">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="mb-6 bg-green-500/10 border border-green-500 text-green-500 px-4 py-2 rounded">
            <?php
            echo htmlspecialchars($_SESSION['success']);
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="mb-6 bg-red-500/10 border border-red-500 text-red-500 px-4 py-2 rounded">
            <?php
            echo htmlspecialchars($_SESSION['error']);
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($errors['database'])): ?>
        <div class="mb-6 bg-red-500/10 border border-red-500 text-red-500 px-4 py-2 rounded">
            <?php echo htmlspecialchars($errors['database']); ?>
        </div>
    <?php endif; ?>

    <div class="mb-8">
        <h1 class="text-4xl font-bold">Manage Registrations</h1>
        <p class="mt-2 text-neutral-400">Review and manage registrations for your events.</p>
    </div>

    <?php if (empty($registrations)): ?>
        <div class="text-center py-12 bg-neutral-900 rounded-lg">
            <h2 class="text-2xl font-bold text-neutral-400">No registrations found</h2>
            <p class="mt-2 text-neutral-500">
                You don't have any registrations for your events yet.
                <a href="create-event.php" class="text-red-500 hover:underline">Create an event</a> to get started.
            </p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full bg-neutral-900 rounded-lg">
                <thead>
                    <tr class="text-left border-b border-neutral-800">
                        <th class="px-6 py-3">Event</th>
                        <th class="px-6 py-3">User</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Registered</th>
                        <th class="px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $registration): ?>
                        <tr class="border-b border-neutral-800">
                            <td class="px-6 py-4">
                                <div>
                                    <div class="font-bold"><?php echo htmlspecialchars($registration['event_title']); ?></div>
                                    <div class="text-sm text-neutral-400">
                                        <?php echo date('F j, Y \a\t g:i A', strtotime($registration['event_date'])); ?>
                                    </div>
                                    <div class="text-sm text-neutral-400">
                                        <?php echo $registration['registered_count']; ?>/<?php echo $registration['capacity']; ?> registered
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($registration['username']); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-sm rounded-full capitalize
                                    <?php echo match ($registration['status']) {
                                        'confirmed' => 'bg-green-500/10 text-green-500',
                                        'pending' => 'bg-yellow-500/10 text-yellow-500',
                                        'waitlisted' => 'bg-blue-500/10 text-blue-500',
                                        'cancelled' => 'bg-neutral-500/10 text-neutral-500',
                                        default => 'bg-neutral-500/10 text-neutral-500'
                                    }; ?>">
                                    <?php echo htmlspecialchars($registration['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-neutral-400">
                                <?php echo date('M j, Y', strtotime($registration['registered_at'])); ?>
                            </td>
                            <td class="px-6 py-4">
                                <form action="" method="post" class="flex gap-2">
                                    <input type="hidden" name="registration_id" value="<?php echo $registration['id']; ?>">
                                    <select name="new_status" class="px-3 py-2 rounded bg-neutral-800 text-sm">
                                        <option value="confirmed" <?php echo $registration['status'] === 'confirmed' ? 'selected' : ''; ?>>
                                            Confirm
                                        </option>
                                        <option value="waitlisted" <?php echo $registration['status'] === 'waitlisted' ? 'selected' : ''; ?>>
                                            Waitlist
                                        </option>
                                        <option value="cancelled" <?php echo $registration['status'] === 'cancelled' ? 'selected' : ''; ?>>
                                            Cancel
                                        </option>
                                    </select>
                                    <button type="submit"
                                        class="px-4 py-2 bg-red-600 text-white text-sm rounded hover:bg-red-500">
                                        Update
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<?php require_once 'includes/footer.php' ?>