/**
 * Client API Manager — base: /1000saveursproject/api/manager
 */
const ManagerAPI = (function () {
    const BASE = '/1000saveursproject/api/manager';

    async function request(method, path, body = null) {
        const options = {
            method,
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        };
        if (body !== null) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }
        const res = await fetch(BASE + path, options);
        let payload = null;
        const text = await res.text();
        try {
            payload = text ? JSON.parse(text) : null;
        } catch (e) {
            payload = { success: false, message: text || 'Réponse invalide' };
        }
        if (!res.ok) {
            const err = new Error(payload?.message || payload?.error || `HTTP ${res.status}`);
            err.status = res.status;
            err.payload = payload;
            throw err;
        }
        return payload;
    }

    return {
        me: async () => {
            try {
                const response = await fetch('/1000saveursproject/api/auth.php?action=me', {
                    credentials: 'same-origin'
                });
                const data = await response.json();
                return data;
            } catch (error) {
                console.error('Erreur récupération utilisateur:', error);
                return { status: 'error', message: 'Non authentifié' };
            }
        },
        
        // CORRECTION: Appeler l'endpoint dashboard.php de l'API manager
        dashboard: () => request('GET', '/dashboard.php'),
        
        products: {
            list: () => request('GET', '/products.php'),
            get: (id) => request('GET', `/products.php?id=${id}`),
            create: (data) => request('POST', '/products.php', data),
            update: (id, data) => request('PUT', `/products.php?id=${id}`, data),
            remove: (id) => request('DELETE', `/products.php?id=${id}`),
        },
        
        sales: {
            list: (params = {}) => {
                const q = new URLSearchParams(params).toString();
                return request('GET', '/sales.php' + (q ? `?${q}` : ''));
            },
            create: (data) => request('POST', '/sales.php', data),
            getStats: () => request('GET', '/sales-stats.php'),
        },
        
        debts: {
            list: (status) => {
                const q = status ? `?status=${encodeURIComponent(status)}` : '';
                return request('GET', `/debts.php${q}`);
            },
            pay: (id, paidAmount) => request('POST', `/debts.php?id=${id}`, { paidAmount }),
            getStats: () => request('GET', '/debts-stats.php'),
        },
        
        employees: {
            list: (position) => {
                const q = position ? `?position=${encodeURIComponent(position)}` : '';
                return request('GET', `/employees.php${q}`);
            },
            get: (id) => request('GET', `/employees.php?id=${id}`),
            create: (data) => request('POST', '/employees.php', data),
            update: (id, data) => request('PUT', `/employees.php?id=${id}`, data),
            remove: (id) => request('DELETE', `/employees.php?id=${id}`),
            getStats: () => request('GET', '/employees-stats.php'),
        },
        
        stock: {
            movements: (params = {}) => {
                const q = new URLSearchParams(params).toString();
                return request('GET', '/stock-movements.php' + (q ? `?${q}` : ''));
            },
            adjust: (data) => request('POST', '/stock-movements.php', data),
            getCurrentStock: () => request('GET', '/current-stock.php'),
            getLowStock: () => request('GET', '/low-stock.php'),
        },
        
        salary: {
            list: (period) => {
                const q = period ? `?period=${encodeURIComponent(period)}` : '';
                return request('GET', `/salary.php${q}`);
            },
            getStats: () => request('GET', '/salary-stats.php'),
        },
        
        departements: {
            list: () => request('GET', '/departements.php'),
            get: (id) => request('GET', `/departements.php?id=${id}`),
            create: (data) => request('POST', '/departements.php', data),
            update: (id, data) => request('PUT', `/departements.php?id=${id}`, data),
            remove: (id) => request('DELETE', `/departements.php?id=${id}`),
        },
        
        auth: {
            me: () => fetch('/1000saveursproject/api/auth.php?action=me', { 
                credentials: 'same-origin' 
            }).then((r) => r.json()),
            
            logout: async () => {
                const response = await fetch('/1000saveursproject/api/auth.php?action=logout', { 
                    method: 'POST', 
                    credentials: 'same-origin' 
                });
                window.location.href = '/1000saveursproject/public/login.php';
                return response.json();
            },
            
            checkAuth: async () => {
                const response = await fetch('/1000saveursproject/api/auth.php?action=me', {
                    credentials: 'same-origin'
                });
                const data = await response.json();
                if (data.status !== 'success') {
                    window.location.href = '/1000saveursproject/public/login.php';
                    return false;
                }
                return true;
            }
        },
    };
})();

window.ManagerAPI = ManagerAPI;