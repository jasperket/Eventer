<?php require_once 'includes/header.php' ?>

<div class="h-screen flex flex-col">
    <div class="p-4"></div>
    <header class="flex items-center justify-between">
        <h1 class="text-4xl font-bold text-red-500">Eventer</h1>
        <div class="flex gap-2">
            <a href="login.php" class="bg-red-600 text-white font-bold px-4 py-2 rounded hover:bg-red-500">Log in</a>
            <a href="register.php" class="px-4 py-2 rounded hover:underline">Sign up</a>
        </div>
    </header>

    <main class="text-center h-full flex-1 flex flex-col items-center justify-center gap-8">
        <h2 class="text-7xl font-bold">Your events,<br> <span class="text-red-600">expertly managed</span></h2>
        <p class="w-[75ch]">Take control of your event lifecycle with seamless registration, real-time tracking, and intelligent capacity management. Focus on creating great experiences while we handle the details.</p>
    </main>
</div>

<?php require_once 'includes/footer.php' ?>