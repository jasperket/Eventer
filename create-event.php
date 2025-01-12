<?php require_once 'includes/nav-auth.php' ?>


<main class="text-center h-full flex-1 flex flex-col gap-8 items-center">
    <form action="" method="post" class="flex flex-col mt-12 w-1/2 py-12 px-10 bg-neutral-900 rounded-2xl border border-red-600 shadow-xl shadow-red-600/50">
        <div class="flex flex-col items-start">
            <h1 class="text-4xl font-bold">Create Event</h1>
            <p class="mt-2">Fill in the details below to create your event.</p>
        </div>

        <div class="mt-8 flex flex-col gap-4">
            <div class="flex flex-col items-start gap-2">
                <label for="title">Event Title</label>
                <input type="text" name="title" id="title" class="w-full px-4 py-2 rounded bg-neutral-800" required>
            </div>

            <div class="flex flex-col items-start gap-2">
                <label for="description">Description</label>
                <textarea name="description" id="description" rows="4" class="w-full px-4 py-2 rounded bg-neutral-800 resize-none" required></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col items-start gap-2">
                    <label for="event_date">Event Date</label>
                    <input type="datetime-local" name="event_date" id="event_date" class="w-full px-4 py-2 rounded bg-neutral-800" required>
                </div>

                <div class="flex flex-col items-start gap-2">
                    <label for="capacity">Capacity</label>
                    <input type="number" name="capacity" id="capacity" min="1" class="w-full px-4 py-2 rounded bg-neutral-800" required>
                </div>
            </div>

            <div class="flex flex-col items-start gap-2">
                <label for="location">Location</label>
                <input type="text" name="location" id="location" class="w-full px-4 py-2 rounded bg-neutral-800" required>
            </div>

            <div class="flex flex-col items-start gap-2">
                <label for="status">Status</label>
                <select name="status" id="status" class="w-full px-4 py-2 rounded bg-neutral-800" required>
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                </select>
            </div>
        </div>

        <button type="submit" class="mt-8 w-full bg-red-600 text-white font-bold py-2 rounded-lg hover:bg-red-500">Create Event</button>
    </form>
</main>
</div>

<?php require_once 'includes/footer.php' ?>