<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - DataScraper</title>

    <style>
        :root {
            --primary: #0066ff;
            --secondary: #00d4ff;
            --dark: #1a1a2e;
            --light: #f8f9fa;
            --text-dark: #2d3748;
            --text-light: #718096;
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
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background: white;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }

        .logo {
            font-size: 1.6rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .container {
            max-width: 600px;
            margin: 80px auto;
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        }

        .profile-avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: linear-gradient(135deg,#0066ff,#00d4ff);
            color: white;
            font-size: 32px;
            display:flex;
            align-items:center;
            justify-content:center;
            margin: 0 auto 20px;
        }

        .profile-name {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .profile-email {
            text-align: center;
            color: var(--text-light);
            margin-bottom: 30px;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            border-radius: 50px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg,var(--primary),var(--secondary));
            color: white;
            margin-bottom: 15px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,102,255,0.3);
        }

       .btn-secondary {
            background: white;
            color: #0066ff;
            border: 2px solid #0066ff;
        }

        .btn-secondary:hover {
            background: #0066ff;
            color: white;
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
            padding: 10px;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
        }

    </style>
</head>
<body>

<nav>
    <div class="logo">🚀 DataScraper</div>
    <a href="/" style="text-decoration:none;color:#333;">Bosh sahifa</a>
</nav>

<div class="container" id="profileContainer">

    <div class="profile-avatar" id="avatarLetter"></div>

    <form id="profileForm">

        <div class="form-group">
            <label>Ism</label>
            <input type="text" id="name" required>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" id="email" required>
        </div>

        <div class="form-group">
            <label>Yangi Parol (ixtiyoriy)</label>
            <input type="password" id="password">
        </div>

        <button type="submit" class="btn btn-primary">
            Saqlash
        </button>

        <a href="/dashboard" class="btn btn-secondary">
            Qaytish
        </a>

    </form>

    <div id="message" style="margin-top:15px;"></div>

</div>


<script>
document.addEventListener("DOMContentLoaded", async function () {

    const token = localStorage.getItem('token');
    if (!token) {
        window.location.href = "/login";
        return;
    }

    const response = await fetch('/api/user', {
        headers: { 'Authorization': 'Bearer ' + token }
    });

    if (!response.ok) {
        localStorage.removeItem('token');
        window.location.href = "/login";
        return;
    }

    const user = await response.json();

    document.getElementById('name').value = user.name;
    document.getElementById('email').value = user.email;
    document.getElementById('avatarLetter').innerText =
        user.name.charAt(0).toUpperCase();
});


document.getElementById('profileForm')
.addEventListener('submit', async function(e){

    e.preventDefault();

    const token = localStorage.getItem('token');

    const name = document.getElementById('name').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;

    const response = await fetch('/api/user/update', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + token
        },
        body: JSON.stringify({ name, email, password })
    });

    const result = await response.json();

    if (!response.ok) {
        document.getElementById('message').innerHTML =
            `<span style="color:red">${result.message ?? 'Xatolik'}</span>`;
        return;
    }

    document.getElementById('message').innerHTML =
        `<span style="color:green">${result.message}</span>`;

});
    

function logout() {
    localStorage.removeItem('token');
    window.location.href = "/";
}
</script>


</body>
</html>
