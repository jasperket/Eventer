<?php
require_once 'includes/bootstrap.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: events.php');
    exit();
}

$user = new User();
$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $old['username'] = $username;

    if ($user->login($username, $password)) {
        header('Location: events.php');
        exit();
    } else {
        $errors = $user->getErrors();
    }
}

require_once 'includes/nav-guest.php';
?>

<main class="text-center h-full flex-1 flex flex-col gap-8 items-center">
    <form action="" method="post" class="flex flex-col mt-36 w-1/3 py-12 px-10 bg-neutral-900 rounded-2xl border border-red-600 shadow-xl shadow-red-600/50">
        <div class="flex flex-col items-start">
            <h1 class="text-4xl font-bold">Login</h1>
            <p class="mt-2"><a href="register.php" class="text-red-600 font-bold hover:underline">Register</a> if you don't have an account.</p>
        </div>

        <?php if (isset($errors['database'])): ?>
            <div class="mt-4 text-red-500 text-left"><?php echo htmlspecialchars($errors['database']); ?></div>
        <?php endif; ?>

        <?php if (isset($errors['login'])): ?>
            <div class="mt-4 text-red-500 text-left"><?php echo htmlspecialchars($errors['login']); ?></div>
        <?php endif; ?>

        <div class="mt-8 flex flex-col gap-4">
            <div class="flex flex-col items-start gap-2">
                <label for="username">Username</label>
                <input type="text" name="username" id="username"
                    class="w-full px-4 py-2 rounded bg-neutral-800"
                    value="<?php echo htmlspecialchars($old['username'] ?? ''); ?>" required>
            </div>

            <div class="flex flex-col items-start gap-2">
                <label for="password">Password</label>
                <input type="password" name="password" id="password"
                    class="w-full px-4 py-2 rounded bg-neutral-800"
                    required>
            </div>
        </div>

        <button type="submit" class="mt-8 w-full bg-red-600 text-white font-bold py-2 rounded-lg hover:bg-red-500">Login</button>
    </form>
</main>

<?php require_once 'includes/footer.php' ?>