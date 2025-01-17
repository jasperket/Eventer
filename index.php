<?php
require_once 'includes/bootstrap.php';

// If user is logged in, redirect to events page
if (isset($_SESSION['user_id'])) {
    header('Location: events.php');
    exit();
}

// Log page visit
logger()->info('Home page visited', [
    'ip' => $_SERVER['REMOTE_ADDR'],
    'userAgent' => $_SERVER['HTTP_USER_AGENT']
]);

require_once 'includes/nav-guest.php';
?>

<main class="text-center h-full flex-1 flex flex-col gap-8 items-center">
    <h2 class="text-7xl font-bold mt-72">Your events,<br> <span class="text-red-600">expertly managed</span></h2>
    <p class="w-[75ch]">Take control of your event lifecycle with seamless registration, real-time tracking, and intelligent capacity management. Focus on creating great experiences while we handle the details.</p>
</main>
</div>

<?php require_once 'includes/footer.php' ?>