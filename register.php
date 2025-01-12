<?php require_once 'includes/header.php' ?>

<?php require_once 'includes/nav-guest.php' ?>

<main class="text-center h-full flex-1 flex flex-col gap-8 items-center">
    <form action="" method="post" class="flex flex-col mt-36 w-1/3 py-12 px-10 bg-neutral-900 rounded-2xl border border-red-600 shadow-xl shadow-red-600/50">
        <div class="flex flex-col items-start">
            <h1 class="text-4xl font-bold">Register</h1>
            <p class="mt-2"><a href="login.php" class="text-red-600 font-bold hover:underline">Login</a> if you already have an account.</p>
        </div>
        <div class="mt-8 flex flex-col gap-4">
            <div class="flex flex-col items-start gap-2">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" class="w-full px-4 py-2 rounded bg-neutral-800">
            </div>
            <div class="flex flex-col items-start gap-2">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" class="w-full px-4 py-2 rounded bg-neutral-800">
            </div>
            <div class="flex flex-col items-start gap-2">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="w-full px-4 py-2 rounded bg-neutral-800">
            </div>
        </div>
        <button type="submit" class="mt-8 w-full bg-red-600 text-white font-bold py-2 rounded-lg hover:bg-red-500">Register</button>
    </form>
</main>
</div>

<?php require_once 'includes/footer.php' ?>