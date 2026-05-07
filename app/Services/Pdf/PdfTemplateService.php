<?php

namespace App\Services\Pdf;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class PdfTemplateService
{
    private const STORAGE_PATH = 'settings/pdf_templates.json';
    public const TYPE_DOCUMENTS = 'documents';
    public const TYPE_REPORTS = 'reports';
    public const TYPE_INVOICES = 'invoices';
    public const SCOPE_ADMIN = 'admin';
    public const SCOPE_AGENT = 'agent';
    public const SCOPE_BRANCH = 'branch';
    public const SCOPE_DISTRIBUTOR = 'distributor';
    public const SCOPE_CONSUMER = 'consumer';
    public const SCOPE_POS = 'pos';
    public const SCOPE_WORKSHOP = 'workshop';
    public const DEFAULT_TEMPLATE_KEY = 'default';

    public function types(): array
    {
        return [
            self::TYPE_DOCUMENTS,
            self::TYPE_REPORTS,
            self::TYPE_INVOICES,
        ];
    }

    public function scopes(): array
    {
        return [
            self::SCOPE_ADMIN,
            self::SCOPE_AGENT,
            self::SCOPE_BRANCH,
            self::SCOPE_DISTRIBUTOR,
            self::SCOPE_CONSUMER,
            self::SCOPE_POS,
            self::SCOPE_WORKSHOP,
        ];
    }

    public function resolveCurrentScope(): string
    {
        foreach ($this->scopes() as $scope) {
            if (auth($scope)->check()) {
                return $scope;
            }
        }

        return self::SCOPE_ADMIN;
    }

    public function getTemplate(string $type = self::TYPE_DOCUMENTS, ?string $scope = null, string $templateKey = self::DEFAULT_TEMPLATE_KEY): array
    {
        $type = $this->normalizeType($type);
        $scope = $this->normalizeScope($scope);
        $templateKey = $this->normalizeTemplateKey($templateKey);
        $state = $this->readState();
        $templates = (array) ($state['templates'] ?? []);
        $scoped = (array) ($templates[$scope] ?? []);
        $typed = (array) ($scoped[$type] ?? []);

        return array_merge($this->defaultTemplate($type), (array) ($typed[$templateKey] ?? []));
    }

    public function getTemplates(?string $scope = null): array
    {
        $scope = $this->normalizeScope($scope);
        $all = [];
        foreach ($this->types() as $type) {
            $all[$type] = $this->getTemplate($type, $scope, self::DEFAULT_TEMPLATE_KEY);
        }

        return $all;
    }

    public function updateTemplate(string $type, array $data, ?UploadedFile $logoFile = null, ?string $scope = null, string $templateKey = self::DEFAULT_TEMPLATE_KEY): array
    {
        $type = $this->normalizeType($type);
        $scope = $this->normalizeScope($scope);
        $templateKey = $this->normalizeTemplateKey($templateKey);
        $state = $this->readState();

        $template = array_merge($this->getTemplate($type, $scope, $templateKey), [
            'platform_name' => trim((string) ($data['platform_name'] ?? '')),
            'platform_address' => trim((string) ($data['platform_address'] ?? '')),
            'platform_phone' => trim((string) ($data['platform_phone'] ?? '')),
            'document_title' => trim((string) ($data['document_title'] ?? '')),
            'business_name_ar' => trim((string) ($data['business_name_ar'] ?? ($data['business_name'] ?? ''))),
            'business_name_en' => trim((string) ($data['business_name_en'] ?? '')),
            'business_address_ar' => trim((string) ($data['business_address_ar'] ?? ($data['business_address'] ?? ''))),
            'business_address_en' => trim((string) ($data['business_address_en'] ?? '')),
            'business_phone' => trim((string) ($data['business_phone'] ?? '')),
            'header_subtitle' => trim((string) ($data['header_subtitle'] ?? '')),
            'footer_note' => trim((string) ($data['footer_note'] ?? '')),
        ]);

        if ($template['platform_name'] === '') {
            $template['platform_name'] = $this->defaultTemplate($type)['platform_name'];
        }

        if ($template['document_title'] === '') {
            $template['document_title'] = $this->defaultTemplate($type)['document_title'];
        }

        if ($logoFile instanceof UploadedFile) {
            $stored = $logoFile->store('pdf-templates/logos', 'public');
            $template['logo_public_path'] = 'storage/' . ltrim($stored, '/');
        } else {
            $providedPath = ltrim(trim((string) ($data['logo_public_path'] ?? '')), '/');
            if ($providedPath !== '') {
                $template['logo_public_path'] = $providedPath;
            }
        }

        if (! isset($template['logo_public_path']) || trim((string) $template['logo_public_path']) === '') {
            $template['logo_public_path'] = $this->defaultTemplate($type)['logo_public_path'];
        }

        $templates = (array) ($state['templates'] ?? []);
        $scoped = (array) ($templates[$scope] ?? []);
        $typed = (array) ($scoped[$type] ?? []);
        $typed[$templateKey] = $template;
        $scoped[$type] = $typed;
        $templates[$scope] = $scoped;

        $state['templates'] = $templates;

        Storage::disk('local')->put(
            self::STORAGE_PATH,
            json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );

        return $template;
    }

