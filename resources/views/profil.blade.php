@extends('layouts.app')

@section('title', 'Profil')

@push('styles')
<style>
    .profile { max-width: 640px; }
    .profile-head { display: flex; align-items: center; gap: 16px; padding: 20px; border-bottom: 1px solid var(--border); }
    .profile-avatar {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366f1, #3b82f6);
        color: white;
        display: grid;
        place-items: center;
        font-size: 1.6rem;
        font-weight: 700;
        flex: none;
    }
    .profile-head strong { display: block; font-size: 1.1rem; }
    .profile-head span { color: var(--muted); }
    .profile-form { padding: 20px; display: grid; gap: 14px; }
    .profile-form .field label { display: block; font-size: .84rem; font-weight: 600; color: var(--text-2); margin-bottom: 6px; }
    .profile-form .hint { color: var(--muted); font-size: .8rem; margin-top: 4px; }
    .profile-form .row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .profile-section { font-size: .72rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; margin-top: 6px; }
    .profile-actions { display: flex; gap: 8px; justify-content: flex-end; padding: 16px 20px; border-top: 1px solid var(--border); }
    .form-error { color: var(--danger); font-size: .85rem; }
    @media (max-width: 640px) { .profile-form .row { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
    <div class="page-head">
        <div>
            <h1>Profil</h1>
            <p>Ism, email va parolingizni o'zgartiring</p>
        </div>
    </div>

    <div class="card profile">
        <div class="profile-head">
            <div class="profile-avatar" id="avatar">·</div>
            <div>
                <strong id="headName">…</strong>
                <span id="headEmail"></span>
            </div>
        </div>

        <form id="profileForm" novalidate>
            <div class="profile-form">
                <div class="row">
                    <div class="field">
                        <label for="name">Ism</label>
                        <input class="input" type="text" id="name" maxlength="255" required>
                    </div>
                    <div class="field">
                        <label for="email">Email</label>
                        <input class="input" type="email" id="email" required autocomplete="username">
                    </div>
                </div>

                <div class="profile-section">Parolni o'zgartirish</div>
                <div class="row">
                    <div class="field">
                        <label for="password">Yangi parol</label>
                        <input class="input" type="password" id="password" autocomplete="new-password" placeholder="O'zgartirmaslik uchun bo'sh qoldiring">
                        <div class="hint">Kamida 6 ta belgi</div>
                    </div>
                    <div class="field">
                        <label for="passwordConfirm">Parolni takrorlang</label>
                        <input class="input" type="password" id="passwordConfirm" autocomplete="new-password">
                    </div>
                </div>
                <div class="form-error" id="formError" hidden></div>
            </div>

            <div class="profile-actions">
                <a href="/dashboard" class="btn btn-secondary">Bekor qilish</a>
                <button type="submit" class="btn btn-primary" id="saveBtn">Saqlash</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const { api, toast } = App;
    const form = document.getElementById('profileForm');
    const errorBox = document.getElementById('formError');
    const saveBtn = document.getElementById('saveBtn');
    const fields = Object.fromEntries(['name', 'email', 'password', 'passwordConfirm'].map(id => [id, document.getElementById(id)]));

    function paint(user) {
        const name = user.name || user.email || '';
        document.getElementById('headName').textContent = name;
        document.getElementById('headEmail').textContent = user.email || '';
        document.getElementById('avatar').textContent = name.charAt(0).toUpperCase() || '·';
        // tepadagi menyu ham yangilansin
        document.getElementById('userName').textContent = name;
        document.getElementById('userEmail').textContent = user.email || '';
        if (!user.avatar) document.getElementById('userBtn').textContent = name.charAt(0).toUpperCase();
    }

    function showError(message) {
        errorBox.hidden = !message;
        errorBox.textContent = message || '';
    }

    api('/api/user').then(user => {
        fields.name.value = user.name || '';
        fields.email.value = user.email || '';
        paint(user);
    }).catch(e => showError(e.message));

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const name = fields.name.value.trim();
        const email = fields.email.value.trim();
        const password = fields.password.value;

        if (!name || !email) return showError('Ism va emailni kiriting');
        if (password && password.length < 6) return showError("Parol kamida 6 ta belgidan iborat bo'lishi kerak");
        if (password !== fields.passwordConfirm.value) return showError('Parollar bir xil emas');

        showError('');
        const ok = await App.confirm({
            title: "O'zgarishlar saqlansinmi?",
            message: password ? "Ism, email va parol yangilanadi. Keyingi safar yangi parol bilan kirasiz." : 'Ism va email yangilanadi.',
            confirmText: 'Saqlash',
            icon: 'user',
        });
        if (!ok) return;

        saveBtn.disabled = true;
        try {
            const result = await api('/api/user/update', {
                method: 'PUT',
                body: JSON.stringify({ name, email, password: password || null }),
            });
            fields.password.value = '';
            fields.passwordConfirm.value = '';
            if (result.user) paint(result.user);
            toast(result.message || 'Saqlandi');
        } catch (err) {
            showError(err.message);
        } finally {
            saveBtn.disabled = false;
        }
    });
})();
</script>
@endpush
