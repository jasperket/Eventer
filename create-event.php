<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'includes/Event.php';

$event = new Event();
$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'event_date' => $_POST['event_date'] ?? '',
        'location' => trim($_POST['location'] ?? ''),
        'capacity' => $_POST['capacity'] ?? '',
        'status' => $_POST['status'] ?? 'draft'
    ];

    $old = $data;

    if ($event->create($data)) {
        $_SESSION['success'] = 'Event created successfully!';
        header('Location: my-events.php');
        exit();
    } else {
        $errors = $event->getErrors();
    }
}
?>

<?php require_once 'includes/nav-auth.php' ?>

<main class="text-center h-full flex-1 flex flex-col gap-8 items-center">
    <form action="" method="post" class="flex flex-col mt-12 w-1/2 py-12 px-10 bg-neutral-900 rounded-2xl border border-red-600 shadow-xl shadow-red-600/50">
        <div class="flex flex-col items-start">
            <h1 class="text-4xl font-bold">Create Event</h1>
            <p class="mt-2">Fill in the details below to create your event.</p>
        </div>
        
        <?php if (isset($errors['database'])): ?>
            <div class="mt-4 text-red-500 text-left"><?php echo htmlspecialchars($errors['database']); ?></div>
        <?php endif; ?>

        <div class="mt-8 flex flex-col gap-4">
            <div class="flex flex-col items-start gap-2">
                <label for="title">Event Title</label>
                <input type="text" name="title" id="title" 
                       class="w-full px-4 py-2 rounded bg-neutral-800 <?php echo isset($errors['title']) ? 'border border-red-500' : ''; ?>"
                       value="<?php echo htmlspecialchars($old['title'] ?? ''); ?>" required>
                <?php if (isset($errors['title'])): ?>
                    <span class="text-red-500 text-sm"><?php echo htmlspecialchars($errors['title']); ?></span>
                <?php endif; ?>
            </div>

            <div class="flex flex-col items-start gap-2">
                <label for="description">Description</label>
                <textarea name="description" id="description" rows="4" 
                          class="w-full px-4 py-2 rounded bg-neutral-800 resize-none <?php echo isset($errors['description']) ? 'border border-red-500' : ''; ?>"
                          required><?php echo htmlspecialchars($old['description'] ?? ''); ?></textarea>
                <?php if (isset($errors['description'])): ?>
                    <span class="text-red-500 text-sm"><?php echo htmlspecialchars($errors['description']); ?></span>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col items-start gap-2">
                    <label for="event_date">Event Date</label>
                    <input type="datetime-local" name="event_date" id="event_date" 
                           class="w-full px-4 py-2 rounded bg-neutral-800 <?php echo isset($errors['event_date']) ? 'border border-red-500' : ''; ?>"
                           value="<?php echo htmlspecialchars($old['event_date'] ?? ''); ?>" required>
                    <?php if (isset($errors['event_date'])): ?>
                        <span class="text-red-500 text-sm"><?php echo htmlspecialchars($errors['event_date']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="flex flex-col items-start gap-2">
                    <label for="capacity">Capacity</label>
                    <input type="number" name="capacity" id="capacity" min="1" 
                           class="w-full px-4 py-2 rounded bg-neutral-800 <?php echo isset($errors['capacity']) ? 'border border-red-500' : ''; ?>"
                           value="<?php echo htmlspecialchars($old['capacity'] ?? ''); ?>" required>
                    <?php if (isset($errors['capacity'])): ?>
                        <span class="text-red-500 text-sm"><?php echo htmlspecialchars($errors['capacity']); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex flex-col items-start gap-2">
                <label for="location">Location</label>
                <input type="text" name="location" id="location" 
                       class="w-full px-4 py-2 rounded bg-neutral-800 <?php echo isset($errors['location']) ? 'border border-red-500' : ''; ?>"
                       value="<?php echo htmlspecialchars($old['location'] ?? ''); ?>" required>
                <?php if (isset($errors['location'])): ?>
                    <span class="text-red-500 text-sm"><?php echo htmlspecialchars($errors['location']); ?></span>
                <?php endif; ?>
            </div>

            <div class="flex flex-col items-start gap-2">
                <label for="status">Status</label>
                <select name="status" id="status" 
                        class="w-full px-4 py-2 rounded bg-neutral-800 <?php echo isset($errors['status']) ? 'border border-red-500' : ''; ?>" required>
                    <option value="draft" <?php echo ($old['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="published" <?php echo ($old['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                </select>
                <?php if (isset($errors['status'])): ?>
                    <span class="text-red-500 text-sm"><?php echo htmlspecialchars($errors['status']); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <button type="submit" class="mt-8 w-full bg-red-600 text-white font-bold py-2 rounded-lg hover:bg-red-500">Create Event</button>
    </form>
</main>

<?php require_once 'includes/footer.php' ?>