    private function readState(): array
    {
        $defaults = [
            'templates' => $this->defaultTemplates(),
        ];

        if (! Storage::disk('local')->exists(self::STORAGE_PATH)) {
            return $defaults;
        }

        $raw = Storage::disk('local')->get(self::STORAGE_PATH);
        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return $defaults;
        }

        // Backward compatibility for legacy single-template payload.
        if (isset($decoded['platform_name']) || isset($decoded['logo_public_path'])) {
            $legacy = array_merge($this->defaultTemplate(self::TYPE_DOCUMENTS), [
                'platform_name' => trim((string) ($decoded['platform_name'] ?? '')),
                'platform_address' => trim((string) ($decoded['platform_address'] ?? '')),
                'platform_phone' => trim((string) ($decoded['platform_phone'] ?? '')),
                'document_title' => trim((string) ($decoded['document_title'] ?? '')),
                'business_name_ar' => trim((string) ($decoded['business_name_ar'] ?? ($decoded['business_name'] ?? ''))),
                'business_name_en' => trim((string) ($decoded['business_name_en'] ?? '')),
                'business_address_ar' => trim((string) ($decoded['business_address_ar'] ?? ($decoded['business_address'] ?? ''))),
                'business_address_en' => trim((string) ($decoded['business_address_en'] ?? '')),
                'business_phone' => trim((string) ($decoded['business_phone'] ?? '')),
                'header_subtitle' => trim((string) ($decoded['header_subtitle'] ?? '')),
                'footer_note' => trim((string) ($decoded['footer_note'] ?? '')),
                'logo_public_path' => ltrim(trim((string) ($decoded['logo_public_path'] ?? '')), '/'),
            ]);

            foreach ($this->scopes() as $scope) {
                foreach ($this->types() as $type) {
                    $defaults['templates'][$scope][$type][self::DEFAULT_TEMPLATE_KEY] = array_merge(
                        $defaults['templates'][$scope][$type][self::DEFAULT_TEMPLATE_KEY],
                        $legacy
                    );
                }
            }

            return $defaults;
        }

        $templates = (array) ($decoded['templates'] ?? []);
        $state = $defaults;

        // Backward compatibility for previous schema: templates[type] = settings.
        $isFlatTypeMap = false;
        foreach ($this->types() as $type) {
            if (isset($templates[$type])) {
                $isFlatTypeMap = true;
                break;
            }
        }
        if ($isFlatTypeMap) {
            foreach ($this->scopes() as $scope) {
                foreach ($this->types() as $type) {
                    $legacyTemplate = (array) ($templates[$type] ?? []);
                    if (! isset($legacyTemplate['business_name_ar']) && isset($legacyTemplate['business_name'])) {
                        $legacyTemplate['business_name_ar'] = $legacyTemplate['business_name'];
                    }
                    if (! isset($legacyTemplate['business_address_ar']) && isset($legacyTemplate['business_address'])) {
                        $legacyTemplate['business_address_ar'] = $legacyTemplate['business_address'];
                    }

                    $state['templates'][$scope][$type][self::DEFAULT_TEMPLATE_KEY] = array_merge(
                        $this->defaultTemplate($type),
                        $legacyTemplate
                    );
                }
            }

            return $state;
        }

