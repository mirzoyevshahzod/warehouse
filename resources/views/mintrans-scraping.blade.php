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
        <div class="button-group" >
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
                    <label>Litsenziya bo'yicha qidirish</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" id="searchLicense" placeholder="Litsenziya">
                    </div>
                </div>

                <div class="search-box">
                    <label>Vaqt bo'yicha</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="date" id="searchDate">
                    </div>
                </div>

                <div class="search-box">
                    <label>Davlat raqam bo'yicha</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" id="searchState" placeholder="Davlat raqam">
                    </div>
                </div>

                <div class="search-box">
                    <label>Korxona nomi bo'yicha</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" id="searchCompany" placeholder="Korxona nomi">
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
                        <th>Rusumi</th>
                        <th>Yuk Ko'tarish Qobilyati</th>
                        <th>Litsenziya Varaqasi</th>
                        <th>Davlat Raqami</th>
                        <th>Korxona Nomi</th>
                        <th>Faoliyat Turi</th>
                        <th>Transport Turi</th>
                        <th>Yuk Turi</th>
                        <th>Belgilangan Sana</th>
                        <th>Holati</th>
                        <th>INN</th>
                        <th>Hududiy Boshqarma</th>
                    </tr>
                    </thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>
            <div class="table-footer">
                Jami: <strong>0</strong> ta natija
            </div>
        </div>
        <div id="pagination" style="padding:15px;text-align:center;"></div>
    </div>

    <script>
        // Upload button
        const uploadBtn = document.getElementById('uploadBtn');
        const fileInput = document.getElementById('fileInput');
        const token = localStorage.getItem('token');

        // Tugma bosilganda file tanlash oynasi ochiladi
        uploadBtn.addEventListener('click', () => {
            fileInput.click();
        });

        // File tanlanganda API ga yuboriladi
        fileInput.addEventListener('change', async function () {

            const file = this.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);

            try {

                uploadBtn.innerText = "Yuklanmoqda...";
                uploadBtn.disabled = true;

                const response = await fetch('/api/mintrans/import', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`
                    },
                    body: formData
                });

                const result = await response.json();

                if (!response.ok) {
                    alert(result.message || "Xatolik yuz berdi");
                } else {
                    alert(result.message || "Fayl muvaffaqiyatli yuklandi");
                }

            } catch (error) {
                console.error(error);
                alert("Server bilan bog'lanishda xatolik!");
            } finally {
                uploadBtn.innerText = "Fayl Yuklash";
                uploadBtn.disabled = false;
                fileInput.value = '';
            }
        });


        // Download button
      document.querySelector('.btn-download').addEventListener('click', async function() {

        const token = localStorage.getItem('token');

        const license = document.getElementById('searchLicense')?.value;
        const state = document.getElementById('searchState')?.value;
        const date = document.getElementById('searchDate')?.value;
        const company = document.getElementById('searchCompany')?.value;

        let url = new URL('/api/mintrans/export', window.location.origin);

        if (license) url.searchParams.append('license_number', license);
        if (state) url.searchParams.append('state_number', state);
        if (date) url.searchParams.append('date_given', date);
        if (company) url.searchParams.append('company_name', company);

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
            a.download = "mintrans_export.xlsx";
            document.body.appendChild(a);
            a.click();
            a.remove();

            window.URL.revokeObjectURL(downloadUrl);

        } catch (error) {
            console.error(error);
            alert("Xatolik yuz berdi");
        }

    });



        // Search functionality

        let currentPage = 1;

        async function loadMintransData(page = 1) {

            const license = document.getElementById('searchLicense')?.value;
            const state = document.getElementById('searchState')?.value;
            const date = document.getElementById('searchDate')?.value;
            const company = document.getElementById('searchCompany')?.value;

            let url = new URL('/api/mintrans', window.location.origin);

            url.searchParams.append('page', page);

            if (license) url.searchParams.append('license_number', license);
            if (state) url.searchParams.append('state_number', state);
            if (date) url.searchParams.append('date_given', date);
            if (company) url.searchParams.append('company_name', company);

            try {
                const response = await fetch(url, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                const result = await response.json();

                const tableBody = document.getElementById('tableBody');
                tableBody.innerHTML = '';

                result.data.forEach(item => {
                    tableBody.innerHTML += `
                <tr>
                    <td>${item.model ?? '-'}</td>
                    <td>${item.load_capacity ?? '-'}</td>
                    <td>${item.license_number ?? '-'}</td>
                    <td>${item.state_number ?? '-'}</td>
                    <td>${item.company_name ?? '-'}</td>
                    <td>${item.type_of_activity ?? '-'}</td>
                    <td>${item.transport_type ?? '-'}</td>
                    <td>${item.cargo_type ?? '-'}</td>
                    <td>${item.date_given ?? '-'}</td>
                    <td>${item.status ?? '-'}</td>
                    <td>${item.inn ?? '-'}</td>
                    <td>${item.territorial_management ?? '-'}</td>
                </tr>
            `;
                });

                document.querySelector('.table-footer').innerHTML =
                    `Jami: <strong>${result.total}</strong> ta natija`;

                renderPagination(result);

            } catch (error) {
                console.error(error);
            }
        }

        function renderPagination(data) {

            const pagination = document.getElementById('pagination');
            pagination.innerHTML = '';

            const current = data.current_page;
            const last = data.last_page;

            let pages = [];

            // Har doim birinchi sahifa
            pages.push(1);

            // Current atrofidagi 2 ta sahifa
            for (let i = current - 2; i <= current + 2; i++) {
                if (i > 1 && i < last) {
                    pages.push(i);
                }
            }

            // Har doim oxirgi sahifa
            if (last > 1) {
                pages.push(last);
            }

            // Duplicate olib tashlaymiz
            pages = [...new Set(pages)].sort((a, b) => a - b);

            let prev = 0;

            pages.forEach(page => {

                if (page - prev > 1) {
                    pagination.innerHTML += `<span style="margin:6px;">...</span>`;
                }

                pagination.innerHTML += `
            <button
                onclick="loadMintransData(${page})"
                style="
                    margin:4px;
                    padding:6px 12px;
                    border-radius:6px;
                    border:1px solid #ccc;
                    background:${page === current ? '#1976D2' : '#fff'};
                    color:${page === current ? '#fff' : '#000'};
                    cursor:pointer;
                ">
                ${page}
            </button>
        `;

                prev = page;
            });
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

        loadMintransData();
        [
            'searchLicense',
            'searchState',
            'searchDate',
            'searchCompany'
        ].forEach(id => {
            document.getElementById(id)?.addEventListener('input', () => {
                loadMintransData(1);
            });
        });


    </script>
</div>
</body>
</html>
