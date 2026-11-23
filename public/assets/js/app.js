document.querySelector('[data-print]')?.addEventListener('click', () => window.print());
const paymentAction = document.querySelector('input[name="action"][value="pay"]');
if (paymentAction) {
    const form = paymentAction.form;
    const amount = form.elements.namedItem('diterima');
    const method = form.elements.namedItem('metode');
    const serviceId = Number(form.elements.namedItem('servis_id').value);
    const fixedTotal = Number(form.elements.namedItem('total_estimasi').value);
    const quantities = form.querySelectorAll('[data-unit]');
    const feedback = form.querySelector('.payment-feedback');
    const estimate = form.querySelector('.estimate');
    const money = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' });
    const dialog = document.querySelector('.payment-dialog');
    const dialogMessage = document.querySelector('#payment-dialog-message');
    const closeDialog = document.querySelector('[data-close-payment]');
    const submit = form.querySelector('button[type="submit"]');
    let saving = false;
    form.noValidate = true;
    const showWarning = message => {
        dialogMessage.textContent = message;
        if (!dialog.open) dialog.showModal();
    };
    closeDialog.addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => amount.focus());
    amount.inputMode = 'decimal';
    amount.setAttribute('aria-describedby', 'payment-feedback');
    feedback.id = 'payment-feedback';

    const updatePayment = () => {
        const total = serviceId ? fixedTotal : Array.from(quantities).reduce((sum, input) => {
            const unit = Math.round(Number(input.dataset.unit) * 100);
            return sum + Number(input.value || 0) * unit;
        }, 0);
        if (estimate) {
            estimate.textContent = 'Estimasi total ' + money.format(total / 100);
        }
        const rawAmount = amount.value.trim();
        const validFormat = /^\d{1,10}(?:\.\d{1,2})?$/.test(rawAmount);
        const received = validFormat ? Math.round(Number(rawAmount) * 100) : 0;
        let message = '';
        if (total <= 0) {
            message = 'Pilih item dengan total lebih dari nol.';
        } else if (rawAmount === '') {
            message = 'Isi nominal pembayaran yang diterima.';
        } else if (!validFormat) {
            message = 'Isi angka tanpa pemisah ribuan, misalnya 80000 atau 80000.50.';
        } else if (received < total) {
            message = 'Nominal kurang ' + money.format((total - received) / 100) + '.';
        } else if (method.value !== 'Tunai' && received !== total) {
            message = 'Nominal Transfer/QRIS harus sama dengan total ' + money.format(total / 100) + '.';
        }
        amount.setCustomValidity(message);
        amount.setAttribute('aria-invalid', String(Boolean(message)));
        feedback.className = 'payment-feedback ' + (message ? 'error' : 'success');
        feedback.textContent = message || (method.value === 'Tunai'
            ? 'Nominal cukup. Kembalian ' + money.format((received - total) / 100) + '.'
            : 'Nominal sesuai total tagihan.');
        return !message;
    };
    form.addEventListener('input', updatePayment);
    form.addEventListener('change', updatePayment);
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (saving) return;
        if (!updatePayment()) {
            showWarning(amount.validationMessage);
            return;
        }
        if (!form.reportValidity()) return;
        saving = true;
        submit.disabled = true;
        submit.textContent = 'Menyimpan…';
        try {
            const response = await fetch(form.getAttribute('action') || window.location.href, {
                method: 'POST',
                body: new FormData(form),
                headers: {Accept: 'application/json'},
                credentials: 'same-origin'
            });
            if (response.headers.get('content-type')?.includes('application/json')) {
                const result = await response.json();
                if (response.ok && result.redirect) {
                    window.location.assign(result.redirect);
                } else {
                    showWarning(result.message || 'Pembayaran belum tersimpan. Periksa isian.');
                }
            } else if (response.ok && response.redirected) {
                window.location.assign(response.url);
            } else {
                showWarning('Pembayaran belum dapat dipastikan. Coba simpan kembali dari formulir ini.');
            }
        } catch {
            showWarning('Koneksi terputus. Coba simpan kembali dari formulir ini untuk memeriksa transaksi.');
        } finally {
            saving = false;
            submit.disabled = false;
            submit.textContent = 'Simpan pembayaran';
        }
    });
    updatePayment();
}

const loginPassword = document.querySelector('#login-password');
const passwordToggle = document.querySelector('[data-password-toggle]');
if (loginPassword && passwordToggle) {
    passwordToggle.hidden = false;
    passwordToggle.addEventListener('click', () => {
        const visible = loginPassword.type === 'password';
        loginPassword.type = visible ? 'text' : 'password';
        passwordToggle.setAttribute('aria-pressed', String(visible));
        passwordToggle.setAttribute('aria-label', visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
    });
}

const demoAccounts = document.querySelector('[data-demo-accounts]');
const loginUsername = document.querySelector('#login-username');
if (demoAccounts && loginUsername && loginPassword) {
    const buttons = demoAccounts.querySelectorAll('[data-demo-user]');
    const updateSelected = () => {
        buttons.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.demoUser === loginUsername.value)));
    };
    buttons.forEach(button => button.addEventListener('click', () => {
        loginUsername.value = button.dataset.demoUser;
        updateSelected();
        loginPassword.focus();
    }));
    loginUsername.addEventListener('input', updateSelected);
    demoAccounts.hidden = false;
    updateSelected();
}
