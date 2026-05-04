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
<x-belarus.sidebar></x-belarus.sidebar>

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
                    <label>Qo'ng'iroq tartibi bo'yicha qidirish</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" id="searchCallOrder" placeholder="Qo'ng'iroq tartibi">
                    </div>
                </div>

                <div class="search-box">
                    <label>Ro'yxatdan O'tish Raqami bo'yicha</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" id="searchCarNumber" placeholder="Ro'yxat raqami">
                    </div>
                </div>

                <div class="search-box">
                    <label>Ro'yxatdan O'tgan Sana bo'yicha</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="date" id="searchRegistrationDate">
                    </div>
                </div>

                <div class="search-box">
                    <label>Holat O'zgartirilgan sana bo'yicha</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="date" id="searchStatusChanged">
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
                        <th>Qo'ng'iroq Qilish Tartibi</th>
                        <th>Navbat Turi</th>
                        <th>Ro'yxatdan O'tish Raqami</th>
                        <th>ZO da Ro'yxatdan O'tgan Sana</th>
                        <th>Holat O'zgartirildi</th>
                        <th>Holat</th>
                        <th>Tashkilot Nomi</th>
                    </tr>
                    </thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>
            <div class="table-footer">
                Jami: <strong>0</strong> ta natija
            </div>
        </div>
        <div id="pagination" style="padding:20px;text-align:center;"></div>
    </div>

    <script>
        // Upload button
        const uploadBtn = document.getElementById('uploadBtn');
        const fileInput = document.getElementById('fileInput');
        const token = localStorage.getItem('token');


        // Button bosilganda file tanlash oynasi ochiladi
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

                const response = await fetch('/api/belarus-kozlovichi/import', {
                    method: 'POST',
                     headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const result = await response.json();

                if (!response.ok) {
                    alert(result.message || "Xatolik yuz berdi");
                } else {
                    alert(result.message || "Fayl muvaffaqiyatli yuklandi");
                    await loadBelarusData();

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
        const downloadBtn = document.querySelector('.btn-download');

        downloadBtn.addEventListener('click',async function () {

            const callOrder = document.getElementById('searchCallOrder')?.value;
            const carNumber = document.getElementById('searchCarNumber')?.value;
            const registrationDate = document.getElementById('searchRegistrationDate')?.value;
            const statusChanged = document.getElementById('searchStatusChanged')?.value;

            let url = new URL('/api/belarus-kozlovichi/export', window.location.origin);

            if (callOrder) url.searchParams.append('call_order', callOrder);
            if (carNumber) url.searchParams.append('car_number', carNumber);
            if (registrationDate) url.searchParams.append('registration_date', registrationDate);
            if (statusChanged) url.searchParams.append('status_changed', statusChanged);

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
                a.download = "belarus_kozlovichi_export.xlsx";
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
        async function loadBelarusData(page = 1) {

            const callOrder = document.getElementById('searchCallOrder')?.value;
            const carNumber = document.getElementById('searchCarNumber')?.value;
            const registrationDate = document.getElementById('searchRegistrationDate')?.value;
            const statusChanged = document.getElementById('searchStatusChanged')?.value;

            let url = new URL('/api/belarus-kozlovichi', window.location.origin);

            url.searchParams.append('page', page);

            if (callOrder) url.searchParams.append('call_order', callOrder);
            if (carNumber) url.searchParams.append('car_number', carNumber);
            if (registrationDate) url.searchParams.append('registration_date', registrationDate);
            if (statusChanged) url.searchParams.append('status_changed', statusChanged);

            try {

                const response = await fetch(url,{
                     headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                const result = await response.json();

                if (!result.status) return;

                const tableBody = document.getElementById('tableBody');
                const pagination = document.getElementById('pagination');

                tableBody.innerHTML = '';
                pagination.innerHTML = '';

                const data = result.data.data; // 🔥 paginate ichidagi data
                const meta = result.data;

                if (data.length === 0) {
                    tableBody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align:center;padding:30px;">
                        Hech qanday ma'lumot topilmadi
                    </td>
                </tr>
            `;
                    return;
                }

                data.forEach(item => {
                    tableBody.innerHTML += `
                <tr>
                    <td>${item.call_order ?? '-'}</td>
                    <td>${item.queue_type ?? '-'}</td>
                    <td>${item.car_number ?? '-'}</td>
                    <td>${item.date_of_registration_in_the_zo ?? '-'}</td>
                    <td>${item.status_changed ?? '-'}</td>
                    <td>${item.status ?? '-'}</td>
                    <td>${item.company_name ?? '-'}</td>
                </tr>
            `;
                });

                // Footer
                document.querySelector('.table-footer').innerHTML =
                    `Jami: <strong>${meta.total}</strong> ta natija`;

                // 🔥 Pagination tugmalar
                for (let i = 1; i <= meta.last_page; i++) {

                    if (
                        i === 1 ||
                        i === meta.last_page ||
                        Math.abs(i - meta.current_page) <= 2
                    ) {

                        pagination.innerHTML += `
                    <button onclick="loadBelarusData(${i})"
                        style="
                            margin:4px;
                            padding:6px 12px;
                            border-radius:6px;
                            border:1px solid #1976D2;
                            background:${i === meta.current_page ? '#1976D2' : 'white'};
                            color:${i === meta.current_page ? 'white' : '#1976D2'};
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


        loadBelarusData();
        [
            'searchCallOrder',
            'searchCarNumber',
            'searchRegistrationDate',
            'searchStatusChanged'
        ].forEach(id => {
            document.getElementById(id)?.addEventListener('input', () => {
                loadBelarusData(1);
            });
        });



    </script>
</div>
</body>
</html>
