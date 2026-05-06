<footer class="admin-footer small">
    <div class="admin-footer-line">
        <strong>نظام مسار</strong>
        <span class="admin-footer-separator">|</span>
        <span>لوحة الإدارة المركزية</span>
        <span class="admin-footer-separator">|</span>
        <span>برمجة وتطوير</span>
        <a href="{{ route('admin.developer-profile.index') }}" class="admin-footer-dev-link">اياد جابر العليمي</a>
        <span>© جميع الحقوق محفوظة</span>
    </div>

    <div class="admin-footer-meta">
        <a href="{{ route('admin.platform-release.index') }}" class="admin-footer-chip">الإصدار 1.0.0</a>
        <span class="admin-footer-chip">{{ now()->format('Y') }}</span>
    </div>
</footer>

{{-- Bootstrap JS --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const shell = document.getElementById('adminShell');
        const toggle = document.getElementById('sidebarToggle');
        const body = document.body;

        if (!shell || !toggle) {
            return;
        }

        const syncMobileLock = () => {
            if (window.innerWidth > 991.98) {
                body.classList.remove('admin-lock');
                return;
            }

            body.classList.toggle('admin-lock', shell.classList.contains('sidebar-open'));
        };

        toggle.addEventListener('click', function() {
            shell.classList.toggle('sidebar-open');
            syncMobileLock();
        });

        document.addEventListener('click', function(event) {
            if (window.innerWidth > 991.98) {
                return;
            }

            const sidebar = document.getElementById('adminSidebar');
            const clickedInsideSidebar = sidebar && sidebar.contains(event.target);
            const clickedToggle = toggle.contains(event.target);

            if (!clickedInsideSidebar && !clickedToggle) {
                shell.classList.remove('sidebar-open');
                syncMobileLock();
            }
        });

        window.addEventListener('resize', syncMobileLock);
    });
</script>
