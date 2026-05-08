<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Supplier\Agent;
use App\Http\Requests\Supplier\SupplierApiProfileUpdateRequest;
use App\Http\Requests\Supplier\SupplierLoginRequest;
use App\Http\Requests\Supplier\SupplierRegisterRequest;
use App\Services\Supplier\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Throwable;

class SupplierAuthController extends Controller
{
    private const CHANGEABLE_FIELDS = [
        'owner_name',
        'branch_manager_name',
        'email',
        'phone',
        'whatsapp',
        'national_id_number',
        'business_name',
        'gps_location',
        'address',
        'commercial_reg_number',
        'license_number',
        'logo',
        'agent_image',
        'branch_manager_image',
        'national_id_image',
        'commercial_reg_image',
        'license_image',
    ];

    private const IMAGE_CHANGEABLE_FIELDS = [
        'logo',
        'agent_image',
        'branch_manager_image',
        'national_id_image',
        'commercial_reg_image',
        'license_image',
    ];

    private const FIELD_LABELS = [
        'owner_name' => 'اسم المالك',
        'branch_manager_name' => 'اسم مدير الفرع',
        'email' => 'البريد الإلكتروني',
        'phone' => 'رقم الهاتف',
        'whatsapp' => 'واتساب',
        'national_id_number' => 'رقم الهوية الوطنية',
        'business_name' => 'الاسم التجاري',
        'gps_location' => 'الموقع الجغرافي',
        'address' => 'العنوان',
        'commercial_reg_number' => 'رقم السجل التجاري',
        'license_number' => 'رقم الرخصة',
        'logo' => 'شعار النشاط',
        'agent_image' => 'صورة الوكيل',
        'branch_manager_image' => 'صورة مدير الفرع',
        'national_id_image' => 'صورة الهوية الوطنية',
        'commercial_reg_image' => 'صورة السجل التجاري',
        'license_image' => 'صورة الرخصة',
    ];

    private const SUPPLIER_PROFILE_APPENDS = [
        'logo_url',
        'agent_image_url',
        'branch_manager_image_url',
        'national_id_image_url',
        'commercial_reg_image_url',
        'license_image_url',
        'working_hours_schedule',
        'has_verification_request',
    ];

    public function login(SupplierLoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = Agent::query()
            ->with('supplier')
            ->where('phone', $data['phone'])
            ->where('status', 'active')
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة.'], 422);
        }

        if (! $user->supplier || $user->supplier->status !== 'active') {
            return response()->json(['message' => 'حساب الوكيل غير نشط.'], 403);
        }

