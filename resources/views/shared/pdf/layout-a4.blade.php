<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin-top: 52mm;
            margin-bottom: 22mm;
            margin-left: 12mm;
            margin-right: 12mm;
            header: doc-header;
            footer: doc-footer;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
            direction: rtl;
        }

        h1,
        h2,
        h3 {
            margin: 0 0 8px 0;
            color: #0f172a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 6px;
            text-align: right;
            vertical-align: middle;
        }

        th {
            background: #f1f5f9;
            color: #0f172a;
            font-weight: 700;
        }

        .meta-box {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 8px;
            padding: 8px 10px;
            margin-bottom: 12px;
        }

        .small {
            font-size: 10px;
            color: #64748b;
        }
    </style>
</head>

<body>
    @php
    $templateScope = trim((string) $__env->yieldContent('pdf-template-scope'));
    if ($templateScope === '') {
    $templateScope = app(\App\Services\Pdf\PdfTemplateService::class)->resolveCurrentScope();
    }

    $templateType = trim((string) $__env->yieldContent('pdf-template-type'));
    $templateType = $templateType !== '' ? $templateType : \App\Services\Pdf\PdfTemplateService::TYPE_DOCUMENTS;
    $templateKey = trim((string) $__env->yieldContent('pdf-template-key'));
    $templateKey = $templateKey !== '' ? $templateKey : \App\Services\Pdf\PdfTemplateService::DEFAULT_TEMPLATE_KEY;
    $templateSettings = app(\App\Services\Pdf\PdfTemplateService::class)->getTemplate($templateType, $templateScope, $templateKey);

    $platformName = trim((string) $__env->yieldContent('pdf-platform-name'));
    $platformName = $platformName !== '' ? $platformName : ($templateSettings['platform_name'] ?? 'منصة مسار');
    $platformAddress = trim((string) $__env->yieldContent('pdf-platform-address'));
    $platformAddress = $platformAddress !== '' ? $platformAddress : (string) ($templateSettings['platform_address'] ?? '');
    $platformPhone = trim((string) $__env->yieldContent('pdf-platform-phone'));
    $platformPhone = $platformPhone !== '' ? $platformPhone : (string) ($templateSettings['platform_phone'] ?? '');

    $headerSubtitle = trim((string) $__env->yieldContent('pdf-header-subtitle'));
    $headerSubtitle = $headerSubtitle !== '' ? $headerSubtitle : ($templateSettings['header_subtitle'] ?? '');
    $footerNote = trim((string) $__env->yieldContent('pdf-footer-note'));
    $footerNote = $footerNote !== '' ? $footerNote : ($templateSettings['footer_note'] ?? '');

    $businessNameAr = trim((string) $__env->yieldContent('pdf-business-name-ar'));
    if ($businessNameAr === '') {
    $businessNameAr = trim((string) $__env->yieldContent('pdf-business-name'));
    }
    if ($businessNameAr === '') {
    $businessNameAr = (string) ($templateSettings['business_name_ar'] ?? ($templateSettings['business_name'] ?? ($templateSettings['platform_name'] ?? '')));
    }

    $businessNameEn = trim((string) $__env->yieldContent('pdf-business-name-en'));
    if ($businessNameEn === '') {
    $businessNameEn = (string) ($templateSettings['business_name_en'] ?? '');
    }
    if ($businessNameEn === '') {
    $businessNameEn = $businessNameAr;
    }

    $businessAddressAr = trim((string) $__env->yieldContent('pdf-business-address-ar'));
    if ($businessAddressAr === '') {
    $businessAddressAr = trim((string) $__env->yieldContent('pdf-business-address'));
    }
    if ($businessAddressAr === '') {
    $businessAddressAr = (string) ($templateSettings['business_address_ar'] ?? ($templateSettings['business_address'] ?? ($templateSettings['platform_address'] ?? '')));
    }

    $businessAddressEn = trim((string) $__env->yieldContent('pdf-business-address-en'));
    if ($businessAddressEn === '') {
    $businessAddressEn = (string) ($templateSettings['business_address_en'] ?? '');
    }
    if ($businessAddressEn === '') {
    $businessAddressEn = $businessAddressAr;
    }

    $businessPhone = trim((string) $__env->yieldContent('pdf-business-phone'));
    if ($businessPhone === '') {
    $businessPhone = (string) ($templateSettings['business_phone'] ?? ($templateSettings['platform_phone'] ?? ''));
    }

    $logoPublicPath = trim((string) ($templateSettings['logo_public_path'] ?? 'assets/images/logo.png'));
    $logoFilePath = str_replace('\\', '/', public_path($logoPublicPath));
    if (! file_exists($logoFilePath) && str_starts_with($logoPublicPath, 'storage/')) {
    $fallback = storage_path('app/public/' . ltrim(substr($logoPublicPath, strlen('storage/')), '/'));
    $fallback = str_replace('\\', '/', $fallback);
    if (file_exists($fallback)) {
    $logoFilePath = $fallback;
    }
    }

    $hasLogo = file_exists($logoFilePath);
    $logoRenderPath = $logoFilePath;
    if ($hasLogo) {
    $logoFileSize = @filesize($logoFilePath);
    $logoSize = @getimagesize($logoFilePath);
    $logoWidth = is_array($logoSize) ? (int) ($logoSize[0] ?? 0) : 0;
    $logoHeight = is_array($logoSize) ? (int) ($logoSize[1] ?? 0) : 0;
    $logoPixels = $logoWidth * $logoHeight;

    // Guard against OOM in mPDF when processing extremely large images.
    $isOversized = ($logoFileSize !== false && (int) $logoFileSize > 3 * 1024 * 1024)
    || $logoWidth > 2400
    || $logoHeight > 2400
    || $logoPixels > 4_000_000;

    if ($isOversized) {
    $hasLogo = false;

    if (function_exists('imagecreatefromstring')) {
    $content = @file_get_contents($logoFilePath);
    $source = $content !== false ? @imagecreatefromstring($content) : false;

    if ($source !== false) {
    $sourceWidth = imagesx($source);
    $sourceHeight = imagesy($source);
    $maxDimension = 1400;
    $ratio = min($maxDimension / max($sourceWidth, 1), $maxDimension / max($sourceHeight, 1), 1);
    $targetWidth = max(1, (int) floor($sourceWidth * $ratio));
    $targetHeight = max(1, (int) floor($sourceHeight * $ratio));

    $optimizedDir = storage_path('app/public/pdf-templates/logos/optimized');
    if (! is_dir($optimizedDir)) {
    @mkdir($optimizedDir, 0775, true);
    }

    $optimizedFile = $optimizedDir . DIRECTORY_SEPARATOR . md5($logoFilePath . '|' . (string) @filemtime($logoFilePath)) . '.png';

    if (! file_exists($optimizedFile)) {
    $target = imagecreatetruecolor($targetWidth, $targetHeight);
    imagealphablending($target, false);
    imagesavealpha($target, true);
    imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
    @imagepng($target, $optimizedFile, 8);
    imagedestroy($target);
    }

    imagedestroy($source);

    if (file_exists($optimizedFile)) {
    $logoRenderPath = str_replace('\\', '/', $optimizedFile);
    $hasLogo = true;
    }
    }
    }
    }
    }

    $title = trim((string) $__env->yieldContent('pdf-title'));
    $title = $title !== '' ? $title : (string) ($templateSettings['document_title'] ?? 'مستند');
    $subtitle = trim((string) $__env->yieldContent('pdf-subtitle'));

    $printedAt = trim((string) $__env->yieldContent('pdf-printed-at'));
    $printedAt = $printedAt !== '' ? $printedAt : now()->format('Y-m-d H:i');

    $printedBy = trim((string) $__env->yieldContent('pdf-printed-by'));
    if ($printedBy === '') {
    $printedBy = (string) (auth()->user()->name ?? '');
    }
    if ($printedBy === '') {
    foreach (['admin', 'agent', 'branch', 'consumer', 'distributor', 'pos'] as $guard) {
    $guardUser = auth($guard)->user();
    if ($guardUser && isset($guardUser->name) && trim((string) $guardUser->name) !== '') {
    $printedBy = trim((string) $guardUser->name);
    break;
    }
    }
    }
    if ($printedBy === '') {
    $printedBy = 'مستخدم النظام';
    }
    @endphp

    <htmlpageheader name="doc-header">
        <table style="border:0; border-collapse:collapse; width:100%; margin:0; border-bottom:1px solid #cbd5e1; padding-bottom:10px; direction:ltr;">
            <tr>
                <td style="border:0; width:36%; text-align:left; vertical-align:top; line-height:1.7; direction:ltr; padding-left:0; padding-right:0;">
                    @if ($businessNameEn !== '')
                    <div style="font-size:14px; font-weight:700; color:#0f172a;">{{ $businessNameEn }}</div>
                    @endif
                    @if ($businessAddressEn !== '')
                    <div style="font-size:12px; color:#475569;">{{ $businessAddressEn }}</div>
                    @endif
                    @if ($businessPhone !== '')
                    <div style="font-size:12px; color:#475569;">Phone: {{ $businessPhone }}</div>
                    @endif
                </td>
                <td style="border:0; width:28%; text-align:center; vertical-align:top; line-height:1.5; padding-left:0; padding-right:0;">
                    @if ($hasLogo)
                    <img src="{{ $logoRenderPath }}" alt="logo" style="height:58px; margin-bottom:6px;">
                    @endif
                    <div style="font-size:18px; font-weight:700; color:#0f172a;">{{ $title }}</div>
                </td>
                <td style="border:0; width:36%; text-align:right; vertical-align:top; line-height:1.7; direction:rtl; padding-left:0; padding-right:0;">
                    @if ($businessNameAr !== '')
                    <div style="font-size:14px; font-weight:700; color:#0f172a;">{{ $businessNameAr }}</div>
                    @endif
                    @if ($businessAddressAr !== '')
                    <div style="font-size:12px; color:#475569;">{{ $businessAddressAr }}</div>
                    @endif
                    @if ($businessPhone !== '')
                    <div style="font-size:12px; color:#475569;">الهاتف: {{ $businessPhone }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </htmlpageheader>

    <htmlpagefooter name="doc-footer">
        <table style="border:0; border-collapse:collapse; width:100%; margin:0; border-top:1px solid #e2e8f0;">
            <tr>
                <td style="border:0; width:40%; padding-top:6px; font-size:10px; color:#64748b; text-align:right;">
                    المستخدم: {{ $printedBy }}
                </td>
                <td style="border:0; width:20%; padding-top:6px; font-size:10px; color:#64748b; text-align:center;">{PAGENO} / {nbpg}</td>
                <td style="border:0; width:40%; padding-top:6px; font-size:10px; color:#64748b; text-align:left;">
                    تاريخ ووقت الطباعة: {{ $printedAt }}
                </td>
            </tr>
        </table>
    </htmlpagefooter>

    @yield('pdf-body')
</body>

</html>