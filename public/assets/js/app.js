document.querySelector('[data-print]')?.addEventListener('click', () => window.print());
const estimate = document.querySelector('.estimate');
if (estimate) {
    const form = estimate.closest('form');
    const quantities = form.querySelectorAll('[data-unit]');
    const money = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' });
    const update = () => {
        const total = Array.from(quantities).reduce((sum, input) => sum + Number(input.value || 0) * Number(input.dataset.unit), 0);
        estimate.textContent = 'Estimasi total ' + money.format(total);
    };
    form.addEventListener('input', update);
    update();
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