        try {
            $token = $user->createToken('supplier-agent-token')->plainTextToken;
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'تعذر إنشاء جلسة تسجيل الدخول. تأكد من تهيئة قاعدة البيانات وتشغيل migrations الخاصة بالتوكنات.'
            ], 500);
        }

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح.',
            'token' => $token,
        ] + $this->buildProfilePayload($user));
    }

    public function register(SupplierRegisterRequest $request, SupplierService $supplierService): JsonResponse
    {
        $supplier = $supplierService->createSupplier($request->validated() + [
            'commercial_reg_number' => '',
            'license_number' => '',
            'national_id_number' => '',
            'address' => '',
            'status' => 'active',
        ]);

        $supplier->load('agentAccount');
        $user = $supplier->agentAccount;

        if (! $user) {
            return response()->json(['message' => 'تعذر إنشاء حساب الوكيل.'], 500);
        }

        try {
            $token = $user->createToken('supplier-agent-token')->plainTextToken;
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'تم إنشاء الحساب لكن تعذر إنشاء جلسة الدخول. حاول تسجيل الدخول يدويًا.'
            ], 500);
        }

        return response()->json([
            'message' => 'تم إنشاء حساب الوكيل بنجاح.',
            'token' => $token,
        ] + $this->buildProfilePayload($user), 201);
    }

    public function logout(): JsonResponse
    {
        $user = request()->user();

        if ($user && method_exists($user, 'currentAccessToken')) {
            $accessToken = $user->currentAccessToken();

            if ($accessToken && method_exists($user, 'tokens')) {
                $user->tokens()->whereKey($accessToken->id)->delete();
            }
        }

        return response()->json(['message' => 'تم تسجيل الخروج بنجاح.']);
    }

    public function me(): JsonResponse
    {
        $user = request()->user();

        if (! $user instanceof Agent) {
            return response()->json(['message' => 'غير مصرح.'], 403);
        }

        $user->load('supplier');

        if (! $user->supplier) {
            return response()->json(['message' => 'بيانات الوكيل غير موجودة.'], 404);
        }

        return response()->json($this->buildProfilePayload($user));
    }

    public function updateProfile(SupplierApiProfileUpdateRequest $request, SupplierService $supplierService): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof Agent) {
            return response()->json(['message' => 'غير مصرح.'], 403);
        }

        $user->load('supplier');

        if (! $user->supplier) {
            return response()->json(['message' => 'بيانات الوكيل غير موجودة.'], 404);
        }

        if ($user->supplier->is_verified) {
            return response()->json(['message' => 'تم توثيق بياناتك من الإدارة، ولا يمكن تعديلها بعد التوثيق.'], 403);
        }

        $payload = $request->validated();

        $payload['supplier_id'] = $user->supplier->id;
        $payload['status'] = $user->supplier->status;

        foreach (['logo', 'agent_image', 'branch_manager_image', 'commercial_reg_image', 'license_image', 'national_id_image'] as $fileKey) {
            if ($request->hasFile($fileKey)) {
                $payload[$fileKey] = $request->file($fileKey);
            }
        }

        if ($request->hasFile('id_card_image') && ! isset($payload['national_id_image'])) {
            $payload['national_id_image'] = $request->file('id_card_image');
        }

        $supplierService->updateSupplier($payload);

        $user->refresh()->load('supplier');

        return response()->json([
            'message' => 'تم تحديث الملف الشخصي بنجاح.',
        ] + $this->buildProfilePayload($user));
    }

    public function requestVerification(SupplierService $supplierService): JsonResponse
    {
        $user = request()->user();

        if (! $user instanceof Agent) {
            return response()->json(['message' => 'غير مصرح.'], 403);
        }

        $user->load('supplier');

        if (! $user->supplier) {
            return response()->json(['message' => 'بيانات الوكيل غير موجودة.'], 404);
        }

        if ($user->supplier->is_verified) {
            return response()->json(['message' => 'حسابك موثّق بالفعل.']);
        }

        if ($user->supplier->has_verification_request || $user->supplier->verification_requested_at) {
            return response()->json(['message' => 'طلب التوثيق قيد المراجعة بالفعل.']);
        }

        $supplierService->requestVerification($user->supplier->id, $user->id);

        return response()->json(['message' => 'تم إرسال طلب التوثيق بنجاح.']);
    }

    public function updateWorkingHours(Request $request, SupplierService $supplierService): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof Agent) {
            return response()->json(['message' => 'غير مصرح.'], 403);
        }

        $user->load('supplier');
        if (! $user->supplier) {
            return response()->json(['message' => 'بيانات الوكيل غير موجودة.'], 404);
        }

        $validated = $request->validate([
            'working_hours' => ['required', 'string'],
        ], [
            'working_hours.required' => 'بيانات أوقات الدوام مطلوبة.',
            'working_hours.string' => 'صيغة أوقات الدوام غير صحيحة.',
        ]);

        $decoded = json_decode((string) $validated['working_hours'], true);
        if (! is_array($decoded)) {
            return response()->json(['message' => 'صيغة أوقات الدوام غير صحيحة.'], 422);
        }

        $supplierService->updateSupplierWorkingHours($user->supplier->id, (string) $validated['working_hours']);

        $user->refresh()->load('supplier');

        return response()->json([
            'message' => 'تم تحديث أوقات الدوام بنجاح.',
        ] + $this->buildProfilePayload($user));
    }

    public function fieldChangeOptions(): JsonResponse
    {
        $user = request()->user();

        if (! $user instanceof Agent) {
            return response()->json(['message' => 'غير مصرح.'], 403);
        }

        $options = [];
        foreach (self::CHANGEABLE_FIELDS as $fieldKey) {
            $options[] = [
                'field_key' => $fieldKey,
                'label' => self::FIELD_LABELS[$fieldKey] ?? $fieldKey,
                'is_image' => in_array($fieldKey, self::IMAGE_CHANGEABLE_FIELDS, true),
            ];
        }

        return response()->json([
            'message' => 'تم تحميل الحقول المتاحة بنجاح.',
            'options' => $options,
        ]);
    }

    public function requestFieldChange(Request $request, SupplierService $supplierService): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof Agent) {
            return response()->json(['message' => 'غير مصرح.'], 403);
        }

        $user->load('supplier');
        $supplier = $user->supplier;

        if (! $supplier) {
            return response()->json(['message' => 'بيانات الوكيل غير موجودة.'], 404);
        }

        if (! $supplier->is_verified) {
            return response()->json([
                'message' => 'يمكنك تعديل الحقول مباشرة قبل التوثيق، ولا حاجة لطلب تعديل.'
            ], 422);
        }

        $fieldKey = (string) $request->input('field_key');
        $isImageField = in_array($fieldKey, self::IMAGE_CHANGEABLE_FIELDS, true);

        $validated = $request->validate([
            'field_key' => ['required', Rule::in(self::CHANGEABLE_FIELDS)],
            'requested_value' => [Rule::requiredIf(! $isImageField), 'nullable', 'string', 'max:1000'],
            'requested_image' => [Rule::requiredIf($isImageField), 'nullable', 'image', 'max:4096'],
            'note' => ['nullable', 'string', 'max:1000'],
            'document' => ['nullable', 'file', 'max:4096', 'mimes:pdf,jpg,jpeg,png,webp'],
        ], [
            'field_key.required' => 'يرجى اختيار الحقل المطلوب تعديله.',
            'field_key.in' => 'الحقل المحدد غير متاح لطلب التعديل.',
            'requested_value.required' => 'يرجى إدخال القيمة الجديدة المطلوبة.',
            'requested_image.required' => 'يرجى رفع الصورة المطلوبة لهذا الحقل.',
            'requested_image.image' => 'الملف المرفوع يجب أن يكون صورة.',
            'requested_image.max' => 'حجم الصورة المطلوبة يجب ألا يتجاوز 4MB.',
            'document.mimes' => 'نوع الملف غير مدعوم. الأنواع المسموحة: PDF أو صورة.',
            'document.max' => 'حجم المستند يجب ألا يتجاوز 4MB.',
        ]);

        try {
            $changeRequest = $supplierService->createFieldChangeRequest(
                $supplier->id,
                (int) $user->id,
                $validated['field_key'],
                $validated['requested_value'] ?? null,
                $validated['note'] ?? null,
                $request->file('document'),
                $request->file('requested_image'),
            );
        } catch (\DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'تم إرسال طلب تعديل الحقل إلى الإدارة بنجاح.',
            'request' => [
                'id' => $changeRequest->id,
                'field_key' => $changeRequest->field_key,
                'field_label' => self::FIELD_LABELS[$changeRequest->field_key] ?? $changeRequest->field_key,
                'status' => $changeRequest->status,
                'status_label' => $this->statusLabel($changeRequest->status),
                'created_at' => $changeRequest->created_at,
            ],
        ], 201);
    }

    public function fieldChangeRequests(): JsonResponse
    {
        $user = request()->user();

        if (! $user instanceof Agent) {
            return response()->json(['message' => 'غير مصرح.'], 403);
        }

        $user->load([
            'supplier.fieldChangeRequests' => function ($query) {
                $query->latest();
            },
        ]);

        $supplier = $user->supplier;
        if (! $supplier) {
            return response()->json(['message' => 'بيانات الوكيل غير موجودة.'], 404);
        }

        $requests = $supplier->fieldChangeRequests->map(function ($requestItem) {
            $isImageField = in_array($requestItem->field_key, self::IMAGE_CHANGEABLE_FIELDS, true);

            return [
                'id' => $requestItem->id,
                'field_key' => $requestItem->field_key,
                'field_label' => self::FIELD_LABELS[$requestItem->field_key] ?? $requestItem->field_key,
                'requested_value' => $requestItem->requested_value,
                'requested_value_url' => $isImageField
                    ? asset('storage/' . ltrim((string) $requestItem->requested_value, '/'))
                    : null,
                'note' => $requestItem->note,
                'document_path' => $requestItem->document_path,
                'document_url' => $requestItem->document_path
                    ? asset('storage/' . ltrim((string) $requestItem->document_path, '/'))
                    : null,
                'status' => $requestItem->status,
                'status_label' => $this->statusLabel((string) $requestItem->status),
                'created_at' => $requestItem->created_at,
                'reviewed_at' => $requestItem->reviewed_at,
            ];
        })->values();

        return response()->json([
            'message' => 'تم تحميل طلبات التعديل بنجاح.',
            'requests' => $requests,
        ]);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'approved' => 'مقبول',
            'rejected' => 'مرفوض',
            default => 'قيد المراجعة',
        };
    }

    private function buildProfilePayload(Agent $user): array
    {
        $supplier = $user->supplier->append(self::SUPPLIER_PROFILE_APPENDS);

        return [
            'user' => $user->toArray(),
            'supplier' => $supplier->toArray(),
        ];
    }
}
