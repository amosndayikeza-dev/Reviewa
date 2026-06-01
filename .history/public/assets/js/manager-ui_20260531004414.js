/**
 * Manager UI — chargement des fragments et liaison API.
 */
(function () {
    const fmtMoney = (n) => (Number(n) || 0).toLocaleString('fr-FR') + ' FBU';
    const fmtDate = (d) => {
        if (!d) return '—';
        const dt = new Date(d);
        if (Number.isNaN(dt.getTime())) return String(d);
        return dt.toLocaleDateString('fr-FR');
    };

    let currentPaymentDebtId = null;
    let productsCache = [];

    function showToast(msg, isError) {
        if (typeof showNotification === 'function') {
            showNotification(msg, isError ? 'error' : 'success');
            return;
        }
        alert(msg);
    }

    //model d'ajout du'une vente
    // Ajout d'une vente via la modale générique
    window.showAddSaleModal = async function() {
        // Charger les produits
        const productsRes = await ManagerAPI.products.list();
        const products = productsRes.data || [];
        const productOptions = products.map(p => `<option value="${p.id}" data-price="${p.unit_price}">${escapeHtml(p.name)}</option>`).join('');

        const formHtml = `
            <div class="tft-form-group">
                <label>Produit</label>
                <select id="modal-sale-product" class="tft-form-control" required>
                    <option value="">-- Sélectionner --</option>
                    ${productOptions}
                </select>
            </div>
            <div class="tft-form-group">
                <label>Quantité</label>
                <input type="number" id="modal-sale-quantity" class="tft-form-control" min="1" required>
            </div>
            <div class="tft-form-group">
                <label>Prix unitaire (FBU)</label>
                <input type="number" id="modal-sale-unit-price" class="tft-form-control" step="any" required>
            </div>
            <div class="tft-form-group">
                <label>Date de vente</label>
                <input type="date" id="modal-sale-date" class="tft-form-control" value="${new Date().toISOString().slice(0,10)}" required>
            </div>
            <div class="mgr-total-line" style="margin-top: 15px;">
                <span>Total :</span>
                <span id="modal-sale-total">0 FBU</span>
            </div>
        `;

        showManagerModal('Nouvelle vente', formHtml, async () => {
            const productId = document.getElementById('modal-sale-product').value;
            const quantity = document.getElementById('modal-sale-quantity').value;
            const unitPrice = document.getElementById('modal-sale-unit-price').value;
            const date = document.getElementById('modal-sale-date').value;
            if (!productId || !quantity || !unitPrice || !date) {
                showToast('Veuillez remplir tous les champs', true);
                return; // Empêche la fermeture de la modale
            }
            try {
                await ManagerAPI.sales.create({
                    productId: productId,
                    quantity: quantity,
                    unitPrice: unitPrice,
                    date: date
                });
                showToast('Vente enregistrée avec succès');
                // Recharger la liste des ventes si la fonction existe
                if (typeof loadSalesList === 'function') {
                    await loadSalesList();
                } else {
                    location.reload(); // fallback
                }
            } catch (err) {
                showToast(err.message, true);
                throw err; // La modale ne se fermera pas
            }
        });

        // Attacher les événements de mise à jour du total après l'ouverture de la modale
        setTimeout(() => {
            const productSelect = document.getElementById('modal-sale-product');
            const qtyInput = document.getElementById('modal-sale-quantity');
            const priceInput = document.getElementById('modal-sale-unit-price');
            const totalSpan = document.getElementById('modal-sale-total');

            const updateTotal = () => {
                const q = parseFloat(qtyInput?.value) || 0;
                const p = parseFloat(priceInput?.value) || 0;
                if (totalSpan) totalSpan.textContent = fmtMoney(q * p);
            };

            productSelect?.addEventListener('change', () => {
                const opt = productSelect.options[productSelect.selectedIndex];
                if (opt && opt.dataset.price) {
                    priceInput.value = opt.dataset.price;
                    updateTotal();
                }
            });
            qtyInput?.addEventListener('input', updateTotal);
            priceInput?.addEventListener('input', updateTotal);
            updateTotal();
        }, 0);
    };

    /**========================================================= */

    function stockBadgeClass(status) {
        if (status === 'rupture') return 'tft-stock-badge-red1';
        if (status === 'low') return 'tft-stock-badge-yellow1';
        return 'tft-stock-badge-green1';
    }

    function stockBadgeText(status) {
        if (status === 'rupture') return 'Rupture';
        if (status === 'low') return 'Stock bas';
        return 'OK';
    }

    function debtBadge(status, remaining) {
        if (status === 'paid' || remaining <= 0) return '<span class="mgr-badge mgr-badge-paid">Soldée</span>';
        if (status === 'partial') return '<span class="mgr-badge mgr-badge-partial">Partiel</span>';
        return '<span class="mgr-badge mgr-badge-unpaid">En cours</span>';
    }

    async function loadUserAndDept() {
        try {
            const auth = await ManagerAPI.auth.me();
            if (auth.status === 'success' && auth.user) {
                const name = [auth.user.firstName, auth.user.lastName].filter(Boolean).join(' ') || auth.user.email;
                const el = document.getElementById('userName');
                if (el) el.textContent = name;
                const roleEl = document.getElementById('userRole');
                if (roleEl) roleEl.textContent = 'Manager';
            }
            const me = await ManagerAPI.me();
            if (me.success && me.departementName) {
                document.querySelectorAll('.app-title .tft-title3').forEach((p) => {
                    p.textContent = me.departementName;
                });
            }
        } catch (e) {
            console.warn('Session manager:', e.message);
            if (e.status === 401 || e.status === 403) {
                window.location.href = '/1000saveursproject/public/login.html';
            }
        }
    }

    function initTabs(root) {
        if (!root) return;
        const tabs = root.querySelectorAll('[data-mgr-tab]');
        const panels = root.querySelectorAll('[data-mgr-panel]');
        tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                const name = tab.getAttribute('data-mgr-tab');
                tabs.forEach((t) => t.classList.remove('mgr-tab-active'));
                tab.classList.add('mgr-tab-active');
                panels.forEach((p) => {
                    p.classList.toggle('mgr-panel-active', p.getAttribute('data-mgr-panel') === name);
                });
                if (name === 'history') loadSalesHistory();
                if (name === 'movements') loadStockMovements();
            });
        });
        const urlTab = new URLSearchParams(window.location.search).get('tab');
        if (urlTab) {
            const target = root.querySelector(`[data-mgr-tab="${urlTab}"]`);
            if (target) target.click();
        }
    }

    function fillProductSelects(products) {
        productsCache = products || [];
        document.querySelectorAll('[data-mgr-product-select]').forEach((select) => {
            const current = select.value;
            select.innerHTML = '<option value="">— Choisir un produit —</option>';
            productsCache.forEach((p) => {
                const opt = document.createElement('option');
                opt.value = String(p.id);
                opt.textContent = p.name;
                opt.dataset.price = String(p.unit_price ?? p.unitPrice ?? 0);
                select.appendChild(opt);
            });
            if (current) select.value = current;
        });
    }

    function onProductChange(select) {
        const target = select.getAttribute('data-price-target');
        const priceInput = target ? document.querySelector(target) : null;
        if (!priceInput) return;
        const opt = select.options[select.selectedIndex];
        priceInput.value = opt && opt.dataset.price ? opt.dataset.price : '';
        updateSaleTotal();
    }

    function updateSaleTotal() {
        const qty = document.getElementById('sale-quantity');
        const price = document.getElementById('sale-unit-price');
        const totalEl = document.getElementById('sale-total');
        if (!qty || !price || !totalEl) return;
        const q = parseFloat(qty.value) || 0;
        const p = parseFloat(price.value) || 0;
        totalEl.textContent = fmtMoney(q * p);
    }

    async function loadDashboard() {
        const res = await ManagerAPI.dashboard();
        const ui = res.ui || {};
        const set = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val;
        };
        set('mgr-kpi-sales-day', fmtMoney(ui.salesDay));
        set('mgr-kpi-products', String(ui.productsCount ?? 0));
        set('mgr-kpi-debts', fmtMoney(ui.pendingDebts));
        set('mgr-kpi-employees', String(ui.employeesCount ?? 0));
        set('mgr-last-sale', ui.lastSaleLabel || '—');
        set('mgr-last-sale-date', fmtDate(ui.lastSaleDate));
        set('mgr-stock-alert', ui.stockAlert || '—');
        set('mgr-recovery-month', fmtMoney(ui.recoveryMonth));
    }

    async function loadProductsForSelects() {
        const res = await ManagerAPI.products.list();
        fillProductSelects(res.data || res || []);
    }

    async function renderStockState() {
        const tbody = document.getElementById('stock-state-body');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="6" class="mgr-empty-hint">Chargement…</td></tr>';
        const res = await ManagerAPI.products.list();
        const products = res.data || [];
        fillProductSelects(products);
        if (!products.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="mgr-empty-hint">Aucun produit</td></tr>';
            return;
        }
        tbody.innerHTML = products
            .map((p) => {
                const st = p.stock_status || 'ok';
                const rowClass = st !== 'ok' ? 'mgr-stock-low' : '';
                return `<tr class="tft-b-bottom-gris ${rowClass}">
                    <td class="tft-title4 tft-clr-white3">${escapeHtml(p.name)}</td>
                    <td class="tft-title4 tft-clr-white3">${escapeHtml(p.description || '')}</td>
                    <td class="tft-title4 tft-clr-white3">${fmtMoney(p.unit_price)}</td>
                    <td class="tft-title4 tft-clr-white3">${p.current_stock ?? 0}</td>
                    <td><span class="${stockBadgeClass(st)}"><span class="tft-stock-dot"></span><span class="tft-stock-info"><p>${stockBadgeText(st)}</p></span></span></td>
                    <td class="tft-bdr-l-gris-1">—</td>
                </tr>`;
            })
            .join('');
    }

    // ================== AJOUTER CETTE FONCTION ==================
    async function loadSalesList() {
        const container = document.getElementById('sales-list');
        if (!container) return;
        container.innerHTML = '<div class="mgr-empty-hint">Chargement des ventes...</div>';
        try {
            const response = await ManagerAPI.sales.list();
            if (response && response.success) {
                const sales = response.data || [];
                if (sales.length === 0) {
                    container.innerHTML = '<div class="mgr-empty-hint">Aucune vente trouvée</div>';
                    return;
                }
                // Mêmes helpers que ceux déjà présents dans le fichier
                const fmtMoney = (n) => (Number(n) || 0).toLocaleString('fr-FR') + ' FBU';
                const fmtDate = (d) => {
                    if (!d) return '—';
                    const dt = new Date(d);
                    return isNaN(dt.getTime()) ? d : dt.toLocaleDateString('fr-FR');
                };
                container.innerHTML = sales.map(sale => `
                    <div class="mgr-card mgr-sale-item" style="margin-bottom: 12px;">
                        <div class="mgr-card-title">${escapeHtml(sale.product_name)}</div>
                        <div class="mgr-sale-details" style="display: flex; gap: 16px; flex-wrap: wrap; margin-top: 8px;">
                            <div><span class="mgr-kpi-label">Quantité :</span> ${sale.quantity}</div>
                            <div><span class="mgr-kpi-label">Prix unitaire :</span> ${fmtMoney(sale.unit_price)}</div>
                            <div><span class="mgr-kpi-label">Total :</span> <strong>${fmtMoney(sale.line_total)}</strong></div>
                            <div><span class="mgr-kpi-label">Date :</span> ${fmtDate(sale.sold_at)}</div>
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<div class="mgr-empty-hint tft-clr-red">Erreur de chargement des ventes</div>';
            }
        } catch (error) {
            console.error(error);
            container.innerHTML = '<div class="mgr-empty-hint tft-clr-red">Impossible de charger les ventes</div>';
        }
    }
    async function loadSalesHistory() {
        const tbody = document.getElementById('sales-history-body');
        if (!tbody) return;
        const start = document.getElementById('sales-filter-start')?.value;
        const end = document.getElementById('sales-filter-end')?.value;
        tbody.innerHTML = '<tr><td colspan="5" class="mgr-empty-hint">Chargement…</td></tr>';
        try {
            const res = await ManagerAPI.sales.list({ startDate: start, endDate: end });
            const lines = res.data || [];
            if (!lines.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="mgr-empty-hint">Aucune vente</td></tr>';
                return;
            }
            tbody.innerHTML = lines
                .map(
                    (l) => `<tr class="tft-b-bottom-gris">
                    <td class="tft-title4 tft-clr-white3">${fmtDate(l.sold_at)}</td>
                    <td class="tft-title4 tft-clr-white3">${escapeHtml(l.product_name)}</td>
                    <td class="tft-title4 tft-clr-white3">${l.quantity}</td>
                    <td class="tft-title4 tft-clr-white3">${Number(l.unit_price).toLocaleString('fr-FR')}</td>
                    <td class="tft-title4 tft-clr-orangesav">${fmtMoney(l.line_total)}</td>
                </tr>`
                )
                .join('');
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="5" class="mgr-empty-hint tft-clr-red">${escapeHtml(e.message)}</td></tr>`;
        }
    }

    async function loadStockMovements() {
        const tbody = document.querySelector('[data-mgr-panel="movements"] tbody');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="5" class="mgr-empty-hint">Chargement…</td></tr>';
        try {
            const res = await ManagerAPI.stock.movements();
            const rows = res.data || [];
            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="mgr-empty-hint">Aucun mouvement</td></tr>';
                return;
            }
            tbody.innerHTML = rows
                .map((m) => {
                    const isIn = (m.type || '').toUpperCase() === 'IN';
                    const cls = isIn ? 'mgr-movement-in' : 'mgr-movement-out';
                    const sign = isIn ? '+' : '-';
                    return `<tr class="tft-b-bottom-gris">
                        <td class="tft-title4 tft-clr-white3">${fmtDate(m.created_at || m.createdAt)}</td>
                        <td class="tft-title4 tft-clr-white3">${escapeHtml(m.product_name || ('#' + m.product_id))}</td>
                        <td class="tft-title4 ${cls}">${isIn ? 'Entrée' : 'Sortie'}</td>
                        <td class="tft-title4 tft-clr-white3">${sign}${m.quantity}</td>
                        <td class="tft-title4 tft-clr-white3">${escapeHtml(m.reason || '')}</td>
                    </tr>`;
                })
                .join('');
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="5" class="mgr-empty-hint">${escapeHtml(e.message)}</td></tr>`;
        }
    }

    async function loadDebts() {
        const tbody = document.getElementById('debts-table-body');
        if (!tbody) return;
        const status = document.getElementById('debt-status-filter')?.value || '';
        tbody.innerHTML = '<tr><td colspan="7" class="mgr-empty-hint">Chargement…</td></tr>';
        const res = await ManagerAPI.debts.list(status || undefined);
        const debts = res.data || [];
        const summary = res.summary || {};
        const set = (id, v) => {
            const el = document.getElementById(id);
            if (el) el.textContent = v;
        };
        set('mgr-total-recovery', fmtMoney(summary.recoveryThisMonth));
        set('mgr-total-pending', fmtMoney(summary.totalOutstanding));
        set('mgr-debtors-count', String(debts.filter((d) => (d.amount - (d.paid_amount || 0)) > 0).length));

        if (!debts.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="mgr-empty-hint">Aucune dette</td></tr>';
            return;
        }
        tbody.innerHTML = debts
            .map((d) => {
                const paid = Number(d.paid_amount || 0);
                const total = Number(d.amount || 0);
                const remaining = Math.max(0, total - paid);
                const canPay = remaining > 0;
                const debtor = d.debtor_name || '—';
                const product = d.product_name || '—';
                return `<tr class="tft-b-bottom-gris">
                    <td class="tft-title4 tft-clr-white3">${escapeHtml(debtor)}</td>
                    <td class="tft-title4 tft-clr-white3">${escapeHtml(product)}</td>
                    <td class="tft-title4 tft-clr-white3">${fmtMoney(total)}</td>
                    <td class="tft-title4 tft-clr-white3">${fmtMoney(paid)}</td>
                    <td class="tft-title4 tft-clr-orangesav">${fmtMoney(remaining)}</td>
                    <td>${debtBadge(d.status, remaining)}</td>
                    <td class="tft-bdr-l-gris-1">${
                        canPay
                            ? `<button type="button" class="tft-btn tft-bdr-greensav-1 tft-clr-greensav tft-hover-greensav" data-pay-debt="${d.id}" data-debtor="${escapeHtml(debtor)}" data-remaining="${remaining}">
                            <i class="fas fa-money-bill-wave"></i> Payer</button>`
                            : '—'
                    }</td>
                </tr>`;
            })
            .join('');

        tbody.querySelectorAll('[data-pay-debt]').forEach((btn) => {
            btn.addEventListener('click', () => {
                mgrOpenPayment(btn.getAttribute('data-debtor'), Number(btn.getAttribute('data-remaining')), Number(btn.getAttribute('data-pay-debt')));
            });
        });
    }

    async function loadEmployees(position) {
        const tbody = document.getElementById('employees-table-body');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="6" class="mgr-empty-hint">Chargement…</td></tr>';
        const res = await ManagerAPI.employees.list(position);
        const list = res.data || [];
        if (!list.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="mgr-empty-hint">Aucun employé</td></tr>';
            return;
        }
        tbody.innerHTML = list
            .map((e) => {
                const poste = e.position || '—';
                return `<tr class="tft-b-bottom-gris" data-poste="${escapeHtml(poste.toLowerCase())}">
                    <td class="tft-title4 tft-clr-white3">${escapeHtml(e.last_name || '')}</td>
                    <td class="tft-title4 tft-clr-white3">${escapeHtml(e.first_name || '')}</td>
                    <td class="tft-title4 tft-clr-white3">${escapeHtml(e.email || '')}</td>
                    <td class="tft-title4 tft-clr-white3">${escapeHtml(e.phone || e.telephone || '—')}</td>
                    <td class="tft-title4 tft-clr-white3">${escapeHtml(poste)}</td>
                    <td class="tft-bdr-l-gris-1">
                        <div class="actions">
                            <div class="tft-icon-round-petit tft-bg-black2 tft-bdr-red-txt-1" title="Supprimer" data-delete-employee="${e.id}">
                                <i class="fe fe-trash delete-icon tft-clr-red"></i>
                            </div>
                        </div>
                    </td>
                </tr>`;
            })
            .join('');

        tbody.querySelectorAll('[data-delete-employee]').forEach((el) => {
            el.addEventListener('click', async () => {
                if (!confirm('Supprimer cet employé ?')) return;
                try {
                    await ManagerAPI.employees.remove(el.getAttribute('data-delete-employee'));
                    showToast('Employé supprimé');
                    loadEmployees(position);
                } catch (err) {
                    showToast(err.message, true);
                }
            });
        });
    }

    async function loadSalary() {
        const tbody = document.getElementById('salary-table-body');
        const periodInput = document.querySelector('input[type="month"]');
        const period = periodInput?.value || '';
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="4" class="mgr-empty-hint">Chargement…</td></tr>';
        const res = await ManagerAPI.salary.list(period);
        const rows = res.data || [];
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="4" class="mgr-empty-hint">Aucune donnée</td></tr>';
            return;
        }
        tbody.innerHTML = rows
            .map((r) => {
                const name = [r.first_name, r.last_name].filter(Boolean).join(' ') || r.employee_name || '—';
                return `<tr class="tft-b-bottom-gris">
                    <td class="tft-title4 tft-clr-white3">${escapeHtml(name)}</td>
                    <td class="tft-title4 tft-clr-white3">${escapeHtml(r.position || '—')}</td>
                    <td class="tft-title4 tft-clr-white3">${fmtMoney(r.salary || r.total_salary)}</td>
                    <td><span class="mgr-badge mgr-badge-paid">${escapeHtml(r.status || '—')}</span></td>
                </tr>`;
            })
            .join('');
    }

    function escapeHtml(s) {
        return String(s ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function bindSaleForm() {
        const select = document.getElementById('sale-product');
        if (select) {
            select.addEventListener('change', () => onProductChange(select));
        }
        ['sale-quantity', 'sale-unit-price'].forEach((id) => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('input', updateSaleTotal);
        });
        const form = document.getElementById('sale-form');
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                try {
                    await ManagerAPI.sales.create({
                        productId: document.getElementById('sale-product').value,
                        quantity: document.getElementById('sale-quantity').value,
                        unitPrice: document.getElementById('sale-unit-price').value,
                        date: document.getElementById('sale-date').value,
                    });
                    showToast('Vente enregistrée');
                    form.reset();
                    setDefaultDates();
                    updateSaleTotal();
                    loadSalesHistory();
                    loadProductsForSelects();
                    renderStockState();
                } catch (err) {
                    showToast(err.message, true);
                }
            });
        }
        document.getElementById('sales-filter-btn')?.addEventListener('click', loadSalesHistory);
    }

    function bindStockForms() {
        const productForm = document.getElementById('mgr-product-form');
        if (productForm) {
            productForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                try {
                    await ManagerAPI.products.create({
                        name: document.getElementById('product-name').value,
                        description: document.getElementById('product-description').value,
                        unitPrice: document.getElementById('product-unit-price').value,
                        currentStock: document.getElementById('product-initial-qty').value,
                    });
                    showToast('Produit ajouté');
                    productForm.reset();
                    renderStockState();
                } catch (err) {
                    showToast(err.message, true);
                }
            });
        }
        const adjustForm = document.getElementById('mgr-adjust-form');
        if (adjustForm) {
            adjustForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                try {
                    await ManagerAPI.stock.adjust({
                        productId: document.getElementById('adjust-product').value,
                        type: document.getElementById('adjust-type').value,
                        quantity: document.getElementById('adjust-quantity').value,
                        reason: document.getElementById('adjust-reason').value,
                    });
                    showToast('Mouvement enregistré');
                    adjustForm.reset();
                    renderStockState();
                    loadStockMovements();
                } catch (err) {
                    showToast(err.message, true);
                }
            });
        }
    }

    function bindDebtPayment() {
        const form = document.getElementById('mgr-payment-form');
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (!currentPaymentDebtId) return;
                try {
                    await ManagerAPI.debts.pay(currentPaymentDebtId, document.getElementById('payment-amount').value);
                    showToast('Paiement enregistré');
                    closeManagerModal('mgr-modal-payment');
                    form.reset();
                    loadDebts();
                } catch (err) {
                    showToast(err.message, true);
                }
            });
        }
        document.getElementById('debt-status-filter')?.addEventListener('change', loadDebts);
    }

    function bindEmployeeForm() {
        const form = document.getElementById('mgr-employee-form');
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                try {
                    await ManagerAPI.employees.create({
                        nom: document.getElementById('emp-nom').value,
                        prenom: document.getElementById('emp-prenom').value,
                        email: document.getElementById('emp-email').value,
                        phone: document.getElementById('emp-phone').value,
                        poste: document.getElementById('emp-poste').value,
                    });
                    showToast('Employé enregistré');
                    closeManagerModal('mgr-modal-employee');
                    form.reset();
                    loadEmployees();
                } catch (err) {
                    showToast(err.message, true);
                }
            });
        }
    }

    function bindEmployeePosteFilter() {
        document.querySelectorAll('[data-poste-filter]').forEach((item) => {
            item.addEventListener('click', () => {
                const poste = item.getAttribute('data-poste-filter');
                loadEmployees(poste || undefined);
                if (typeof hideFilterOptions === 'function') hideFilterOptions();
            });
        });
    }

    function setDefaultDates() {
        const today = new Date().toISOString().slice(0, 10);
        ['sale-date', 'adjust-date', 'sales-filter-end'].forEach((id) => {
            const el = document.getElementById(id);
            if (el && !el.value) el.value = today;
        });
    }

    window.mgrOpenPayment = function (debtorName, remaining, debtId) {
        currentPaymentDebtId = debtId;
        const nameEl = document.getElementById('payment-debtor-name');
        const remainEl = document.getElementById('payment-remaining');
        const amountInput = document.getElementById('payment-amount');
        if (nameEl) nameEl.textContent = debtorName;
        if (remainEl) remainEl.textContent = fmtMoney(remaining);
        if (amountInput) {
            amountInput.max = remaining;
            amountInput.value = '';
        }
        openManagerModal('mgr-modal-payment');
    };

    window.mgrPayFull = function () {
        const remainEl = document.getElementById('payment-remaining');
        const amountInput = document.getElementById('payment-amount');
        if (!remainEl || !amountInput) return;
        const num = parseInt(remainEl.textContent.replace(/\D/g, ''), 10) || 0;
        amountInput.value = num;
    };

    window.openManagerModal = function (id) {
        const el = document.getElementById(id);
        if (el) el.style.display = 'flex';
    };
    window.closeManagerModal = function (id) {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    };

    async function loadManagerPage() {
        const root = document.getElementById('manager-page-root');
        if (!root) return;
        const page = root.getAttribute('data-manager-page');
        if (!page) return;

        try {
            const [contentRes, modalsRes] = await Promise.all([
                fetch(`../assets/html/manager/${page}-content.html`),
                fetch('../assets/html/manager/modals.html'),
            ]);
            if (!contentRes.ok) throw new Error('Contenu introuvable: ' + page);
            root.innerHTML = await contentRes.text();
            if (modalsRes.ok) {
                const holder = document.getElementById('manager-modals-root');
                if (holder) holder.innerHTML = await modalsRes.text();
            }

            setDefaultDates();
            initTabs(root);
            bindSaleForm();
            bindStockForms();
            bindDebtPayment();
            bindEmployeeForm();
            bindEmployeePosteFilter();

            await loadUserAndDept();

            switch (page) {
                case 'dashboard':
                    await loadDashboard();
                    break;
                case 'sales':
                    await loadProductsForSelects();
                    await loadSalesHistory();
                    await loadSalesList();
                    break;
                case 'stock':
                    await renderStockState();
                    break;
                case 'debts':
                    await loadDebts();
                    break;
                case 'employees':
                    await loadEmployees();
                    break;
                case 'salary':
                    document.getElementById('salary-filter-btn')?.addEventListener('click', loadSalary);
                    await loadSalary();
                    break;
            }
        } catch (err) {
            console.error(err);
            root.innerHTML = `<p class="mgr-empty-hint">${escapeHtml(err.message)}</p>`;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const logout = document.getElementById('logoutyes');
        if (logout) {
            logout.addEventListener('click', async (e) => {
                e.preventDefault();
                await ManagerAPI.auth.logout();
                window.location.href = '/1000saveursproject/public/login.html';
            });
        }
        loadManagerPage();
    });
})();
