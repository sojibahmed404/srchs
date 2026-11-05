/**
 * ============================================================
 * Sirajganj Road Chowrasta High School - Main JavaScript
 * ============================================================
 * Vanilla JavaScript — no jQuery dependency
 */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // ----------------------------------------------------------
    // 1. Navbar Scroll Effect
    // ----------------------------------------------------------
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        const handleNavbarScroll = function () {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        };
        window.addEventListener('scroll', handleNavbarScroll, { passive: true });
        handleNavbarScroll(); // run on load
    }

    // ----------------------------------------------------------
    // 2. Smooth Scroll for Anchor Links
    // ----------------------------------------------------------
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener('click', function (e) {
            var targetId = this.getAttribute('href');
            if (targetId === '#' || targetId === '') return;
            var target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                var offset = navbar ? navbar.offsetHeight + 10 : 80;
                var top = target.getBoundingClientRect().top + window.pageYOffset - offset;
                window.scrollTo({ top: top, behavior: 'smooth' });
            }
        });
    });

    // ----------------------------------------------------------
    // 3. Counter Animation (IntersectionObserver)
    // ----------------------------------------------------------
    var counters = document.querySelectorAll('[data-target]');
    if (counters.length > 0) {
        var counterAnimated = new WeakSet();

        var animateCounter = function (el) {
            if (counterAnimated.has(el)) return;
            counterAnimated.add(el);

            var target = parseInt(el.getAttribute('data-target'), 10);
            if (isNaN(target) || target <= 0) {
                el.textContent = '0';
                return;
            }

            var duration = 2000; // 2 seconds
            var startTime = null;

            var step = function (timestamp) {
                if (!startTime) startTime = timestamp;
                var progress = Math.min((timestamp - startTime) / duration, 1);
                // Ease-out quad
                var eased = 1 - Math.pow(1 - progress, 3);
                var current = Math.floor(eased * target);
                el.textContent = current.toLocaleString();
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                } else {
                    el.textContent = target.toLocaleString();
                }
            };

            window.requestAnimationFrame(step);
        };

        var counterObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                }
            });
        }, { threshold: 0.3 });

        counters.forEach(function (c) {
            counterObserver.observe(c);
        });
    }

    // ----------------------------------------------------------
    // 4. Back To Top Button
    // ----------------------------------------------------------
    var backToTopBtn = document.querySelector('.back-to-top');
    if (backToTopBtn) {
        var handleBackToTop = function () {
            if (window.scrollY > 300) {
                backToTopBtn.classList.add('visible');
            } else {
                backToTopBtn.classList.remove('visible');
            }
        };
        window.addEventListener('scroll', handleBackToTop, { passive: true });
        handleBackToTop();

        backToTopBtn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ----------------------------------------------------------
    // 5. Mobile Sidebar Toggle
    // ----------------------------------------------------------
    var sidebarToggle = document.querySelector('.sidebar-toggle');
    var sidebarOverlay = document.querySelector('.sidebar-overlay');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            document.body.classList.toggle('sidebar-open');
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function () {
            document.body.classList.remove('sidebar-open');
        });
    }

    // Sidebar submenu toggles
    document.querySelectorAll('.sidebar-nav .nav-link[data-bs-toggle="collapse"]').forEach(function (link) {
        link.addEventListener('click', function () {
            this.classList.toggle('collapsed');
        });
    });

    // Close sidebar on window resize to desktop
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992) {
            document.body.classList.remove('sidebar-open');
        }
    });

    // ----------------------------------------------------------
    // 6. Delete Confirmation
    // ----------------------------------------------------------
    document.querySelectorAll('.btn-delete').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var href = this.getAttribute('href') || this.dataset.href;
            var itemName = this.dataset.name || 'this item';
            var message = 'Are you sure you want to delete "' + itemName + '"?\nThis action cannot be undone.';

            // Try using a Bootstrap modal if #deleteModal exists
            var deleteModal = document.getElementById('deleteModal');
            if (deleteModal) {
                var confirmBtn = deleteModal.querySelector('.btn-confirm-delete');
                var nameEl = deleteModal.querySelector('.delete-item-name');
                if (nameEl) nameEl.textContent = itemName;
                if (confirmBtn && href) {
                    confirmBtn.setAttribute('href', href);
                    // For form-based deletes
                    var formAction = deleteModal.querySelector('form');
                    if (formAction) {
                        formAction.setAttribute('action', href);
                    }
                }
                var modal = new bootstrap.Modal(deleteModal);
                modal.show();
            } else {
                // Fallback: native confirm
                if (confirm(message)) {
                    if (href) {
                        window.location.href = href;
                    }
                }
            }
        });
    });

    // ----------------------------------------------------------
    // 7. Bootstrap 5 Form Validation
    // ----------------------------------------------------------
    document.querySelectorAll('.needs-validation').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // ----------------------------------------------------------
    // 8. Image Preview on File Input Change
    // ----------------------------------------------------------
    document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
        input.addEventListener('change', function () {
            var previewSelector = this.dataset.preview;
            var previewEl = document.querySelector(previewSelector);
            if (!previewEl) return;

            if (this.files && this.files[0]) {
                var file = this.files[0];
                // Validate it's an image
                if (!file.type.startsWith('image/')) {
                    previewEl.classList.remove('active');
                    previewEl.src = '';
                    return;
                }

                var reader = new FileReader();
                reader.onload = function (e) {
                    previewEl.src = e.target.result;
                    previewEl.classList.add('active');
                };
                reader.readAsDataURL(file);
            } else {
                previewEl.src = '';
                previewEl.classList.remove('active');
            }
        });
    });

    // ----------------------------------------------------------
    // 9. AJAX Helper Function
    // ----------------------------------------------------------
    window.sendAjax = function (url, data, method, callback) {
        method = (method || 'POST').toUpperCase();
        var options = {
            method: method,
            headers: {}
        };

        if (method === 'GET') {
            // Append data as query string
            if (data && typeof data === 'object') {
                var params = new URLSearchParams(data).toString();
                url += (url.indexOf('?') > -1 ? '&' : '?') + params;
            }
        } else {
            if (data instanceof FormData) {
                options.body = data;
                // Let browser set Content-Type with boundary
            } else {
                options.headers['Content-Type'] = 'application/x-www-form-urlencoded';
                if (typeof data === 'object') {
                    options.body = new URLSearchParams(data).toString();
                } else {
                    options.body = data;
                }
            }
        }

        fetch(url, options)
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP error: ' + response.status);
                }
                var contentType = response.headers.get('content-type');
                if (contentType && contentType.indexOf('application/json') !== -1) {
                    return response.json();
                }
                return response.text();
            })
            .then(function (result) {
                if (typeof callback === 'function') {
                    callback(null, result);
                }
            })
            .catch(function (error) {
                console.error('AJAX Error:', error);
                if (typeof callback === 'function') {
                    callback(error, null);
                }
            });
    };

    // ----------------------------------------------------------
    // 10. Toast / Notification Function
    // ----------------------------------------------------------
    window.showNotification = function (message, type) {
        type = type || 'info';

        // Ensure container exists
        var container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        // Icon mapping
        var icons = {
            success: 'fa-circle-check',
            error: 'fa-circle-xmark',
            warning: 'fa-triangle-exclamation',
            info: 'fa-circle-info'
        };
        var icon = icons[type] || icons.info;
        var toastClass = 'toast-' + (type === 'danger' ? 'error' : type);

        var toastEl = document.createElement('div');
        toastEl.className = 'toast ' + toastClass;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        toastEl.innerHTML =
            '<div class="toast-header">' +
                '<i class="fa-solid ' + icon + ' me-2"></i>' +
                '<strong class="me-auto">' + type.charAt(0).toUpperCase() + type.slice(1) + '</strong>' +
                '<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>' +
            '</div>' +
            '<div class="toast-body">' + message + '</div>';

        container.appendChild(toastEl);

        // Use Bootstrap Toast API
        if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
            var bsToast = new bootstrap.Toast(toastEl, { delay: 5000 });
            bsToast.show();
            toastEl.addEventListener('hidden.bs.toast', function () {
                toastEl.remove();
            });
        } else {
            // Fallback without Bootstrap JS
            toastEl.style.display = 'block';
            toastEl.style.opacity = '1';
            setTimeout(function () {
                toastEl.style.opacity = '0';
                toastEl.style.transition = 'opacity 0.4s ease';
                setTimeout(function () { toastEl.remove(); }, 500);
            }, 5000);
        }
    };

    // ----------------------------------------------------------
    // 11. DataTable Search Filter
    // ----------------------------------------------------------
    document.querySelectorAll('.table-search').forEach(function (input) {
        var tableSelector = input.dataset.table;
        var table = tableSelector ? document.querySelector(tableSelector) : input.closest('.card-dashboard, .card, .search-filter-bar')?.parentElement?.querySelector('table');
        if (!table) return;

        input.addEventListener('input', function () {
            var query = this.value.toLowerCase().trim();
            var rows = table.querySelectorAll('tbody tr');

            rows.forEach(function (row) {
                var text = row.textContent.toLowerCase();
                row.style.display = text.indexOf(query) > -1 ? '' : 'none';
            });

            // Update "no results" row if present
            var visibleRows = table.querySelectorAll('tbody tr:not([style*="display: none"])');
            var noResultRow = table.querySelector('.no-search-result');
            if (visibleRows.length === 0) {
                if (!noResultRow) {
                    var colCount = table.querySelectorAll('thead th').length || 1;
                    noResultRow = document.createElement('tr');
                    noResultRow.className = 'no-search-result';
                    noResultRow.innerHTML = '<td colspan="' + colCount + '" class="text-center text-muted py-4"><i class="fa-solid fa-search me-2"></i>No results found</td>';
                    table.querySelector('tbody').appendChild(noResultRow);
                }
                noResultRow.style.display = '';
            } else if (noResultRow) {
                noResultRow.style.display = 'none';
            }
        });
    });

    // ----------------------------------------------------------
    // 12. Select All Checkbox
    // ----------------------------------------------------------
    document.querySelectorAll('.select-all').forEach(function (selectAll) {
        selectAll.addEventListener('change', function () {
            var checked = this.checked;
            var container = this.closest('table') || this.closest('.card-body') || document;
            container.querySelectorAll('.select-item').forEach(function (cb) {
                cb.checked = checked;
            });
        });
    });

    // Also update "select all" when individual items change
    document.querySelectorAll('.select-item').forEach(function (item) {
        item.addEventListener('change', function () {
            var container = this.closest('table') || this.closest('.card-body') || document;
            var selectAll = container.querySelector('.select-all');
            if (!selectAll) return;

            var allItems = container.querySelectorAll('.select-item');
            var checkedItems = container.querySelectorAll('.select-item:checked');
            selectAll.checked = allItems.length === checkedItems.length;
            selectAll.indeterminate = checkedItems.length > 0 && checkedItems.length < allItems.length;
        });
    });

    // ----------------------------------------------------------
    // 13. Auto-dismiss Alerts after 5 seconds
    // ----------------------------------------------------------
    document.querySelectorAll('.alert-dismissible.auto-dismiss, .alert.auto-dismiss').forEach(function (alert) {
        setTimeout(function () {
            // Try Bootstrap dismiss
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            } else {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.4s ease';
                setTimeout(function () { alert.remove(); }, 500);
            }
        }, 5000);
    });

    // ----------------------------------------------------------
    // 14. Print Button Handler
    // ----------------------------------------------------------
    document.querySelectorAll('.btn-print').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            window.print();
        });
    });

    // ----------------------------------------------------------
    // 15. Loading Indicator Functions
    // ----------------------------------------------------------
    var loadingOverlay = null;

    window.showLoading = function () {
        if (!loadingOverlay) {
            loadingOverlay = document.createElement('div');
            loadingOverlay.className = 'loading-overlay';
            loadingOverlay.innerHTML = '<div class="spinner"></div>';
            document.body.appendChild(loadingOverlay);
        }
        // Force reflow then add class
        loadingOverlay.offsetHeight;
        loadingOverlay.classList.add('active');
    };

    window.hideLoading = function () {
        if (loadingOverlay) {
            loadingOverlay.classList.remove('active');
        }
    };

    // ----------------------------------------------------------
    // 16. Gallery Lightbox
    // ----------------------------------------------------------
    var lightboxOverlay = document.querySelector('.lightbox-overlay');
    var galleryItems = document.querySelectorAll('.gallery-item[data-lightbox]');

    if (galleryItems.length > 0) {
        // Create lightbox overlay if it doesn't exist
        if (!lightboxOverlay) {
            lightboxOverlay = document.createElement('div');
            lightboxOverlay.className = 'lightbox-overlay';
            lightboxOverlay.innerHTML =
                '<button class="lightbox-close" aria-label="Close">&times;</button>' +
                '<img src="" alt="Lightbox image">' +
                '<div class="lightbox-caption"></div>';
            document.body.appendChild(lightboxOverlay);
        }

        var lightboxImg = lightboxOverlay.querySelector('img');
        var lightboxCaption = lightboxOverlay.querySelector('.lightbox-caption');
        var lightboxClose = lightboxOverlay.querySelector('.lightbox-close');

        galleryItems.forEach(function (item) {
            item.addEventListener('click', function () {
                var fullSrc = this.dataset.lightbox || this.querySelector('img')?.src;
                var caption = this.dataset.caption || this.querySelector('.caption')?.textContent || '';

                if (fullSrc) {
                    lightboxImg.src = fullSrc;
                    lightboxCaption.textContent = caption;
                    lightboxOverlay.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }
            });
        });

        // Close lightbox
        if (lightboxClose) {
            lightboxClose.addEventListener('click', function () {
                lightboxOverlay.classList.remove('active');
                document.body.style.overflow = '';
                lightboxImg.src = '';
            });
        }

        lightboxOverlay.addEventListener('click', function (e) {
            if (e.target === lightboxOverlay) {
                lightboxOverlay.classList.remove('active');
                document.body.style.overflow = '';
                lightboxImg.src = '';
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && lightboxOverlay.classList.contains('active')) {
                lightboxOverlay.classList.remove('active');
                document.body.style.overflow = '';
                lightboxImg.src = '';
            }
        });
    }

    // ----------------------------------------------------------
    // 17. Active Nav Link Highlight
    // ----------------------------------------------------------
    var currentPath = window.location.pathname;
    document.querySelectorAll('.sidebar-nav .nav-link, .navbar-nav .nav-link').forEach(function (link) {
        var href = link.getAttribute('href');
        if (href && currentPath.indexOf(href) > -1 && href !== '#' && href !== '/') {
            link.classList.add('active');
            // Expand parent submenu if inside one
            var parentSubmenu = link.closest('.nav-submenu');
            if (parentSubmenu) {
                parentSubmenu.classList.add('show');
                var parentToggle = parentSubmenu.previousElementSibling;
                if (parentToggle) {
                    parentToggle.classList.remove('collapsed');
                }
            }
        }
    });

    // ----------------------------------------------------------
    // 18. Tooltip Initialization (Bootstrap 5)
    // ----------------------------------------------------------
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            new bootstrap.Tooltip(el);
        });
    }

    // ----------------------------------------------------------
    // 19. Popover Initialization (Bootstrap 5)
    // ----------------------------------------------------------
    if (typeof bootstrap !== 'undefined' && bootstrap.Popover) {
        document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (el) {
            new bootstrap.Popover(el);
        });
    }

    // ----------------------------------------------------------
    // 20. Scroll Reveal Animations
    // ----------------------------------------------------------
    var revealElements = document.querySelectorAll('.reveal-on-scroll');
    if (revealElements.length > 0) {
        var revealObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });

        revealElements.forEach(function (el) {
            revealObserver.observe(el);
        });
    }

    // ----------------------------------------------------------
    // Console branding
    // ----------------------------------------------------------
    console.log(
        '%c SRCHS %c Sirajganj Road Chowrasta High School Management Portal ',
        'background: #123B2A; color: #D9AD55; padding: 6px 10px; font-weight: bold; border-radius: 4px 0 0 4px;',
        'background: #198754; color: #fff; padding: 6px 10px; border-radius: 0 4px 4px 0;'
    );

}); // end DOMContentLoaded
