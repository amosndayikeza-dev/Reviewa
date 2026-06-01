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
        me: () => request('GET', '/me.php'),
        dashboard: () => request('GET', '/1000saveursproject/public/manager/dashboardmanager.html'),
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
        },
        debts: {
            list: (status) => {
                const q = status ? `?status=${encodeURIComponent(status)}` : '';
                return request('GET', `/debts.php${q}`);
            },
            pay: (id, paidAmount) => request('POST', `/debts.php?id=${id}`, { paidAmount }),
        },
        employees: {
            list: (position) => {
                const q = position ? `?position=${encodeURIComponent(position)}` : '';
                return request('GET', `/employees.php${q}`);
            },
            create: (data) => request('POST', '/employees.php', data),
            remove: (id) => request('DELETE', `/employees.php?id=${id}`),
        },
        stock: {
            movements: (params = {}) => {
                const q = new URLSearchParams(params).toString();
                return request('GET', '/stock-movements.php' + (q ? `?${q}` : ''));
            },
            adjust: (data) => request('POST', '/stock-movements.php', data),
        },
        salary: {
            list: (period) => {
                const q = period ? `?period=${encodeURIComponent(period)}` : '';
                return request('GET', `/salary.php${q}`);
            },
        },
        auth: {
            me: () => fetch('/1000saveursproject/api/auth.php?action=me', { credentials: 'same-origin' }).then((r) => r.json()),
            logout: () => fetch('/1000saveursproject/api/auth.php?action=logout', { method: 'POST', credentials: 'same-origin' }),
        },
    };
})();

window.ManagerAPI = ManagerAPI;
