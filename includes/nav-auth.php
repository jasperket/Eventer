<?php require_once 'header.php' ?>

<div class="h-screen flex flex-col">
    <div class="pt-8">
        <header class="flex items-center gap-8">
            <h2 class="text-4xl font-bold text-red-500">Eventer</h2>
            <div class="flex gap-2">
                <a href="events.php" class="px-4 py-2 rounded hover:underline">All Events</a>
                <a href="registered-events.php" class="px-4 py-2 rounded hover:underline">Registered Events</a>
                <a href="user-events.php" class="px-4 py-2 rounded hover:underline">My Events</a>
            </div>
            <div class="ml-auto">
                <a href="create-event.php" class="bg-red-600 text-white font-bold px-4 py-2 rounded hover:bg-red-500">+ Create an Event</a>
                <a href="logout.php" class="px-4 py-2 rounded hover:underline">Logout</a>
            </div>
        </header>
    </div>