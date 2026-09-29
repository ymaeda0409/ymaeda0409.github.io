<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ErrorCode;
use App\Enums\Permission;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Concerns\HasTranslationRules;
use App\Models\NotificationTemplate;
use App\Services\AuditLogger;
use App\Services\TranslationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Translatable push/SMS texts, editable by translation managers (SUPER_ADMIN).
 */
class NotificationTemplateController extends Controller
{
    use HasTranslationRules;

    public function index(Request $request): JsonResponse
    {
        $this->authorizeManage($request);

        $templates = NotificationTemplate::query()->with('translations')->orderBy('code')->orderBy('channel')->get();

        return ApiResponse::success($templates->map(fn (NotificationTemplate $t) => $this->present($t))->all());
    }

    public function update(Request $request, NotificationTemplate $notificationTemplate, TranslationService $translations, AuditLogger $audit): JsonResponse
    {
        $this->authorizeManage($request);
        $data = Validator::make($request->all(), [
            'is_active' => ['sometimes', 'boolean'],
            ...$this->translationRules('translations', ['title' => 150, 'body' => 1000], false, 'body'),
        ])->validate();

        DB::transaction(function () use ($notificationTemplate, $data, $translations, $audit) {
            $before = ['is_active' => $notificationTemplate->is_active, 'translations' => $notificationTemplate->translationsByLocale()];
            $notificationTemplate->fill(array_intersect_key($data, ['is_active' => true]))->save();
            if (isset($data['translations'])) {
                $translations->sync($notificationTemplate, $data['translations']);
            }
            $audit->log('notification_template.updated', $notificationTemplate, $before, $data);
        });

        return ApiResponse::success($this->present($notificationTemplate->load('translations')));
    }

    private function authorizeManage(Request $request): void
    {
        if (! $request->user()->hasPermission(Permission::TRANSLATIONS_MANAGE)) {
            throw ApiException::of(ErrorCode::FORBIDDEN);
        }
    }

    private function present(NotificationTemplate $t): array
    {
        return [
            'id' => $t->id,
            'code' => $t->code,
            'channel' => $t->channel->value,
            'is_active' => $t->is_active,
            'translations' => $t->translationsByLocale(),
        ];
    }
}
