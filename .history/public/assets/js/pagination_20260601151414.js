// pagination.js

class PaginationManager {
    constructor(options) {
        this.containerId = options.containerId;     // où afficher les boutons (ex: 'employees-pagination')
        this.loadCallback = options.loadCallback;   // fonction à appeler quand on change de page
        this.totalPages = options.totalPages || 1;
        this.currentPage = options.currentPage || 1;
    }

    // Affiche les boutons "Précédent" / "Suivant" et les infos de page
    render() {
        const container = document.getElementById(this.containerId);
        if (!container) return;

        if (this.totalPages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '<div class="pagination-buttons">';
        if (this.currentPage > 1) {
            html += `<button class="tft-btn-sm tft-bg-gris" data-page="${this.currentPage - 1}">« Précédent</button>`;
        }
        html += `<span class="page-info"> Page ${this.currentPage} / ${this.totalPages} </span>`;
        if (this.currentPage < this.totalPages) {
            html += `<button class="tft-btn-sm tft-bg-gris" data-page="${this.currentPage + 1}">Suivant »</button>`;
        }
        html += '</div>';

        container.innerHTML = html;

        // Attacher les événements
        container.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const newPage = parseInt(btn.dataset.page);
                if (!isNaN(newPage) && this.loadCallback) {
                    this.loadCallback(newPage);
                }
            });
        });
    }

    // Met à jour l'état interne et rafraîchit l'affichage
    update(currentPage, totalPages) {
        this.currentPage = currentPage;
        this.totalPages = totalPages;
        this.render();
    }
}