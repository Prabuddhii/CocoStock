<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Check user credentials
    $sql = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $row['role'];

        if ($row['role'] == 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: add.php");
        }
        exit(); // Ensure no further code is executed after redirect
    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - CocoStock</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f0f2f5, #d9e6d9);
            color: #333;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow-x: hidden;
        }

        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
            animation: fadeIn 1s ease-out;
        }

        h2 {
            font-size: clamp(1.5rem, 4vw, 2rem);
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1.5rem;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 0.8rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            font-weight: 400;
            display: <?php echo isset($error) ? 'block' : 'none'; ?>;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        input[type="text"],
        input[type="password"] {
            padding: 0.8rem 1rem;
            font-size: 1rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #28a745;
            box-shadow: 0 0 5px rgba(40, 167, 69, 0.4);
        }

        input::placeholder {
            color: #999;
            font-weight: 300;
        }

        .btn {
            padding: 0.8rem;
            background: #28a745;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
        }

        .btn:hover {
            background: #218838;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.6);
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .form-container {
                padding: 1.5rem;
                margin: 1rem;
            }

            h2 {
                font-size: clamp(1.2rem, 3vw, 1.5rem);
            }

            input[type="text"],
            input[type="password"],
            .btn {
                font-size: 0.9rem;
                padding: 0.7rem;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Sign In to CocoStock</h2>
        <?php if (isset($error)) echo "<div class='error-message'>$error</div>"; ?>
        <form method="POST" action="">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" class="btn">Sign In</button>
        </form>
    </div>

    <script>
        // Fade-in animation on page load
        document.addEventListener('DOMContentLoaded', () => {
            const formContainer = document.querySelector('.form-container');
            formContainer.style.opacity = '0';
            setTimeout(() => {
                formContainer.style.opacity = '1';
                formContainer.style.transition = 'opacity 0.5s ease';
            }, 100);
        });

        // Bounce effect on button click
        const btn = document.querySelector('.btn');
        btn.addEventListener('click', () => {
            btn.style.animation = 'bounce 0.6s ease';
            setTimeout(() => {
                btn.style.animation = ''; // Reset animation after it finishes
            }, 600);
        });
    </script>
</body>
</html>