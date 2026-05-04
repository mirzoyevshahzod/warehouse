<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tizimga Kirish - DataScraper</title>

    <style>
        :root {
            --primary: #0066ff;
            --secondary: #00d4ff;
            --dark: #1a1a2e;
            --light: #f8f9fa;
            --text-light: #718096;
            --border-radius: 16px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            background: white;
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            animation: fadeIn 0.4s ease;
        }

        .login-logo {
            text-align: center;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 30px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .login-container h2 {
            text-align: center;
            margin-bottom: 25px;
            color: var(--dark);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 6px;
            display: block;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            border-radius: 50px;
            border: none;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            transition: var(--transition);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 102, 255, 0.3);
        }

        .extra-links {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
        }

        .extra-links a {
            color: var(--primary);
            text-decoration: none;
        }

        .extra-links a:hover {
            text-decoration: underline;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2>Tizimga Kirish</h2>

    <form id="loginForm">
        <!-- Laravel bo‘lsa -->
        <!-- @csrf -->

        <div class="form-group">
            <label>Email manzil</label>
            <input type="email" name="email" placeholder="email@example.com" required>
        </div>

        <div class="form-group">
            <label>Parol</label>
            <input type="password" name="password" placeholder="********" required>
        </div>

        <button type="submit" class="btn-login">
            Kirish
        </button>
    </form>

    <div class="extra">
        Bosh Sahifaga Qaytmoqchimisiz? <a href="/">Bosh Sahifa</a>
    </div>
</div>
<script>
document.getElementById('loginForm').addEventListener('submit', async function(e){
    e.preventDefault();

    const email = document.querySelector('[name="email"]').value;
    const password = document.querySelector('[name="password"]').value;

    const response = await fetch('/api/login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ email, password })
    });

    const result = await response.json();
    if(!response.ok){
        alert(result.message);
        return;
    }

    // Token saqlaymiz
    localStorage.setItem('token', result.token);

    window.location.href = "/dashboard";
});
</script>

</body>
</html>
