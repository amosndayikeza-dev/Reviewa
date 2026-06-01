// sales-page.js
(function() {
    function formatMoney(amount) {
        return (Number(amount) || 0).toLocaleString('fr-FR') + ' FBU';
    }

    function formatDate(dateStr) {
        if (!dateStr) return '—';
        const d = new Date(dateStr);
        return isNaN(d.getTime()) ? dateStr : d.toLocaleDateString('fr-FR');
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    async function loadSales() {
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
                container.innerHTML = sales.map(sale => `
                    <div class="mgr-card mgr-sale-item">
                        <div class="mgr-card-title">${escapeHtml(sale.product_name)}</div>
                        <div class="mgr-sale-details">
                            <div><span class="mgr-kpi-label">Quantité :</span> ${sale.quantity}</div>
                            <div><span class="mgr-kpi-label">Prix unitaire :</span> ${formatMoney(sale.unit_price)}</div>
                            <div><span class="mgr-kpi-label">Total :</span> <strong>${formatMoney(sale.line_total)}</strong></div>
                            <div><span class="mgr-kpi-label">Date :</span> ${formatDate(sale.sold_at)}</div>
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

    loadSales();
})();