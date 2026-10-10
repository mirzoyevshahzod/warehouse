<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma'lumotlar Boshqaruvi</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<style>
.date-range-box {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #eef1f5;
    padding: 10px 14px;
    border-radius: 12px;
    border: 1px solid #ddd;
    width: 100%;
    max-width: 320px;
}

.date-range-box .label {
    color: #666;
    font-size: 14px;
}

.date-range-box input {
    border: none;
    background: transparent;
    outline: none;
    font-size: 14px;
    color: #333;
    width: 120px;
}
</style>
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
            <a href="{{ route('qozoqScraping') }}"  class="{{ request()->routeIs('qozoqScraping') ? 'active' : '' }}">
                <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                Umumiy
            </a>
        </li>
         <li>
            <a href="{{ route('checkedQozoqScraping') }}" class="{{ request()->routeIs('checkedQozoqScraping') ? 'active' : '' }}">
                <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                Tekshirilganlar(Eksport)
            </a>
        </li>
         <li>
            <a href="{{ route('importQozoqScraping') }}" class="{{ request()->routeIs('importQozoqScraping') ? 'active' : '' }}">
                <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                Import Qozoq
            </a>
        </li>
         <li>
            <a href="{{ route('importEksportQozoqScraping') }}" class="{{ request()->routeIs('importEksportQozoqScraping') ? 'active' : '' }}">
                <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                Import Eksport Qozoq
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
                    <label>Nomi bo'yicha qidirish</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <select id="searchBoundary" style="width:100%;border:none;background:transparent;outline:none;">
                            <option value="">Barchasi</option>
                            <option>Акбалшык – Воскресенское</option>
                            <option>Аксай – Илек</option>
                            <option>Алимбет – Орск</option>
                            <option>Амангельды – Невольное</option>
                            <option>Аят – Николаевка</option>
                            <option>Бидаик – Одесское</option>
                            <option>Жайсан – Сагарчин</option>
                            <option>Жана Жол – Петухово</option>
                            <option>Жаныбек – Вишневка</option>
                            <option>Жезкент – Горняк</option>
                            <option>Желкуар – Мариинский</option>
                            <option>Кайрак – Бугристое</option>
                            <option>Каракога – Исилькуль</option>
                            <option>Карашатау – Светлый</option>
                            <option>Кондыбай – Комсомольский</option>
                            <option>Косак – Павловка</option>
                            <option>Коянбай – Малиновое Озеро</option>
                            <option>Курмангазы – Караузек</option>
                            <option>Кызыл Жар – Казанское</option>
                            <option>Найза – Павловка (Славгород)</option>
                            <option>Орда – Полынный</option>
                            <option>Сырым – Маштаково</option>
                            <option>Таскала – Озинки</option>
                            <option>Убаган – Звериноголовское</option>
                            <option>Убе – Михайловка</option>
                            <option>Урлютобе – Ольховка</option>
                            <option>Шаган – Теплое</option>
                            <option>Шарбакты – Кулунда</option>
                            <option>Ауыл – Веселоярск</option>
                        </select>
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
                    <label>Holat bo'yicha</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" id="searchStatus" placeholder="Holat">

                    </div>
                </div>
                <div class="search-box">
                    <label>Avto raqam bo'yicha</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" id="searchCar" placeholder="Avto raqam">
                    </div>
                </div>
                <div class="search-box">
                    <label>Tashkilot Nomi bo'yicha</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" id="searchCompany" placeholder="Tashkilot nomi">
                    </div>
                </div>
                  <div class="search-box">
                       <label>Avto turi</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <select id="searchCarType" style="width:100%;border:none;background:transparent;outline:none;">
                            <option value="">Barchasi</option>
                            <option value="uz">Faqat O‘zbekiston</option>
                            <option value="foreign">Faqat O‘zbekiston emas</option>
                        </select>
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
                        <th>Chegara nomi</th>
                        <th>Avto raqami</th>
                        <th>Telefon</th>
                        <th>Tashkilot</th>
                        <th>Sana</th>
                        <th>Holati</th>
                    </tr>
                    </thead>

                    <tbody id="tableBody"></tbody>
                </table>
            </div>
            <div class="table-footer">
                Jami: <strong>6</strong> ta natija
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

               const response = await fetch('/api/checked-qozoq/import', {
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
                    await loadQozoqData();
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

        const boundary = document.getElementById('searchBoundary')?.value;
        const car = document.getElementById('searchCar')?.value;
        const dateFrom = document.getElementById('searchDateFrom')?.value;
        const dateTo = document.getElementById('searchDateTo')?.value;
        const status = document.getElementById('searchStatus')?.value;
        const company = document.getElementById('searchCompany')?.value;
        const carType = document.getElementById('searchCarType')?.value;

        let url = new URL('/api/checked-qozoq/export', window.location.origin);

        if (boundary) url.searchParams.append('boundary_name', boundary);
        if (car) url.searchParams.append('car_number', car);
        if (dateFrom) url.searchParams.append('date_from', dateFrom + ' 00:00:00');
        if (dateTo) url.searchParams.append('date_to', dateTo + ' 23:59:59');
        if (status) url.searchParams.append('status', status);
        if (company) url.searchParams.append('company_name', company);
        if (carType) url.searchParams.append('car_type', carType);

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
            a.download = "export.xlsx"; // backend nom bermasa
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
        async function loadQozoqData(page = 1) {

            const boundary = document.getElementById('searchBoundary')?.value;
            const car = document.getElementById('searchCar')?.value;
            const dateFrom = document.getElementById('searchDateFrom')?.value;
            const dateTo = document.getElementById('searchDateTo')?.value;
            const status = document.getElementById('searchStatus')?.value;
            const company = document.getElementById('searchCompany')?.value;
            const carType = document.getElementById('searchCarType')?.value;


            let url = new URL('/api/checked-qozoq', window.location.origin);

            url.searchParams.append('page', page);

            if (boundary) url.searchParams.append('boundary_name', boundary);
            if (car) url.searchParams.append('car_number', car);
            if (dateFrom) url.searchParams.append('date_from', dateFrom + ' 00:00:00');
            if (dateTo) url.searchParams.append('date_to', dateTo + ' 23:59:59');
            if (status) url.searchParams.append('status', status);
            if (company) url.searchParams.append('company_name', company);
            if (carType) url.searchParams.append('car_type', carType);



            const response = await fetch(url, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });
            const result = await response.json();

            const tableBody = document.getElementById('tableBody');
            tableBody.innerHTML = '';

            const paginated = result.data;
            const data = paginated.data;

            if (data.length === 0) {
                tableBody.innerHTML = `
            <tr>
                <td colspan="5" style="text-align:center;padding:30px;">
                    Hech qanday ma'lumot topilmadi
                </td>
            </tr>
        `;
                return;
            }

            data.forEach(item => {
                tableBody.innerHTML += `
            <tr>
                <td>${item.boundary_name ?? '-'}</td>
                <td>${item.car_number ?? '-'}</td>
                <td>${item.phone ?? '-'}</td>
                <td>${item.company_name ?? '-'}</td>
                <td>${item.date_and_time ?? '-'}</td>
                <td>${item.status ?? '-'}</td>
            </tr>
        `;
            });

            document.querySelector('.table-footer').innerHTML =
                `Jami: <strong>${paginated.total}</strong> ta natija`;

            renderPagination(paginated);
        }
        function renderPagination(paginated) {

        const paginationDiv = document.getElementById('pagination');
        paginationDiv.innerHTML = '';

        const current = paginated.current_page;
        const last = paginated.last_page;

        if (last <= 1) return;

        function createButton(page) {
            return `
                <button
                    onclick="loadQozoqData(${page})"
                    style="
                        margin:4px;
                        padding:6px 12px;
                        border-radius:6px;
                        border:1px solid #1976D2;
                        background:${page === current ? '#1976D2' : 'white'};
                        color:${page === current ? 'white' : '#1976D2'};
                        cursor:pointer;
                    ">
                    ${page}
                </button>
            `;
        }

        // 1-sahifa
        paginationDiv.innerHTML += createButton(1);

        // Chap taraf ...
        if (current > 3) {
            paginationDiv.innerHTML += `<span style="margin:0 5px;">...</span>`;
        }

        // Current atrofidagi sahifalar
        for (let i = current - 1; i <= current + 1; i++) {
            if (i > 1 && i < last) {
                paginationDiv.innerHTML += createButton(i);
            }
        }

        // O‘ng taraf ...
        if (current < last - 2) {
            paginationDiv.innerHTML += `<span style="margin:0 5px;">...</span>`;
        }

        // Oxirgi sahifa
        if (last > 1) {
            paginationDiv.innerHTML += createButton(last);
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

        loadQozoqData();
       [
            'searchBoundary',
            'searchCar',
            'searchDateFrom',
            'searchDateTo',
            'searchStatus',
            'searchCompany',
            'searchCarType',
        ].forEach(id => {
            document.getElementById(id)?.addEventListener('input', () => {
                loadQozoqData(1); // 🔥 doim 1-sahifa
            });
        });


    </script>
</div>
</body>
</html>
