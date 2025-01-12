<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'includes/Event.php';

$event = new Event();
$events = $event->getUpcomingEvents();
$errors = $event->getErrors();

require_once 'includes/nav-auth.php';
?>

<main class="py-12">
    <?php if (isset($errors['database'])): ?>
        <div class="bg-red-500/10 border border-red-500 text-red-500 px-4 py-2 rounded">
            <?php echo htmlspecialchars($errors['database']); ?>
        </div>
    <?php endif; ?>

    <div class="mb-8">
        <h1 class="text-4xl font-bold">Upcoming Events</h1>
        <p class="mt-2 text-neutral-400">Discover and join exciting events in your area.</p>
    </div>

    <?php if (empty($events)): ?>
        <div class="text-center py-12">
            <h2 class="text-2xl font-bold text-neutral-400">No upcoming events found</h2>
            <p class="mt-2 text-neutral-500">Check back later or create your own event!</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-3 gap-6">
            <?php foreach ($events as $event): ?>
                <div class="bg-neutral-900 rounded-lg overflow-hidden border border-neutral-800 hover:border-red-600 transition-colors">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-xl font-bold truncate"><?php echo htmlspecialchars($event['title']); ?></h3>
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
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>
                                <span class="truncate">Organized by <?php echo htmlspecialchars($event['creator_name']); ?></span>
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <a href="event.php?id=<?php echo $event['id']; ?>" 
                               class="block w-full bg-red-600 text-center text-white font-bold py-2 rounded-lg hover:bg-red-500">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php require_once 'includes/footer.php' ?>