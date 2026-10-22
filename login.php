<?php
require_once __DIR__ . '/config.php';
session_start();
require_once __DIR__ . '/app/Models/User.php';
require_once __DIR__ . '/app/Database/Database.php';
require_once __DIR__ . '/app/Helpers/csrf.php';
$userModel = new User();
$dt = new Database();
$conn = $dt->getConnect();
// With no accounts yet, show the one-time form that creates the first Admin.
// After that, accounts are created by an Admin on the Accounts page.
$firstRun = $userModel->countUsers() === 0;

$message = [];
foreach (['Saves', 'Error'] as $key) {
 if (!empty($_SESSION[$key])) {
  $message[] = $_SESSION[$key];
  unset($_SESSION[$key]);
 }
}
if ($_SERVER["REQUEST_METHOD"] == 'POST') {
 $username = trim($_POST['username'] ?? '');
 $pass = $_POST['password'] ?? '';

 $statement = $conn->prepare("SELECT * FROM user_account WHERE username = :username");
 $statement->bindValue(':username', $username);
 $statement->execute();
 $arr = $statement->fetch(PDO::FETCH_ASSOC);

 if ($username === '' || $pass === '' || empty($arr) || !password_verify($pass, $arr['user_pass'])) {
  $message[] = 'Invalid Credentials, Please try again!';
 } else {
  $message[] = "Congratulations, You are successfully logged in!";
  session_regenerate_id(true);
  $_SESSION['adminId'] = $arr['user_id'];
  header('Location: ' . BASE_URL . '/views/dashboard.php');
  exit;
 }
}
csrf_token();

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventory Management System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

</head>

<body>

    <div id="parent">

        <form action="<?php echo $_SERVER['PHP_SELF'] ?>" class="row g-3 needs-validation" novalidate method="post">
            <div class="input-group d-flex justify-content-center flex-column align-items-center">
                <img src="" alt="">

                <?php if ($message) : ?>

                 <?php foreach ($message as $value) : ?>
                  <script>
                   alert("<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>");

                  </script>

                 <?php endforeach; ?>

                <?php endif; ?>
                <h2 class="lh-1" style="font-size:20px;">INVENTORY MANAGEMENT SYSTEM</h2>

            </div>
            <div class="input-group mb-1">
                <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                <input type="text" class="form-control" required placeholder="Username" name="username">

            </div>
            <div class="input-group mb-1">
                <span class="input-group-text"><i class="fa-solid fa-key"></i></span>
                <input type="password" class="form-control" required placeholder="Password" name="password">

            </div>

            <div class="input-group mb-2">
                <button class="btn btn-success" type="submit">LOGIN</button>

            </div>

        </form>
        <?php if ($firstRun): ?>
        <div class="input-group mb-3">
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addAccount">SET UP ADMIN ACCOUNT</button>

        </div>
        <?php endif; ?>

        <div class=""><?php $message; ?></div>

    </div>

    <?php if ($firstRun): ?>
    <div class="modal fade" id="addAccount" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h1 class="modal-title fs-5 text text-white fw-bold text-uppercase" id="staticBackdropLabel" style="font-family: 'Playfair Display', serif; letter-spacing:2px; "><i class="fa-sharp fa-solid fa-user me-2"></i>Create Admin Account</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

                </div>

                <form action="<?= BASE_URL ?>/app/Controllers/UserController.php" method="post">
                 <input type="hidden" name="_action" value="insert">
                 <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="modal-body">
                   
                        <p class="mb-3">This is the first account, so it will be an <b>Admin</b>. You can add Staff and Employee accounts from the Accounts page after signing in.</p>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="floatingInput" name="user">
                            <label for="floatingInput" class="text text-black">Username:</label>

                        </div>
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="floatingInput" name="pass" minlength="8" required>
                            <label for="floatingInput" class="text text-black">Password (at least 8 characters):</label>

                        </div>
                        <hr>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="floatingInput" name="first">
                            <label for="floatingInput" class="text text-black">Firstname:</label>

                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="floatingInput" name="last">
                            <label for="floatingTextarea" class="text text-black">Lastname:</label>

                        </div>
                        <div class="form-floating">
                            <input type="text" class="form-control" id="floatingInput" name="phone">
                            <label for="floatingTextarea" class="text text-black">Phone number:</label>

                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success" name="save">Save Account</button>

                    </div>

                </form>

            </div>

        </div>

    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>

    <script>
        (() => {
            'use strict'

            const forms = document.querySelectorAll('.needs-validation')

            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }

                    form.classList.add('was-validated')
                }, false)
            })
        })()

    </script>
</body>

</html>