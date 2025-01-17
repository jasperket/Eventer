<?php
require_once 'includes/bootstrap.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$event = new Event();
$hostedEvents = $event->getHostedEvents($_SESSION['user_id']);
$registeredEvents = $event->getRegisteredEvents($_SESSION['user_id']);
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

    <!-- Hosted Events Section -->
    <section class="mb-12">
        <div class="mb-8">
            <h1 class="text-4xl font-bold">Events You're Hosting</h1>
            <p class="mt-2 text-neutral-400">Manage the events you've created.</p>
        </div>

        <?php if (empty($hostedEvents)): ?>
            <div class="text-center py-12 bg-neutral-900 rounded-lg">
                <h2 class="text-2xl font-bold text-neutral-400">You haven't created any events yet</h2>
                <p class="mt-2 text-neutral-500">
                    <a href="create-event.php" class="text-red-500 hover:underline">Create your first event</a>
                </p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-3 gap-6">
                <?php foreach ($hostedEvents as $event): ?>
                    <div class="bg-neutral-900 rounded-lg overflow-hidden border border-neutral-800 hover:border-red-600 transition-colors">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="text-xl font-bold truncate flex-1"><?php echo htmlspecialchars($event['title']); ?></h3>
                                <span class="px-2 py-1 bg-red-600/10 text-red-500 text-sm rounded-full whitespace-nowrap ml-2">
                                    <?php echo $event['registered_count']; ?>/<?php echo $event['capacity']; ?> registered
                                </span>
                            </div>

                            <p class="text-neutral-400 mb-4 line-clamp-3"><?php echo htmlspecialchars($event['description']); ?></p>

                            <div class="space-y-2 text-sm text-neutral-400">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                    </svg>
                                    <span class="truncate"><?php echo date('F j, Y \a\t g:i A', strtotime($event['event_date'])); ?></span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                    </svg>
                                    <span class="truncate"><?php echo htmlspecialchars($event['location']); ?></span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                                    </svg>
                                    <span class="capitalize"><?php echo htmlspecialchars($event['status']); ?></span>
                                </div>
                            </div>

                            <div class="mt-6 flex gap-2">
                                <a href="event.php?id=<?php echo $event['id']; ?>"
                                    class="flex-1 bg-red-600 text-center text-white font-bold py-2 rounded-lg hover:bg-red-500">
                                    View Details
                                </a>
                                <div class="flex gap-2">
                                    <a href="edit-event.php?id=<?php echo $event['id']; ?>"
                                        class="px-4 py-2 bg-neutral-800 text-white rounded hover:bg-neutral-700">
                                        Edit
                                    </a>
                                    <form action="delete-event.php" method="post" class="inline"
                                        onsubmit="return confirm('Are you sure you want to delete this event? This action cannot be undone.');">
                                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                        <button type="submit"
                                            class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-500">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Registered Events Section -->
    <section>
        <div class="mb-8">
            <h1 class="text-4xl font-bold">Events You're Attending</h1>
            <p class="mt-2 text-neutral-400">Keep track of your event registrations.</p>
        </div>

        <?php if (empty($registeredEvents)): ?>
            <div class="text-center py-12 bg-neutral-900 rounded-lg">
                <h2 class="text-2xl font-bold text-neutral-400">You haven't registered for any events</h2>
                <p class="mt-2 text-neutral-500">
                    <a href="events.php" class="text-red-500 hover:underline">Browse available events</a>
                </p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-3 gap-6">
                <?php foreach ($registeredEvents as $event): ?>
                    <div class="bg-neutral-900 rounded-lg overflow-hidden border border-neutral-800 hover:border-red-600 transition-colors">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="text-xl font-bold truncate flex-1"><?php echo htmlspecialchars($event['title']); ?></h3>
                                <span class="px-2 py-1 text-sm rounded-full whitespace-nowrap ml-2 capitalize
                                           <?php echo match ($event['registration_status']) {
                                                'confirmed' => 'bg-green-500/10 text-green-500',
                                                'pending' => 'bg-yellow-500/10 text-yellow-500',
                                                'waitlisted' => 'bg-blue-500/10 text-blue-500',
                                                'cancelled' => 'bg-neutral-500/10 text-neutral-500',
                                                default => 'bg-neutral-500/10 text-neutral-500'
                                            }; ?>">
                                    <?php echo htmlspecialchars($event['registration_status']); ?>
                                </span>
                            </div>

                            <p class="text-neutral-400 mb-4 line-clamp-3"><?php echo htmlspecialchars($event['description']); ?></p>

                            <div class="space-y-2 text-sm text-neutral-400">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                    </svg>
                                    <span class="truncate"><?php echo date('F j, Y \a\t g:i A', strtotime($event['event_date'])); ?></span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                    </svg>
                                    <span class="truncate"><?php echo htmlspecialchars($event['location']); ?></span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                    </svg>
                                    <span class="truncate">Organized by <?php echo htmlspecialchars($event['creator_name']); ?></span>
                                </div>
                            </div>

                            <div class="mt-6">
                                <?php if ($event['registration_status'] !== 'cancelled'): ?>
                                    <form action="cancel-registration.php" method="post"
                                        onsubmit="return confirm('Are you sure you want to cancel your registration?');">
                                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                        <div class="flex gap-2">
                                            <a href="event.php?id=<?php echo $event['id']; ?>"
                                                class="flex-1 bg-red-600 text-center text-white font-bold py-2 rounded-lg hover:bg-red-500">
                                                View Details
                                            </a>
                                            <button type="submit" class="px-4 py-2 bg-neutral-800 text-white rounded hover:bg-neutral-700">
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <a href="event.php?id=<?php echo $event['id']; ?>"
                                        class="block w-full bg-red-600 text-center text-white font-bold py-2 rounded-lg hover:bg-red-500">
                                        View Details
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once 'includes/footer.php' ?>