        // Current schema: templates[scope][type][templateKey] = settings
        foreach ($this->scopes() as $scope) {
            $scoped = (array) ($templates[$scope] ?? []);
            foreach ($this->types() as $type) {
                $typed = (array) ($scoped[$type] ?? []);

                // Backward compatibility: templates[scope][type] = settings.
                $hasNamedKeys = false;
                foreach (array_keys($typed) as $key) {
                    if (is_array($typed[$key] ?? null)) {
                        $hasNamedKeys = true;
                        break;
                    }
                }

                if (! $hasNamedKeys && $typed !== []) {
                    $typed = [self::DEFAULT_TEMPLATE_KEY => $typed];
                }

                foreach ($typed as $key => $settings) {
                    if (! is_array($settings)) {
                        continue;
                    }

                    $templateKey = $this->normalizeTemplateKey((string) $key);
                    if (! isset($settings['business_name_ar']) && isset($settings['business_name'])) {
                        $settings['business_name_ar'] = $settings['business_name'];
                    }
                    if (! isset($settings['business_address_ar']) && isset($settings['business_address'])) {
                        $settings['business_address_ar'] = $settings['business_address'];
                    }

                    $state['templates'][$scope][$type][$templateKey] = array_merge(
                        $this->defaultTemplate($type),
                        $settings
                    );
                }
            }
        }

        return $state;
    }

    private function normalizeScope(?string $scope): string
    {
        $scope = trim((string) $scope);
        if ($scope === '') {
            return $this->resolveCurrentScope();
        }

        if (! in_array($scope, $this->scopes(), true)) {
            return self::SCOPE_ADMIN;
        }

        return $scope;
    }

    private function normalizeType(string $type): string
    {
        if (! in_array($type, $this->types(), true)) {
            return self::TYPE_DOCUMENTS;
        }

        return $type;
    }

    private function normalizeTemplateKey(string $templateKey): string
    {
        $templateKey = trim(strtolower($templateKey));
        if ($templateKey === '') {
            return self::DEFAULT_TEMPLATE_KEY;
        }

        $clean = preg_replace('/[^a-z0-9\-_]/', '-', $templateKey);
        $clean = trim((string) $clean, '-_');

        return $clean !== '' ? $clean : self::DEFAULT_TEMPLATE_KEY;
    }

    private function defaultTemplates(): array
    {
        $result = [];
        foreach ($this->scopes() as $scope) {
            $result[$scope] = [];
            foreach ($this->types() as $type) {
                $result[$scope][$type] = [
                    self::DEFAULT_TEMPLATE_KEY => $this->defaultTemplate($type),
                ];
            }
        }

        return $result;
    }

    private function defaultTemplate(string $type): array
    {
        $label = match ($type) {
            self::TYPE_REPORTS => 'التقارير',
            self::TYPE_INVOICES => 'الفواتير',
            default => 'المستندات',
        };

        return [
            'platform_name' => 'منصة مسار',
            'platform_address' => 'العنوان الرئيسي',
            'platform_phone' => '0000000000',
            'document_title' => 'مستند ' . $label,
            'business_name_ar' => '',
            'business_name_en' => '',
            'business_address_ar' => '',
            'business_address_en' => '',
            'business_phone' => '',
            'header_subtitle' => 'نظام ERP لإدارة العمليات - ' . $label,
            'footer_note' => 'مستند رسمي',
            'logo_public_path' => 'assets/images/logo.png',
        ];
    }
}
