<?php
require_once 'includes/bootstrap.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get event ID from URL
$eventId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$eventId) {
    header('Location: events.php');
    exit();
}

$event = new Event();
$eventDetails = $event->getEventById($eventId);

if (!$eventDetails) {
    header('Location: events.php');
    exit();
}

// Check if current user is the creator
$isCreator = $_SESSION['user_id'] === $eventDetails['creator_id'];

// Get registration status of current user
$userRegistration = $event->getUserRegistrationStatus($eventId, $_SESSION['user_id']);

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

    <div class="bg-neutral-900 rounded-lg border border-neutral-800 overflow-hidden">
        <div class="p-8">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h1 class="text-4xl font-bold mb-2"><?php echo htmlspecialchars($eventDetails['title']); ?></h1>
                    <p class="text-neutral-400">
                        Created by <?php echo htmlspecialchars($eventDetails['creator_name']); ?>
                    </p>
                </div>

                <?php if ($isCreator): ?>
                    <div class="flex gap-2">
                        <a href="edit-event.php?id=<?php echo $eventId; ?>"
                            class="px-4 py-2 bg-neutral-800 text-white rounded hover:bg-neutral-700">
                            Edit Event
                        </a>
                        <form action="delete-event.php" method="post" class="inline"
                            onsubmit="return confirm('Are you sure you want to delete this event?');">
                            <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                            <button type="submit"
                                class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-500">
                                Delete Event
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-3 gap-8">
                <div class="col-span-2">
                    <div class="prose prose-invert">
                        <h2 class="text-2xl font-bold mb-4">About this event</h2>
                        <p class="whitespace-pre-wrap"><?php echo htmlspecialchars($eventDetails['description']); ?></p>
                    </div>

                    <div class="mt-8">
                        <h2 class="text-2xl font-bold mb-4">Event Details</h2>
                        <div class="space-y-4">
                            <div class="flex items-center gap-2 text-neutral-400">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                </svg>
                                <span><?php echo date('F j, Y \a\t g:i A', strtotime($eventDetails['event_date'])); ?></span>
                            </div>

                            <div class="flex items-center gap-2 text-neutral-400">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                </svg>
                                <span><?php echo htmlspecialchars($eventDetails['location']); ?></span>
                            </div>

                            <div class="flex items-center gap-2 text-neutral-400">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                                </svg>
                                <span>
                                    <?php echo $eventDetails['registered_count']; ?>/<?php echo $eventDetails['capacity']; ?> registered
                                </span>
                            </div>

                            <div class="flex items-center gap-2 text-neutral-400">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                                </svg>
                                <span class="capitalize"><?php echo htmlspecialchars($eventDetails['status']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="bg-neutral-800 p-6 rounded-lg">
                        <h2 class="text-2xl font-bold mb-4">Registration</h2>

                        <?php if (!$isCreator): ?>
                            <?php if (!$userRegistration): ?>
                                <?php if ($eventDetails['registered_count'] < $eventDetails['capacity']): ?>
                                    <form action="register-event.php" method="post">
                                        <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                                        <button type="submit" class="w-full bg-red-600 text-white font-bold py-2 rounded hover:bg-red-500">
                                            Register for Event
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form action="register-event.php" method="post">
                                        <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                                        <input type="hidden" name="waitlist" value="1">
                                        <button type="submit" class="w-full bg-neutral-700 text-white font-bold py-2 rounded hover:bg-neutral-600">
                                            Join Waitlist
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="text-center">
                                    <p class="mb-4">
                                        Your registration status:
                                        <span class="font-bold capitalize"><?php echo htmlspecialchars($userRegistration['status']); ?></span>
                                    </p>
                                    <?php if ($userRegistration['status'] !== 'cancelled'): ?>
                                        <form action="cancel-registration.php" method="post"
                                            onsubmit="return confirm('Are you sure you want to cancel your registration?');">
                                            <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                                            <button type="submit" class="w-full bg-red-600 text-white font-bold py-2 rounded hover:bg-red-500">
                                                Cancel Registration
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-center text-neutral-400">You are the organizer of this event</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php' ?>