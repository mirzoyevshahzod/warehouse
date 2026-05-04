<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma'lumotlar Boshqaruvi</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            min-height: 100vh;
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
            color: white;
            padding: 30px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 0 20px 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 20px;
        }

        .sidebar-logo {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin: 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            padding-left: 25px;
        }

        .sidebar-menu a.active {
            background: rgba(33, 150, 243, 0.3);
            color: #64B5F6;
            border-left: 3px solid #64B5F6;
            padding-left: 17px;
        }

        .menu-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        /* Hamburger Menu */
        .hamburger {
            display: none;
            flex-direction: column;
            gap: 6px;
            cursor: pointer;
            background: none;
            border: none;
            color: #1a237e;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
        }

        .hamburger span {
            width: 25px;
            height: 3px;
            background: #1a237e;
            border-radius: 2px;
            transition: all 0.3s ease;
        }

        .hamburger.active span:nth-child(1) {
            transform: rotate(45deg) translate(10px, 10px);
        }

        .hamburger.active span:nth-child(2) {
            opacity: 0;
        }

        .hamburger.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -7px);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px 20px;
            transition: margin-left 0.3s ease;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Header */
        .header {
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 2.5rem;
            color: #1a237e;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .header p {
            color: #616161;
            font-size: 1rem;
        }

        /* Action Buttons */
        .button-group {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-upload {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
            color: white;
        }

        .btn-upload:hover {
            background: linear-gradient(135deg, #1976D2 0%, #1565C0 100%);
        }

        .btn-download {
            background: linear-gradient(135deg, #4CAF50 0%, #388E3C 100%);
            color: white;
        }

        .btn-download:hover {
            background: linear-gradient(135deg, #388E3C 0%, #2E7D32 100%);
        }

        /* Search Section */
        .search-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .search-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .search-box {
            display: flex;
            flex-direction: column;
        }

        .search-box label {
            font-size: 0.9rem;
            font-weight: 600;
            color: #424242;
            margin-bottom: 8px;
        }

        .input-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f5f5f5;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }

        .input-wrapper:focus-within {
            border-color: #2196F3;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
        }

        .search-icon {
            width: 18px;
            height: 18px;
            color: #999;
            flex-shrink: 0;
        }

        .search-box input {
            border: none;
            background: transparent;
            width: 100%;
            outline: none;
            font-size: 0.95rem;
            color: #333;
        }

        .search-box input::placeholder {
            color: #999;
        }

        /* Table Section */
        .table-section {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(90deg, #1976D2 0%, #1565C0 100%);
            color: white;
        }

        th {
            padding: 18px 20px;
            text-align: left;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
        }

        td {
            padding: 15px 20px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 0.95rem;
        }

        tbody tr {
            transition: all 0.2s ease;
        }

        tbody tr:hover {
            background: #f3f6ff;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        /* Status Badges */
        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
        }

        .status.confirmed {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .status.pending {
            background: #fff3e0;
            color: #e65100;
            border: 1px solid #ffe0b2;
        }

        .status.rejected {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        /* Name Column */
        td:first-child {
            font-weight: 600;
            color: #1a237e;
        }

        /* Post Code Column */
        td:last-child {
            font-weight: 600;
            color: #0d47a1;
        }

        /* Table Footer */
        .table-footer {
            background: #f9f9f9;
            border-top: 1px solid #e0e0e0;
            padding: 15px 20px;
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }

        .table-footer strong {
            color: #1a237e;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hamburger {
                display: flex;
            }

            .sidebar {
                position: fixed;
                transform: translateX(-100%);
                width: 250px;
                height: 100vh;
                top: 0;
                left: 0;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 80px 20px 30px;
            }

            .header h1 {
                font-size: 1.8rem;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .search-grid {
                grid-template-columns: 1fr;
            }

            th, td {
                padding: 12px 10px;
                font-size: 0.85rem;
            }

            table {
                font-size: 0.8rem;
            }
        }

        /* Animations */
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

        .search-section, table, .btn {
            animation: fadeIn 0.4s ease;
        }
    </style>
</head>
<body>
<!-- Hamburger Menu -->
<button class="hamburger" id="hamburger">
    <span></span>
    <span></span>
    <span></span>
</button>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <svg class="menu-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/>
            </svg>
            Dashboard
        </div>
    </div>
    <ul class="sidebar-menu">
        <li>
            <a href="#" class="active">
                <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                Asosiy sahifa
            </a>
        </li>
        <li>
            <a href="#">
                <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                Ma'lumotlar
            </a>
        </li>
        <li>
            <a href="#">
                <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h4z"></path>
                    <path d="M16 4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h4z"></path>
                </svg>
                Jadvallar
            </a>
        </li>
        <li>
            <a href="#">
                <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                    <polyline points="13 2 13 9 20 9"></polyline>
                </svg>
                Fayllar
            </a>
        </li>
        <li>
            <a href="#">
                <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="1"></circle>
                    <circle cx="19" cy="12" r="1"></circle>
                    <circle cx="5" cy="12" r="1"></circle>
                </svg>
                Ko'p ko'rish
            </a>
        </li>
        <li style="margin-top: 30px; border-top: 1px solid rgba(255, 255, 255, 0.2); padding-top: 20px;">
            <a href="#">
                <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="1"></circle>
                    <path d="M12 1v6m0 6v6"></path>
                    <path d="M4.22 4.22l4.24 4.24m2.12 2.12l4.24 4.24"></path>
                    <path d="M1 12h6m6 0h6"></path>
                    <path d="M4.22 19.78l4.24-4.24m2.12-2.12l4.24-4.24"></path>
                </svg>
                Sozlamalar
            </a>
        </li>
    </ul>
</aside>

<!-- Main Content -->
<div class="main-content">
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Ma'lumotlar Boshqaruvi</h1>
            <p>Fayllarni boshqaring va ma'lumotlarni qidiring</p>
        </div>

        <!-- Action Buttons -->
        <div class="button-group">
            <button class="btn btn-upload">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                Fayl Yuklash
            </button>
            <button class="btn btn-download">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Fayl Yuklab olish
            </button>
        </div>

        <!-- Search Section -->
        <div class="search-section">
            <div class="search-grid">
                <div class="search-box">
                    <label>Nomi bo'yicha qidirish</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" placeholder="Ism kiriting...">
                    </div>
                </div>

                <div class="search-box">
                    <label>Vaqt bo'yicha</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" placeholder="Vaqt kiriting...">
                    </div>
                </div>

                <div class="search-box">
                    <label>Holat bo'yicha</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" placeholder="Holat kiriting...">
                    </div>
                </div>

                <div class="search-box">
                    <label>Post kodi bo'yicha</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" placeholder="Post kodi kiriting...">
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="table-section">
            <div class="table-wrapper">
                <table>
                    <thead>
                    <tr>
                        <th>Nomi</th>
                        <th>Vaqti</th>
                        <th>Holati</th>
                        <th>Chegara</th>
                        <th>Post kodi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>Aloqadorov Sherzod</td>
                        <td>09:30</td>
                        <td><span class="status confirmed">Tasdiqlandi</span></td>
                        <td>Fergona</td>
                        <td>150100</td>
                    </tr>
                    <tr>
                        <td>Qodirov Javlon</td>
                        <td>10:15</td>
                        <td><span class="status pending">Kutilmoqda</span></td>
                        <td>Namangan</td>
                        <td>160100</td>
                    </tr>
                    <tr>
                        <td>Abdullayev Karim</td>
                        <td>11:45</td>
                        <td><span class="status rejected">Rad etildi</span></td>
                        <td>Andijon</td>
                        <td>170100</td>
                    </tr>
                    <tr>
                        <td>Malik Odijon</td>
                        <td>14:20</td>
                        <td><span class="status confirmed">Tasdiqlandi</span></td>
                        <td>Qashqadarya</td>
                        <td>180100</td>
                    </tr>
                    <tr>
                        <td>Safarov Dilmurod</td>
                        <td>15:00</td>
                        <td><span class="status pending">Kutilmoqda</span></td>
                        <td>Samarqand</td>
                        <td>140100</td>
                    </tr>
                    <tr>
                        <td>Xolmatov Farhod</td>
                        <td>16:30</td>
                        <td><span class="status confirmed">Tasdiqlandi</span></td>
                        <td>Toshkent</td>
                        <td>100000</td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                Jami: <strong>6</strong> ta natija
            </div>
        </div>
    </div>

    <script>
        // Upload button
        document.querySelectorAll('.btn-upload')[0].addEventListener('click', function() {
            alert('Fayl tanlash oynasi ochiladi...');
        });

        // Download button
        document.querySelectorAll('.btn-download')[0].addEventListener('click', function() {
            alert('Fayl yuklab olinadi...');
        });

        // Search functionality
        const searchInputs = document.querySelectorAll('.search-box input');
        const tableRows = document.querySelectorAll('tbody tr');

        searchInputs.forEach(input => {
            input.addEventListener('keyup', filterTable);
        });

        function filterTable() {
            const nameFilter = searchInputs[0].value.toLowerCase();
            const timeFilter = searchInputs[1].value.toLowerCase();
            const statusFilter = searchInputs[2].value.toLowerCase();
            const postCodeFilter = searchInputs[3].value.toLowerCase();

            let visibleRows = 0;

            tableRows.forEach(row => {
                const name = row.cells[0].textContent.toLowerCase();
                const time = row.cells[1].textContent.toLowerCase();
                const status = row.cells[2].textContent.toLowerCase();
                const postCode = row.cells[4].textContent.toLowerCase();

                const matches =
                    name.includes(nameFilter) &&
                    time.includes(timeFilter) &&
                    status.includes(statusFilter) &&
                    postCode.includes(postCodeFilter);

                row.style.display = matches ? '' : 'none';
                if (matches) visibleRows++;
            });

            // Update count
            const footer = document.querySelector('.table-footer');
            footer.innerHTML = `Jami: <strong>${visibleRows}</strong> ta natija`;

            // Show empty state if no results
            if (visibleRows === 0) {
                const tbody = document.querySelector('tbody');
                if (!document.querySelector('.empty-state')) {
                    const emptyRow = document.createElement('tr');
                    emptyRow.innerHTML = '<td colspan="5"><div class="empty-state">Hech qanday ma\'lumot topilmadi</div></td>';
                    tbody.appendChild(emptyRow);
                }
            } else {
                const emptyRow = document.querySelector('.empty-state')?.parentElement;
                if (emptyRow) emptyRow.remove();
            }
        }

        // Hamburger menu toggle
        const hamburger = document.getElementById('hamburger');
        const sidebar = document.getElementById('sidebar');

        hamburger.addEventListener('click', function() {
            hamburger.classList.toggle('active');
            sidebar.classList.toggle('active');
        });

        // Close sidebar when clicking on a link
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            link.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    hamburger.classList.remove('active');
                    sidebar.classList.remove('active');
                }

                // Remove active class from all links
                document.querySelectorAll('.sidebar-menu a').forEach(a => {
                    a.classList.remove('active');
                });

                // Add active class to clicked link
                this.classList.add('active');
            });
        });
    </script>
</div>
</div>
</body>
</html>
