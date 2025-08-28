/**
 * admin-users.js - JavaScript para panel de administración
 * 
 * PROPÓSITO:
 * - Verificar permisos de administrador
 * - Manejar filtros de usuarios en tiempo real
 * - Gestión de tabs (Usuarios/OAuth)
 * - Funcionalidades administrativas avanzadas
 */

document.addEventListener('DOMContentLoaded', function() {
    // Verificar permisos de administrador al cargar
    verificarPermisosAdmin();
    
    // Configurar filtros de usuarios
    configurarFiltrosUsuarios();
    
    // Configurar tabs
    configurarTabs();
    
    // Configurar funcionalidades OAuth
    configurarOAuthPanel();
});

async function verificarPermisosAdmin() {
    try {
        const response = await GrammerUtils.fetch('/api/admin/verify');
        
        if (!response || !response.ok) {
            GrammerUtils.showMessage('Acceso denegado. Se requieren permisos de administrador.', 'error');
            setTimeout(() => {
                window.location.href = GrammerUtils.buildUrl('/dashboard');
            }, 3000);
            return;
        }
        
        const result = await response.json();
        if (!result.isAdmin) {
            throw new Error('Sin permisos de administrador');
        }
        
    } catch (error) {
        console.error('Error verificando permisos:', error);
        GrammerUtils.showMessage('Error verificando permisos de administrador', 'error');
        setTimeout(() => {
            window.location.href = GrammerUtils.buildUrl('/dashboard');
        }, 3000);
    }
}

function configurarFiltrosUsuarios() {
    const searchInput = document.getElementById('searchInput');
    const plantFilter = document.getElementById('plantFilter');
    const statusFilter = document.getElementById('statusFilter');
    const tableBody = document.getElementById('usersTableBody');
    const noResultsMessage = document.getElementById('noResultsMessage');
    
    if (!tableBody) return;
    
    const rows = tableBody.getElementsByTagName('tr');
    
    function filterTable() {
        const searchText = searchInput?.value.toLowerCase() || '';
        const plantValue = plantFilter?.value || '';
        const statusValue = statusFilter?.value || '';
        let visibleRows = 0;
        
        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            
            // Skip si es fila de "no resultados"
            if (row.cells.length === 1) continue;
            
            const nameEmailCell = row.cells[0]?.textContent.toLowerCase() || '';
            const plantCell = row.cells[1]?.textContent || '';
            const statusCell = row.cells[2]?.textContent.trim() || '';
            
            const matchesSearch = nameEmailCell.includes(searchText);
            const matchesPlant = plantValue === "" || plantCell === plantValue;
            const matchesStatus = statusValue === "" || statusCell.includes(statusValue);
            
            if (matchesSearch && matchesPlant && matchesStatus) {
                row.style.display = "";
                visibleRows++;
            } else {
                row.style.display = "none";
            }
        }
        
        // Mostrar mensaje si no hay resultados
        if (noResultsMessage) {
            noResultsMessage.style.display = visibleRows === 0 ? "block" : "none";
        }
    }
    
    // Añadir listeners a los filtros
    searchInput?.addEventListener('keyup', filterTable);
    plantFilter?.addEventListener('change', filterTable);
    statusFilter?.addEventListener('change', filterTable);
}

function configurarTabs() {
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            showTab(tabName, this);
        });
    });
}

function showTab(tabName, clickedButton) {
    // Ocultar todas las tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Mostrar tab seleccionada
    const targetTab = document.getElementById(tabName + '-tab');
    if (targetTab) {
        targetTab.classList.add('active');
        clickedButton.classList.add('active');
        
        // Cargar datos específicos de la tab si es necesario
        if (tabName === 'oauth') {
            cargarDatosOAuth();
        }
    }
}

function configurarOAuthPanel() {
    // Event listener para nuevo cliente
    const newClientForm = document.getElementById('newClientForm');
    if (newClientForm) {
        newClientForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const clientData = {
                client_id: formData.get('client_id'),
                name: formData.get('name'),
                redirect_uri: formData.get('redirect_uri')
            };
            
            try {
                const response = await GrammerUtils.fetch('/api/oauth/clients', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(clientData)
                });
                
                if (response && response.ok) {
                    const result = await response.json();
                    GrammerUtils.showMessage('Cliente OAuth creado exitosamente', 'success');
                    this.reset();
                    cargarDatosOAuth(); // Recargar tabla
                } else {
                    GrammerUtils.showMessage('Error creando cliente OAuth', 'error');
                }
                
            } catch (error) {
                console.error('Error:', error);
                GrammerUtils.showMessage('Error de conexión', 'error');
            }
        });
    }
}

async function cargarDatosOAuth() {
    try {
        const response = await GrammerUtils.fetch('/api/oauth/stats');
        
        if (response && response.ok) {
            const stats = await response.json();
            
            // Actualizar estadísticas OAuth
            document.getElementById('totalClients').textContent = stats.total_clients || 0;
            document.getElementById('activeTokens').textContent = stats.active_tokens || 0;
            document.getElementById('todayLogins').textContent = stats.today_logins || 0;
        }
    } catch (error) {
        console.warn('No se pudieron cargar estadísticas OAuth:', error);
    }
}

// Funciones para gestión de clientes OAuth
function toggleSecret(element, secret) {
    if (element.textContent === '••••••••••••••••') {
        element.textContent = secret;
        element.style.color = 'var(--grammer-blue)';
        element.style.fontFamily = 'monospace';
    } else {
        element.textContent = '••••••••••••••••';
        element.style.color = 'var(--gray-500)';
    }
}

async function regenerateSecret(clientId) {
    if (!confirm('¿Estás seguro de regenerar el secret? Esto invalidará el secret actual.')) {
        return;
    }
    
    try {
        const response = await GrammerUtils.fetch(`/api/oauth/clients/${clientId}/regenerate-secret`, {
            method: 'POST'
        });
        
        if (response && response.ok) {
            const result = await response.json();
            GrammerUtils.showMessage(`Secret regenerado para ${clientId}`, 'success');
            
            // Actualizar el secret en la tabla
            const secretElement = document.querySelector(`[data-client="${clientId}"] .secret-hidden`);
            if (secretElement) {
                secretElement.setAttribute('onclick', `toggleSecret(this, '${result.new_secret}')`);
            }
        } else {
            GrammerUtils.showMessage('Error regenerando secret', 'error');
        }
        
    } catch (error) {
        console.error('Error:', error);
        GrammerUtils.showMessage('Error de conexión', 'error');
    }
}

function viewClientDetails(clientId) {
    // Por ahora mostrar modal simple - en futuro implementar modal completo
    GrammerUtils.showMessage(`Mostrando detalles de ${clientId} (funcionalidad en desarrollo)`, 'info');
}

// Hacer funciones globalmente disponibles
window.toggleSecret = toggleSecret;
window.regenerateSecret = regenerateSecret;
window.viewClientDetails = viewClientDetails;