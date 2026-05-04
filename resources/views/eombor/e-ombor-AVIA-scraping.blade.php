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
<x-eombor.sidebar></x-eombor.sidebar>

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
                    <label>
                        Yuk Varaqasi Raqami bo'yicha qidirish</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" id="air_waybill_number" placeholder="Yuk varaqasi...">
                    </div>
                </div>

                <div class="search-box">
                    <label>Parvoz Raqami bo'yicha</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" id="flight_number" placeholder="Parvoz raqami...">
                    </div>
                </div>

                <div class="search-box">
                    <label>Ro'yxatdan o'tish sanasi bo'yicha</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="date" id="registration_date">
                    </div>
                </div>


                <div class="search-box">
                    <label>Bojxona Pochta Indeksi bo'yicha</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" id="border_customs_post_code" placeholder="Post kodi kiriting...">

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
                        <th>Yuk Varaqasi Raqami</th>
                        <th>Parvoz Raqami</th>
                        <th>Ro'yxatdan o'tish sanasi</th>
                        <th>Yuk Qabul Qilivchi</th>
                        <th>Sof Og'irligi</th>
                        <th>Bojxona Pochta Indeksi</th>
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
        <div id="pagination" style="padding:20px; text-align:center;"></div>

    </div>

    <script>
        // Upload button
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

                const response = await fetch('/api/avia-eombor/import', {
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
                    alert(result.message);
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
        document.querySelector('.btn-download').addEventListener('click',async function () {

            const airWaybill = document.getElementById('air_waybill_number').value;
            const registrationDate = document.getElementById('registration_date').value;
            const flightNumber = document.getElementById('flight_number').value;
            const postCode = document.getElementById('border_customs_post_code').value;

            let url = new URL('/api/avia-e-ombor/export', window.location.origin);

            if (airWaybill) url.searchParams.append('air_waybill_number', airWaybill);
            if (registrationDate) url.searchParams.append('registration_date', registrationDate);
            if (flightNumber) url.searchParams.append('flight_number', flightNumber);
            if (postCode) url.searchParams.append('border_customs_post_code', postCode);

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
                a.download = "AVIA-e-ombor.xlsx";
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
        async function loadAviaData(page = 1) {

            const airWaybill = document.getElementById('air_waybill_number').value;
            const registrationDate = document.getElementById('registration_date').value;
            const flightNumber = document.getElementById('flight_number').value;
            const postCode = document.getElementById('border_customs_post_code').value;

            const params = new URLSearchParams({
                page: page,
                air_waybill_number: airWaybill,
                registration_date: registrationDate,
                flight_number: flightNumber,
                border_customs_post_code: postCode
            });

            try {

                const response = await fetch(`/api/avia-e-ombor?${params}`, {
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

                const data = result.eombors.data;

                if (data.length === 0) {
                    tableBody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align:center;padding:30px;">
                        Hech qanday ma'lumot topilmadi
                    </td>
                </tr>
            `;
                }

                data.forEach(item => {
                    tableBody.innerHTML += `
                <tr>
                    <td>${item.air_waybill_number ?? '-'}</td>
                    <td>${item.flight_number ?? '-'}</td>
                    <td>${item.registration_date ?? '-'}</td>
                    <td>${item.consignee ?? '-'}</td>
                    <td>${item.total_net_weight ?? '-'}</td>
                    <td>${item.border_customs_post_code ?? '-'}</td>
                </tr>
            `;
                });

                document.querySelector('.table-footer').innerHTML =
                    `Jami: <strong>${result.eombors.total}</strong> ta yozuv`;

                for (let i = 1; i <= result.eombors.last_page; i++) {

                    if (
                        i === 1 ||
                        i === result.eombors.last_page ||
                        Math.abs(i - result.eombors.current_page) <= 2
                    ) {

                        pagination.innerHTML += `
                    <button onclick="loadAviaData(${i})"
                        style="
                            margin:4px;
                            padding:6px 12px;
                            border-radius:6px;
                            border:1px solid #1976D2;
                            background:${i === result.eombors.current_page ? '#1976D2' : 'white'};
                            color:${i === result.eombors.current_page ? 'white' : '#1976D2'};
                            cursor:pointer;
                        ">
                        ${i}
                    </button>
                `;
                    }
                }

            } catch (error) {
                console.error(error);
                alert("Xatolik yuz berdi");
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

        let currentPage = 1;



        // Sahifa yuklanganda avtomatik chaqiriladi
        loadAviaData();
        document.querySelectorAll('#air_waybill_number, #registration_date, #flight_number, #border_customs_post_code')
            .forEach(input => {
                input.addEventListener('keyup', () => loadAviaData(1));
                input.addEventListener('change', () => loadAviaData(1));
            });


    </script>
</div>
</body>
</html>
