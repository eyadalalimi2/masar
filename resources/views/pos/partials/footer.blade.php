<footer class="pos-footer small">
    <div class="pos-footer-line">
        <strong>نظام مسار</strong>
        <span class="pos-footer-separator">|</span>
        <span>لوحة المحلات التجارية</span>
        <span class="pos-footer-separator">|</span>
        <span>برمجة وتطوير</span>
        <a href="{{ route('pos.developer-profile.index') }}" class="pos-footer-chip">اياد جابر العليمي</a>
        <span>© جميع الحقوق محفوظة</span>
    </div>

    @if (Route::has('pos.platform-release.index'))
        <a href="{{ route('pos.platform-release.index') }}" class="pos-footer-chip">الإصدار 1.0.0</a>
    @else
        <span class="pos-footer-chip">الإصدار 1.0.0</span>
    @endif
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
