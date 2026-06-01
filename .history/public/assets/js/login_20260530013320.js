// login.js - Gestion de l'authentification

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const errorDiv = document.getElementById('error-message');

    loginForm.addEventListener('submit', async function(event) {
        event.preventDefault();

        const email = emailInput.value.trim();
        const password = passwordInput.value;

        if (!email || !password) {
            // errorDiv.textContent = Veuillez remplir tous les champs.';
            errorDiv.innerHTML = `
            <div class="tft-icon-carre-moyen tft-bg-red">
                <i class="fas fa-exclamation-triangle tft-clr-remain-white"></i>
            </div>
            <p class="tft-title1">Veuillez remplir tous les champs</p>`;
            return;
        }

        try {
            const response = await fetch('/1000saveursproject/api/auth.php?action=login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email, password })
            });

            const data = await response.json();

            if (response.ok && data.status === 'success') {
                const user = data.user || {};
                const role = String(user.role || '').toLowerCase().trim();
                const isDeptManager = Boolean(user.isDepartmentManager);
                const managerDashboard = '/1000saveursproject/public/manager/dashboardmanager.html';

                if (role === 'patron') {
                    window.location.href = '/1000saveursproject/public/patron/dashboardpatron.php';
                } else if (role === 'manager' || role === 'gerant' || role === 'gérant' || isDeptManager) {
                    window.location.href = managerDashboard;
                } else if (role === 'admin') {
                    window.location.href = managerDashboard;
                } else {
                    window.location.href = '/1000saveursproject/public/login.html';
                }
            } else {
                // errorDiv.textContent = data.message || 'Email ou mot de passe incorrect.';
                errorDiv.innerHTML = `
                <div class="tft-icon-carre-moyen tft-bg-red">
                    <i class="fas fa-warning tft-clr-remain-white"></i>
                </div>
                <p class="tft-title1">${data.message || 'Email ou mot de passe incorrect'}</p>`;
            }
        } catch (error) {
            console.error('Erreur de connexion :', error);
            // errorDiv.textContent ='Erreur de connexion au serveur. Veuillez réessayer.';
            errorDiv.innerHTML = `
            <div class="tft-icon-carre-moyen tft-bg-red">
                <i class="fas fa-warning tft-clr-remain-white"></i>
            </div>
            <p class="tft-title1">Erreur de connexion au serveur. Veuillez réessayer</p>`;
        }
    });
});