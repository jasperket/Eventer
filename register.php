<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: events.php');
    exit();
}

require_once 'includes/User.php';

$user = new User();
$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? ''
    ];

    $old = $data;

    if ($user->register($data)) {
        // Log the user in automatically after registration
        $user->login($data['username'], $data['password']);
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
            <h1 class="text-4xl font-bold">Register</h1>
            <p class="mt-2"><a href="login.php" class="text-red-600 font-bold hover:underline">Login</a> if you already have an account.</p>
        </div>

        <?php if (isset($errors['database'])): ?>
            <div class="mt-4 text-red-500 text-left"><?php echo htmlspecialchars($errors['database']); ?></div>
        <?php endif; ?>

        <div class="mt-8 flex flex-col gap-4">
            <div class="flex flex-col items-start gap-2">
                <label for="username">Username</label>
                <input type="text" name="username" id="username"
                    class="w-full px-4 py-2 rounded bg-neutral-800 <?php echo isset($errors['username']) ? 'border border-red-500' : ''; ?>"
                    value="<?php echo htmlspecialchars($old['username'] ?? ''); ?>" required>
                <?php if (isset($errors['username'])): ?>
                    <span class="text-red-500 text-sm"><?php echo htmlspecialchars($errors['username']); ?></span>
                <?php endif; ?>
            </div>

            <div class="flex flex-col items-start gap-2">
                <label for="email">Email</label>
                <input type="email" name="email" id="email"
                    class="w-full px-4 py-2 rounded bg-neutral-800 <?php echo isset($errors['email']) ? 'border border-red-500' : ''; ?>"
                    value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>" required>
                <?php if (isset($errors['email'])): ?>
                    <span class="text-red-500 text-sm"><?php echo htmlspecialchars($errors['email']); ?></span>
                <?php endif; ?>
            </div>

            <div class="flex flex-col items-start gap-2">
                <label for="password">Password</label>
                <input type="password" name="password" id="password"
                    class="w-full px-4 py-2 rounded bg-neutral-800 <?php echo isset($errors['password']) ? 'border border-red-500' : ''; ?>"
                    required>
                <?php if (isset($errors['password'])): ?>
                    <span class="text-red-500 text-sm"><?php echo htmlspecialchars($errors['password']); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <button type="submit" class="mt-8 w-full bg-red-600 text-white font-bold py-2 rounded-lg hover:bg-red-500">Register</button>
    </form>
</main>

<?php require_once 'includes/footer.php' ?>