<?php

namespace App\Services\Admin;

use App\Models\NotificationTemplate;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductOption;
use App\Models\ProductOptionGroup;
use App\Models\Store;
use App\Models\User;
use App\Services\LocaleService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One place to review and fill translations of every translatable table.
 *
 * A record "needs" a translation when its default-locale text exists; it is "translated"
 * for a locale when that locale has the primary attribute filled.
 */
class TranslationManagementService
{
    /**
     * primary: the attribute that decides whether a translation exists.
     * required: may not be emptied for the default locale.
     * max: max length per attribute.
     */
    public const TYPES = [
        'categories' => ['model' => ProductCategory::class, 'primary' => 'name', 'required' => true, 'max' => ['name' => 150]],
        'products' => ['model' => Product::class, 'primary' => 'name', 'required' => true, 'max' => ['name' => 150, 'description' => 2000]],
        'option_groups' => ['model' => ProductOptionGroup::class, 'primary' => 'name', 'required' => true, 'max' => ['name' => 150]],
        'options' => ['model' => ProductOption::class, 'primary' => 'name', 'required' => true, 'max' => ['name' => 150]],
        'stores' => ['model' => Store::class, 'primary' => 'description', 'required' => false, 'max' => ['description' => 2000, 'announcement' => 1000]],
        'notification_templates' => ['model' => NotificationTemplate::class, 'primary' => 'body', 'required' => true, 'max' => ['title' => 150, 'body' => 1000]],
    ];

    public function __construct(private readonly LocaleService $locales) {}

    /**
     * @return array<string, array{total: int, translated: array<string, int>}>
     */
    public function summary(User $user): array
    {
        $codes = $this->activeCodes();
        $default = $this->locales->defaultLocale();

        return collect(self::TYPES)->map(function (array $type, string $name) use ($user, $codes, $default) {
            $base = $this->withSource($this->query($name, $user), $type['primary'], $default);

            return [
                'total' => (clone $base)->count(),
                'translated' => collect($codes)->mapWithKeys(fn ($code) => [
                    $code => (clone $base)->whereHas('translations', fn ($q) => $this->filled($q, $type['primary'], $code))->count(),
                ])->all(),
            ];
        })->all();
    }

    /**
     * Records of a type with default-locale source text and the target locale's text.
     */
    public function items(string $type, User $user, string $locale, bool $missingOnly, int $perPage)
    {
        $definition = self::TYPES[$type];
        $default = $this->locales->defaultLocale();
        $query = $this->withSource($this->query($type, $user), $definition['primary'], $default)
            ->with(['translations' => fn ($q) => $q->whereIn('locale', array_unique([$default, $locale]))])
            ->when($missingOnly, fn ($q) => $q->whereDoesntHave('translations', fn ($t) => $this->filled($t, $definition['primary'], $locale)))
            ->orderBy('id');

        $page = $query->paginate($perPage);
        $page->getCollection()->transform(function (Model $model) use ($default, $locale, $type) {
            $byLocale = $model->translationsByLocale();

            return [
                'id' => $model->getKey(),
                'context' => $this->context($type, $model),
                'source' => $byLocale[$default] ?? null,
                'translation' => $byLocale[$locale] ?? null,
            ];
        });

        return $page;
    }

    public function find(string $type, User $user, int $id): ?Model
    {
        return $this->query($type, $user)->find($id);
    }

    /**
     * Languages that can be translated into, including inactive ones (prepare, then activate).
     *
     * @return list<string>
     */
    public function activeCodes(): array
    {
        return $this->locales->registeredLocales();
    }

    /**
     * Base query per type, restricted to the user's tenant.
     */
    private function query(string $type, User $user): Builder
    {
        return match ($type) {
            'categories' => ProductCategory::query()->visibleTo($user),
            'products' => Product::query()->visibleTo($user),
            'option_groups' => ProductOptionGroup::query()->whereHas('product', fn ($q) => $q->visibleTo($user))->with('product.translations'),
            'options' => ProductOption::query()->whereHas('group.product', fn ($q) => $q->visibleTo($user))->with('group.translations'),
            'stores' => Store::query()->visibleTo($user),
            'notification_templates' => NotificationTemplate::query(),
        };
    }

    private function withSource(Builder $query, string $primary, string $default): Builder
    {
        return $query->whereHas('translations', fn ($q) => $this->filled($q, $primary, $default));
    }

    private function filled($query, string $attribute, string $locale)
    {
        return $query->where('locale', $locale)->whereNotNull($attribute)->where($attribute, '!=', '');
    }

    /**
     * A short non-translated hint so translators know what they are looking at.
     */
    private function context(string $type, Model $model): string
    {
        return match ($type) {
            'categories' => $model->code,
            'products' => $model->sku,
            'option_groups' => (string) $model->product?->translate('name'),
            'options' => (string) $model->group?->translate('name'),
            'stores' => $model->code.' · '.$model->name,
            'notification_templates' => $model->code.' · '.$model->channel->value,
        };
    }
}
