/**
 * JAVASCRIPT PRINCIPAL - CONDOMINIO TERRAZAS
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // SIDEBAR TOGGLE (Mobile)
    // ============================================
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });
    }
    
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }
    
    // ============================================
    // Cerrar sidebar al hacer clic en un link (mobile)
    // ============================================
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }
        });
    });
    
    // ============================================
    // AUTO-CLOSE ALERTS
    // ============================================
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 300);
        }, 5000);
    });
    
    // ============================================
    // CONFIRM DELETE
    // ============================================
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            const message = btn.getAttribute('data-confirm-delete') || '¿Está seguro de eliminar este registro?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
    
    // ============================================
    // MODAL FUNCTIONALITY
    // ============================================
    const modalTriggers = document.querySelectorAll('[data-modal]');
    modalTriggers.forEach(function(trigger) {
        trigger.addEventListener('click', function() {
            const modalId = trigger.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('show');
            }
        });
    });
    
    const modalCloseButtons = document.querySelectorAll('.modal-close');
    modalCloseButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const modal = btn.closest('.modal');
            if (modal) {
                modal.classList.remove('show');
            }
        });
    });
    
    // Cerrar modal al hacer clic fuera
    const modals = document.querySelectorAll('.modal');
    modals.forEach(function(modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.remove('show');
            }
        });
    });
    
    // ============================================
    // FORM VALIDATION
    // ============================================
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Remover errores anteriores
            const errors = form.querySelectorAll('.is-invalid');
            errors.forEach(function(el) {
                el.classList.remove('is-invalid');
            });
            const feedbacks = form.querySelectorAll('.invalid-feedback');
            feedbacks.forEach(function(el) {
                el.remove();
            });
            
            // Validar campos requeridos
            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    isValid = false;
                    showFieldError(field, 'Este campo es requerido');
                }
            });
            
            // Validar emails
            const emailFields = form.querySelectorAll('[type="email"]');
            emailFields.forEach(function(field) {
                if (field.value && !isValidEmail(field.value)) {
                    isValid = false;
                    showFieldError(field, 'Ingrese un correo electrónico válido');
                }
            });
            
            // Validar DNI (8 dígitos)
            const dniFields = form.querySelectorAll('[data-type="dni"]');
            dniFields.forEach(function(field) {
                if (field.value && !/^\d{8}$/.test(field.value)) {
                    isValid = false;
                    showFieldError(field, 'El DNI debe tener 8 dígitos');
                }
            });
            
            // Validar RUC (11 dígitos)
            const rucFields = form.querySelectorAll('[data-type="ruc"]');
            rucFields.forEach(function(field) {
                if (field.value && !/^\d{11}$/.test(field.value)) {
                    isValid = false;
                    showFieldError(field, 'El RUC debe tener 11 dígitos');
                }
            });
            
            // Validar longitud mínima
            const minLengthFields = form.querySelectorAll('[minlength]');
            minLengthFields.forEach(function(field) {
                const minLength = parseInt(field.getAttribute('minlength'));
                if (field.value && field.value.length < minLength) {
                    isValid = false;
                    showFieldError(field, 'Mínimo ' + minLength + ' caracteres');
                }
            });
            
            // Validar confirmación de contraseña
            const password = form.querySelector('[name="password"], [name="password_nuevo"]');
            const passwordConfirm = form.querySelector('[name="password_confirm"]');
            if (password && passwordConfirm) {
                if (password.value !== passwordConfirm.value) {
                    isValid = false;
                    showFieldError(passwordConfirm, 'Las contraseñas no coinciden');
                }
            }
            
            if (!isValid) {
                e.preventDefault();
                // Scroll al primer error
                const firstError = form.querySelector('.is-invalid');
                if (firstError) {
                    firstError.focus();
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    });
    
    function showFieldError(field, message) {
        field.classList.add('is-invalid');
        
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = message;
        
        field.parentNode.insertBefore(feedback, field.nextSibling);
    }
    
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // ============================================
    // LIMPIAR ERROR AL ESCRIBIR
    // ============================================
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('is-invalid')) {
            e.target.classList.remove('is-invalid');
            const feedback = e.target.parentNode.querySelector('.invalid-feedback');
            if (feedback) {
                feedback.remove();
            }
        }
    });
    
    // ============================================
    // SEARCH AUTO-DEBOUNCE
    // ============================================
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(function(input) {
        let timeout;
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                const form = input.closest('form');
                if (form) {
                    form.submit();
                }
            }, 500);
        });
    });
    
    // ============================================
    // TOOLTIP (Simple)
    // ============================================
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(function(el) {
        el.addEventListener('mouseenter', function() {
            const text = el.getAttribute('data-tooltip');
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = text;
            tooltip.style.cssText = 'position:absolute;background:#1e293b;color:#fff;padding:0.5rem 0.75rem;border-radius:0.375rem;font-size:0.75rem;z-index:9999;white-space:nowrap;';
            document.body.appendChild(tooltip);
            
            const rect = el.getBoundingClientRect();
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 8) + 'px';
            tooltip.style.left = (rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2)) + 'px';
            
            el._tooltip = tooltip;
        });
        
        el.addEventListener('mouseleave', function() {
            if (el._tooltip) {
                el._tooltip.remove();
                el._tooltip = null;
            }
        });
    });
    
    // ============================================
    // ACUERDOS DINÁMICOS (Agregar/Quitar)
    // ============================================
    const addAcuerdoBtn = document.querySelector('[data-add-acuerdo]');
    if (addAcuerdoBtn) {
        addAcuerdoBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const acuerdosContainer = document.getElementById('acuerdos-container');
            if (!acuerdosContainer) return;
            
            let acuerdoIndex = acuerdosContainer.children.length;
            const template = document.getElementById('acuerdo-template');
            
            if (template) {
                const html = template.innerHTML.replace(/__INDEX__/g, acuerdoIndex);
                acuerdosContainer.insertAdjacentHTML('beforeend', html);
            }
        });
    }
    
    // Eliminar acuerdo
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-acuerdo')) {
            const acuerdoItem = e.target.closest('.acuerdo-item');
            if (acuerdoItem) {
                acuerdoItem.remove();
            }
        }
    });
    
    // ============================================
    // IMPRIMIR COMPROBANTE
    // ============================================
    const printBtn = document.querySelector('[data-print]');
    if (printBtn) {
        printBtn.addEventListener('click', function() {
            window.print();
        });
    }

    // ============================================
    // FILTROS AVANZADOS
    // ============================================
    const toggleFiltersBtn = document.querySelector('[data-toggle-filters]');
    if (toggleFiltersBtn) {
        const advancedFilters = document.getElementById('advanced-filters');
        if (advancedFilters) {
            toggleFiltersBtn.addEventListener('click', function() {
                advancedFilters.classList.toggle('show');
                toggleFiltersBtn.textContent = advancedFilters.classList.contains('show') ? 'Ocultar filtros' : 'Mostrar filtros';
            });
        }
    }
    
    // ============================================
    // CONFIRMACIÓN ANTES DE ENVIAR FORMULARIO
    // ============================================
    const confirmForms = document.querySelectorAll('form[data-confirm]');
    confirmForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const message = form.getAttribute('data-confirm') || '¿Está seguro de realizar esta acción?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
    
    // ============================================
    // CALCULAR MONTO AUTOMÁTICO
    // ============================================
    const montoCuota = document.querySelector('[data-monto-cuota]');
    if (montoCuota) {
        const montoDefault = parseFloat(montoCuota.getAttribute('data-monto-cuota'));
        const montoInput = document.querySelector('[name="monto"]');
        if (montoInput && !montoInput.value) {
            montoInput.value = montoDefault.toFixed(2);
        }
    }
    
    // ============================================
    // SELECT ALL CHECKBOX
    // ============================================
    const selectAllCheckbox = document.getElementById('select-all');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
        });
    }
    
    // ============================================
    // TOAST NOTIFICATIONS
    // ============================================
    window.showToast = function(message, type = 'info') {
        const container = document.querySelector('.toast-container') || createToastContainer();
        
        const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        };
        
        const colors = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#06b6d4'
        };
        
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.innerHTML = '<span style="color:' + colors[type] + ';font-weight:bold;font-size:1.25rem;">' + icons[type] + '</span><span>' + message + '</span>';
        
        container.appendChild(toast);
        
        setTimeout(function() {
            toast.classList.add('hide');
            setTimeout(function() {
                toast.remove();
            }, 300);
        }, 4000);
    };
    
    function createToastContainer() {
        const container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
        return container;
    }
    
    // ============================================
    // GRÁFICOS SIMPLES CON CSS (Fallback sin librerías)
    // ============================================
    const chartBars = document.querySelectorAll('.chart-bar');
    chartBars.forEach(function(bar) {
        const value = parseFloat(bar.getAttribute('data-value'));
        const max = parseFloat(bar.getAttribute('data-max'));
        if (max > 0) {
            const percentage = (value / max) * 100;
            bar.style.width = percentage + '%';
        }
    });
    
    // ============================================
    // DARK MODE TOGGLE
    // ============================================
    const themeToggleBtn = document.getElementById('theme-toggle');
    const prefersDarkScheme = window.matchMedia("(prefers-color-scheme: dark)");

    // Función para cambiar el ícono según el tema
    function updateThemeIcon(theme) {
        if (!themeToggleBtn) return;
        const icon = themeToggleBtn.querySelector('i');
        if (theme === 'dark') {
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
            themeToggleBtn.title = 'Tema Claro';
        } else {
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
            themeToggleBtn.title = 'Tema Oscuro';
        }
    }

    // Comprobar la preferencia guardada o asignarla
    const currentTheme = localStorage.getItem("theme");
    if (currentTheme == "dark") {
        document.documentElement.setAttribute("data-theme", "dark");
        updateThemeIcon('dark');
    } else if (currentTheme == "light") {
        document.documentElement.setAttribute("data-theme", "light");
        updateThemeIcon('light');
    } else if (prefersDarkScheme.matches) {
        // Fallback al tema del sistema
        document.documentElement.setAttribute("data-theme", "dark");
        updateThemeIcon('dark');
    }

    // Escuchar el clic del botón
    if (themeToggleBtn) {
        themeToggleBtn.addEventListener("click", function() {
            let theme = document.documentElement.getAttribute("data-theme");
            let targetTheme = "light";

            if (theme === "dark") {
                targetTheme = "light";
            } else {
                targetTheme = "dark";
            }

            document.documentElement.setAttribute("data-theme", targetTheme);
            localStorage.setItem("theme", targetTheme);
            updateThemeIcon(targetTheme);
        });
    }

});

// ============================================
// EXPORTAR A CSV
// ============================================
function exportarCSV(tablaId, nombreArchivo) {
    const tabla = document.getElementById(tablaId);
    if (!tabla) return;
    
    let csv = [];
    const rows = tabla.querySelectorAll('tr');
    
    rows.forEach(function(row) {
        const cols = row.querySelectorAll('td, th');
        let rowData = [];
        cols.forEach(function(col) {
            let text = col.textContent.trim();
            text = text.replace(/"/g, '""');
            rowData.push('"' + text + '"');
        });
        csv.push(rowData.join(','));
    });
    
    const csvContent = '\uFEFF' + csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = (nombreArchivo || 'reporte') + '.csv';
    link.click();
}

// ============================================
// CONFIRMACIÓN GENÉRICA
// ============================================
function confirmar(mensaje) {
    return confirm(mensaje || '¿Está seguro de realizar esta acción?');
}
