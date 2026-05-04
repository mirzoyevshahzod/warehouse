<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scraping Service - Xush Kelibsiz</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #0066ff;
            --secondary: #00d4ff;
            --accent: #ff6b6b;
            --dark: #1a1a2e;
            --light: #f8f9fa;
            --text-dark: #2d3748;
            --text-light: #718096;
            --border-radius: 16px;
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Navigation */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background: white;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-links {
            display: flex;
            gap: 30px;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            transition: var(--transition);
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .auth-buttons {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 102, 255, 0.3);
        }

        .btn-outline {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        /* Hero Section */
        .hero {
            padding: 80px 40px;
            text-align: center;
        }

        .hero h1 {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 20px;
            background: linear-gradient(135deg, var(--dark), var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
        }

        .hero p {
            font-size: 1.3rem;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto 40px;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Services Section */
        .services {
            padding: 100px 40px;
            background: var(--light);
        }

        .services-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .services-header h2 {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--dark);
        }

        .services-header p {
            font-size: 1.2rem;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .service-card {
            background: white;
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.08);
            transition: var(--transition);
            cursor: pointer;
            position: relative;
            overflow: hidden;

            display: flex;
            flex-direction: column;   /* 🔥 MUHIM */
        }


        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            transform: scaleX(0);
            transform-origin: left;
            transition: var(--transition);
        }

        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
        }

        .service-card:hover::before {
            transform: scaleX(1);
        }

        .service-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 20px;
        }

        .service-card:nth-child(1) .service-icon {
            background: rgba(0, 102, 255, 0.1);
            color: var(--primary);
        }

        .service-card:nth-child(2) .service-icon {
            background: rgba(0, 212, 255, 0.1);
            color: var(--secondary);
        }

        .service-card:nth-child(3) .service-icon {
            background: rgba(255, 107, 107, 0.1);
            color: var(--accent);
        }

        .service-card h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: var(--dark);
        }

        .service-card p {
            color: var(--text-light);
            margin-bottom: 25px;
            min-height: 60px;
        }

        .service-card .btn {
            margin-top: auto;   /* 🔥 Eng muhim qator */
            width: 100%;
        }


        /* Features Section */
        .features {
            padding: 100px 40px;
            background: white;
        }

        .features-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .features-header h2 {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-item {
            text-align: center;
        }

        .feature-number {
            font-size: 3rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .feature-item h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: var(--dark);
        }

        .feature-item p {
            color: var(--text-light);
        }

        /* CTA Section */
        .cta {
            padding: 80px 40px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            text-align: center;
            border-radius: 0;
        }

        .cta h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        .cta p {
            font-size: 1.1rem;
            margin-bottom: 40px;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta .btn {
            background: white;
            color: var(--primary);
        }

        .cta .btn:hover {
            background: var(--light);
        }

        /* Footer */
        footer {
            background: var(--dark);
            color: white;
            padding: 40px;
            text-align: center;
        }

        footer p {
            opacity: 0.8;
        }

        /* Responsive */
        @media (max-width: 768px) {
            nav {
                flex-direction: column;
                gap: 20px;
                padding: 15px 20px;
            }

            .nav-links {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .hero {
                padding: 40px 20px;
            }

            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .hero-buttons {
                flex-direction: column;
                gap: 15px;
            }

            .hero-buttons .btn {
                width: 100%;
            }

            .services {
                padding: 60px 20px;
            }

            .services-header h2 {
                font-size: 2rem;
            }

            .services-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .features {
                padding: 60px 20px;
            }

            .features-header h2 {
                font-size: 2rem;
            }

            .cta {
                padding: 50px 20px;
            }

            .cta h2 {
                font-size: 1.8rem;
            }

        }
        .user-menu {
            position: relative;
        }

        .user-avatar-wrapper {
            cursor: pointer;
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-avatar-letter {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg,#0066ff,#00d4ff);
            color: white;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:600;
        }

        .dropdown {
            position: absolute;
            top: 55px;
            right: 0;
            background: white;
            min-width: 180px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: none;
            overflow: hidden;
            z-index: 999;
        }

        .dropdown a,
        .dropdown button {
            display: block;
            padding: 12px 16px;
            text-decoration: none;
            color: #333;
            background: none;
            border: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
        }

        .dropdown a:hover,
        .dropdown button:hover {
            background: #f5f5f5;
        }

        .dropdown-user {
            padding: 12px 16px;
            font-weight: 600;
            border-bottom: 1px solid #eee;
        }

    </style>
</head>
<body>
<!-- Navigation -->
<nav>
    <div class="logo">🚀 DataScraper</div>
    <div class="auth-buttons" id="authArea">
    </div>

</nav>

<!-- Hero Section -->

<!-- Services Section -->
<section class="services" id="services">
    <div class="services-header">
        <h2>Bizning Xizmatlar</h2>
        <p>Uchta asosiy manba dan ma'lumot yig'ish xizmatlari</p>
    </div>

    <div class="services-grid">
        <!-- Turkey Scraping -->
        <div class="service-card">
            <div class="service-icon">🇹🇷</div>
            <h3>Turkiya Skrapinga</h3>
            <p>Turkiya bozoridan eng yangi va to'liq ma'lumotlarni yig'ing. E-commerce saytlari, narxlar, mahsulot ma'lumotlarini real vaqtda oling.</p>
            <a href="{{ route('turkeyScraping') }}" class="btn btn-primary">Boshlash</a>
        </div>

        <!-- E-Commerce Scraping -->
        <div class="service-card">
            <div class="service-icon">🛍️</div>
            <h3>E-Ombor Skrapinga</h3>
            <p>Katta e-commerce platformalardan mahsulot katalogini, narxlarni va reyting ma'lumotlarini avtomatik yig'ing.</p>
            <a href="{{ route('eOmborATScraping') }}" class="btn btn-primary">Boshlash</a>
        </div>

        <!-- Belarus Scraping -->
        <div class="service-card">
            <div class="service-icon">🇧🇾</div>
            <h3>Belarus Skrapinga</h3>
            <p>Belarus bozorining tahlil uchun kerakli ma'lumotlarni yig'ing. Mahalliy web-saytlar va bozor ma'lumotlarini ta'minlanuvchidan oling.</p>
            <a href=" {{ route('belarusBenyakoni') }}" class="btn btn-primary">Boshlash</a>
        </div>
            <!-- Turkey Scraping -->
            <div class="service-card">
                <div class="service-icon">🇰🇿</div>
                <h3>Qazoq Skrapinga</h3>
                <p>Turkiya bozoridan eng yangi va to'liq ma'lumotlarni yig'ing. E-commerce saytlari, narxlar, mahsulot ma'lumotlarini real vaqtda oling.</p>
                <a href="{{ route('qozoqScraping') }}" class="btn btn-primary">Boshlash</a>
            </div>

            <!-- E-Commerce Scraping -->
            <div class="service-card">
                <div class="service-icon">🛍️</div>
                <h3>Mintrans Skrapinga</h3>
                <p>Katta e-commerce platformalardan mahsulot katalogini, narxlarni va reyting ma'lumotlarini avtomatik yig'ing.</p>
                <a href="{{ route('mintransScraping') }}" class="btn btn-primary">Boshlash</a>
            </div>

            <!-- Drivers -->
            <div class="service-card">
                <div class="service-icon">🚚</div>
                <h3>Haydovchilar</h3>
                <p>Haydovchilar ma'lumotlarini yig'ing.</p>
                <a href="{{ route('drivers') }}" class="btn btn-primary">Boshlash</a>
            </div>
            

    </div>
</section>



<!-- Footer -->
<footer>
    <p>&copy; 2026 DataScraper. Barcha huquqlar himoyalangan.</p>
</footer>
</body>
</html>
<script>
    const token = localStorage.getItem('token');
document.addEventListener("DOMContentLoaded", async function () {

    const token = localStorage.getItem('token');
    const authArea = document.getElementById('authArea');
    if (!token) {
    window.location.href = "/";
}

    if (!token) return;

    try {

        const response = await fetch('/api/user', {
            headers: {
                'Authorization': 'Bearer ' + token
            }
        });

        if (!response.ok) {
            localStorage.removeItem('token');
            return;
        }

        const user = await response.json();

        const avatar = user.avatar
            ? `<img src="${user.avatar}" class="user-avatar">`
            : `<div class="user-avatar-letter">${user.name.charAt(0).toUpperCase()}</div>`;

        authArea.innerHTML = `
            <div class="user-menu">
                <div class="user-avatar-wrapper" onclick="toggleDropdown()">
                    ${avatar}
                </div>

                <div class="dropdown" id="dropdownMenu">
                    <div class="dropdown-user">${user.name}</div>
                    <a href="/profile">Profile</a>
                    <button onclick="logout()">Chiqish</button>
                </div>
            </div>
        `;

    } catch (e) {
        console.error(e);
    }
});

function toggleDropdown() {
    const dropdown = document.getElementById('dropdownMenu');
    dropdown.style.display =
        dropdown.style.display === 'block' ? 'none' : 'block';
}

async function logout() {
    

    try {
        await fetch('/api/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        });
    } catch (error) {
        console.error('Logout xatolik:', error);
    } finally {
        localStorage.removeItem('token');
        window.location.href = "/";
    }
}


window.addEventListener('click', function(e){
    if (!e.target.closest('.user-menu')) {
        const dropdown = document.getElementById('dropdownMenu');
        if(dropdown) dropdown.style.display = 'none';
    }
});
</script>
