<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma'lumotlar Boshqaruvi</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
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
            <a href="/dashboard">
                <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                Bosh Sahifaga Qaytish
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
            <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" hidden>
            <button class="btn btn-upload" id="uploadBtn">
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
                    <label>Tashkilot Nomi bo'yicha qidirish</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" id="searchCompanyName" placeholder="Company name">
                    </div>
                </div>

                <div class="search-box">
                    <label>Vaqtdan boshlab</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="date" id="searchDateFrom">
                    </div>
                </div>

                 <div class="search-box">
                    <label>Vaqtgacha</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="date" id="searchDateTo">
                    </div>
                </div>

                <div class="search-box">
                    <label>Avtomabil bo'yicha</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" id="searchCarNumber" placeholder="Car number">
                    </div>
                </div>

                <div class="search-box">
                    <label>Kirish Tartib Raqami bo'yicha</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" id="searchInputSequence" placeholder="Input sequence number">
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
                        <th>Tartib Raqam</th>
                        <th>Kirish Tartib Raqami</th>
                        <th>Avtomabil</th>
                        <th>Telefon</th>
                        <th>Sana</th>
                        <th>Kirish Joyi</th>
                        <th>Tashkilot Nomi</th>
                    </tr>
                    </thead>
                    <tbody id="tableBody">
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                Jami: <strong>6</strong> ta natija
            </div>
        </div>
        <div id="pagination" style="padding:20px;text-align:center;"></div>
    </div>
</div>

<script>
    // Download button
    document.querySelector('.btn-download').addEventListener('click', async function () {

    const token = localStorage.getItem('token');

    const inputSequence = document.getElementById('searchInputSequence').value;
    const carNumber = document.getElementById('searchCarNumber').value;
    const companyName = document.getElementById('searchCompanyName').value;
    const dateFrom = document.getElementById('searchDateFrom').value;
    const dateTo = document.getElementById('searchDateTo').value;
    
    let url = new URL('/api/turkey/export', window.location.origin);

    if (inputSequence) url.searchParams.append('input_sequence_number', inputSequence);
    if (carNumber) url.searchParams.append('car_number', carNumber);
    if (companyName) url.searchParams.append('company_name', companyName);
    if (dateFrom) url.searchParams.append('date_from', dateFrom);
    if (dateTo) url.searchParams.append('date_to', dateTo);

    try {

        const response = await fetch(url, {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });

        if (!response.ok) {
            alert("Fayl yuklab bo'lmadi");
            return;
        }

        const blob = await response.blob();
        const downloadUrl = window.URL.createObjectURL(blob);

        const a = document.createElement('a');
        a.href = downloadUrl;
        a.download = "turkey_export.xlsx"; // default nom
        document.body.appendChild(a);
        a.click();
        a.remove();

        window.URL.revokeObjectURL(downloadUrl);

    } catch (error) {
        console.error(error);
        alert("Xatolik yuz berdi");
    }

});


    document.querySelectorAll('#searchInputSequence, #searchCarNumber, #searchCompanyName, #searchDateFrom, #searchDateTo')
        .forEach(input => {
            input.addEventListener('input', () => loadTurkeyData(1));
    });



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

    const uploadBtn = document.getElementById('uploadBtn');
    const fileInput = document.getElementById('fileInput');
    const token = localStorage.getItem('token');

    uploadBtn.addEventListener('click', () => {
        fileInput.click();
    });

    fileInput.addEventListener('change', async function () {
        const file = this.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('file', file);

        try {
            uploadBtn.innerText = "Yuklanmoqda...";
            uploadBtn.disabled = true;

            const response = await fetch('/api/turkey/import', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                },
                body: formData
            });

            const result = await response.json();

            alert(result.message);
            loadTurkeyData();

        } catch (error) {
            console.error(error);
            alert("Xatolik yuz berdi!");
        } finally {
            uploadBtn.innerText = "Fayl Yuklash";
            uploadBtn.disabled = false;
        }
    });
    document.querySelectorAll('#searchInputSequence, #searchCarNumber, #searchCompanyName, #searchDate')
        .forEach(input => {
            input.addEventListener('input', loadTurkeyData);
        });

    async function loadTurkeyData(page = 1) {

        const inputSequence = document.getElementById('searchInputSequence').value;
        const carNumber = document.getElementById('searchCarNumber').value;
        const companyName = document.getElementById('searchCompanyName').value;
        const dateFrom = document.getElementById('searchDateFrom').value;
        const dateTo = document.getElementById('searchDateTo').value;

        let url = new URL('/api/turkey', window.location.origin);
        url.searchParams.append('page', page);

        if (inputSequence) url.searchParams.append('input_sequence_number', inputSequence);
        if (carNumber) url.searchParams.append('car_number', carNumber);
        if (companyName) url.searchParams.append('company_name', companyName);
        if (dateFrom) url.searchParams.append('date_from', dateFrom);
        if (dateTo) url.searchParams.append('date_to', dateTo);

        try {
            const response = await fetch(url, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });
            const result = await response.json();

            const tableBody = document.getElementById('tableBody');
            const pagination = document.getElementById('pagination');

            tableBody.innerHTML = '';
            pagination.innerHTML = '';

            // 🔥 MUHIM
            const paginated = result.data;     // pagination object
            const rows = paginated.data;       // real array

            rows.forEach(item => {
                tableBody.innerHTML += `
                <tr>
                    <td>${item.ordinal_number ?? '-'}</td>
                    <td>${item.input_sequence_number ?? '-'}</td>
                    <td>${item.car_number ?? '-'}</td>
                    <td>${item.phone ?? '-'}</td>
                    <td>${item.date ?? '-'}</td>
                    <td>${item.entrance ?? '-'}</td>
                    <td>${item.company_name ?? '-'}</td>
                </tr>
            `;
            });

            // Footer
            document.querySelector('.table-footer').innerHTML =
                `Jami: <strong>${paginated.total}</strong> ta natija`;

            // Pagination tugmalar
            for (let i = 1; i <= paginated.last_page; i++) {

                if (
                    i === 1 ||
                    i === paginated.last_page ||
                    Math.abs(i - paginated.current_page) <= 2
                ) {
                    pagination.innerHTML += `
                    <button onclick="loadTurkeyData(${i})"
                        style="
                            margin:4px;
                            padding:6px 12px;
                            border-radius:6px;
                            border:1px solid #1976D2;
                            background:${i === paginated.current_page ? '#1976D2' : 'white'};
                            color:${i === paginated.current_page ? 'white' : '#1976D2'};
                            cursor:pointer;
                        ">
                        ${i}
                    </button>
                `;
                }
            }

        } catch (error) {
            console.error(error);
            alert("Ma'lumotlarni yuklashda xatolik!");
        }
    }




    // Sahifa yuklanganda chaqiramiz
    loadTurkeyData();
    [
        'searchInputSequence',
        'searchCarNumber',
        'searchCompanyName',
        'searchDateFrom',
        'searchDateTo'
    ].forEach(id => {
        document.getElementById(id).addEventListener('input', () => {
            loadTurkeyData(1);
        });
    });


</script>
</body>
</html>
