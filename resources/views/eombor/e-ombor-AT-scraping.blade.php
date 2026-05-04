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
                    <label>Hujjat raqami bo'yicha qidirish</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" id="searchDocument" placeholder="Hujjat raqam">
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
                    <label>INN bo'yicha</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" id="searchInn" placeholder="INN">
                    </div>
                </div>

                <div class="search-box">
                    <label>Avto Raqam bo'yicha</label>
                    <div class="input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" id="searchTransport" placeholder="Avto raqam">
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
                        <th>Xujjat Raqami</th>
                        <th>Kod</th>
                        <th>Vaqt</th>
                        <th>Avto Raqami</th>
                        <th>Gross Vazni</th>
                        <th>INN</th>
                        <th>Qabul Qilivchi Ismi</th>
                        <th>Yetkazib berish Posti</th>
                        <th>Yetkazib berish Sanasi</th>
                        <th>Kelish Joyi</th>
                        <th>Holat</th>
                    </tr>
                    </thead>
                    <tbody id="tableBody">

                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                Jami: <strong>6</strong> ta natija
            </div>
            <div id="pagination" style="padding:20px; text-align:center;"></div>
        </div>
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

        // File tanlanganda API ga yuboramiz
        fileInput.addEventListener('change', async function () {

            const file = this.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);

            try {

                uploadBtn.innerText = "Yuklanmoqda...";
                uploadBtn.disabled = true;

                const response = await fetch('/api/at-e-ombor/import', {
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
                    loadEomborData();
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
        document.querySelector('.btn-download').addEventListener('click',async  function () {

            const documentNumber = document.getElementById('searchDocument').value;
            const customDate = document.getElementById('searchDate').value;
            const transportNumber = document.getElementById('searchTransport').value;
            const inn = document.getElementById('searchInn').value;

            let url = new URL('/api/at-e-ombor/export', window.location.origin);

            if (documentNumber) url.searchParams.append('document_number', documentNumber);
            if (customDate) url.searchParams.append('custom_date', customDate);
            if (transportNumber) url.searchParams.append('transport_number', transportNumber);
            if (inn) url.searchParams.append('inn', inn);

            // Browser o‘zi file yuklab oladi
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
                a.download = "AT-e-ombor.xlsx";
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

        async function loadEomborData(page = 1) {

            const documentNumber = document.getElementById('searchDocument').value;
            const customDate = document.getElementById('searchDate').value;
            const transportNumber = document.getElementById('searchTransport').value;
            const inn = document.getElementById('searchInn').value;

            let url = new URL('/api/at-e-ombor', window.location.origin);


            url.searchParams.append('page', page);

            if (documentNumber) url.searchParams.append('document_number', documentNumber);
            if (customDate) url.searchParams.append('custom_date', customDate);
            if (transportNumber) url.searchParams.append('transport_number', transportNumber);
            if (inn) url.searchParams.append('inn', inn);

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

                if (!result.status) return;

                result.eombors.data.forEach(item => {
                    tableBody.innerHTML += `
                <tr>
                    <td>${item.document_number ?? '-'}</td>
                    <td>${item.custom_code ?? '-'}</td>
                    <td>${item.custom_date ?? '-'}</td>
                    <td>${item.transport_number ?? '-'}</td>
                    <td>${item.gross_weight ?? '-'}</td>
                    <td>${item.inn ?? '-'}</td>
                    <td>${item.recipient_name ?? '-'}</td>
                    <td>${item.delivery_post ?? '-'}</td>
                    <td>${item.delivery_date ?? '-'}</td>
                    <td>${item.arrival_place ?? '-'}</td>
                    <td>${item.status ?? '-'}</td>
                </tr>
            `;
                });

                document.querySelector('.table-footer').innerHTML =
                    `Jami: <strong>${result.eombors.total}</strong> ta yozuv`;

                // Pagination
                for (let i = 1; i <= result.eombors.last_page; i++) {

                    if (
                        i === 1 ||
                        i === result.eombors.last_page ||
                        Math.abs(i - result.eombors.current_page) <= 2
                    ) {

                        pagination.innerHTML += `
                    <button onclick="loadEomborData(${i})"
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


        loadEomborData();

        const filters = [
            'searchDocument',
            'searchDate',
            'searchTransport',
            'searchInn'
        ];

        filters.forEach(id => {
            document.getElementById(id).addEventListener('input', () => {
                loadEomborData(1);
            });
        });


    </script>
</div>
</body>
</html>
