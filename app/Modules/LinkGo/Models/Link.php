<?php

namespace App\Modules\LinkGo\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Link extends Model
{
    use SoftDeletes;

    public const VISIBILITY_ALL = 'all_jtmk';
    public const VISIBILITY_KJ_KPRO = 'kj_kpro_only';
    public const VISIBILITY_OWNER = 'owner_only';

    protected $table = 'link_go_links';

    protected $fillable = [
        'user_id',
        'portfolio_id',
        'title',
        'url',
        'description',
        'visibility',
        'is_pinned',
        'is_active',
        'click_count',
        'copy_count',
        'last_clicked_at',
        'last_copied_at',
    ];

    public static function visibilityOptions(): array
    {
        return [
            self::VISIBILITY_ALL => 'All JTMK',
            self::VISIBILITY_KJ_KPRO => 'KJ/KPRO only',
            self::VISIBILITY_OWNER => 'Owner only',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class, 'portfolio_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('title', 'like', "%{$search}%")
                ->orWhere('url', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('owner', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                ->orWhereHas('portfolio', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
        });
    }

    public function scopeVisibleTo(Builder $query, User $user, bool $isModuleAdmin = false): Builder
    {
        if ($user->is_super_admin || $isModuleAdmin) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query
                ->where('visibility', self::VISIBILITY_ALL)
                ->orWhere('user_id', $user->id)
                ->orWhere(function (Builder $query) use ($user): void {
                    $query->where('visibility', self::VISIBILITY_KJ_KPRO);

                    if (! $user->canViewSensitiveStaffDirectory()) {
                        $query->whereRaw('1 = 0');
                    }
                });
        });
    }

    public function isViewableBy(User $user, bool $isModuleAdmin = false): bool
    {
        if ($user->is_super_admin || $isModuleAdmin || $this->user_id === $user->id) {
            return true;
        }

        return $this->is_active && match ($this->visibility) {
            self::VISIBILITY_ALL => true,
            self::VISIBILITY_KJ_KPRO => $user->canViewSensitiveStaffDirectory(),
            default => false,
        };
    }

    public function canBeEditedBy(User $user, bool $isModuleAdmin = false): bool
    {
        return $isModuleAdmin || (! $user->is_super_admin && $this->user_id === $user->id);
    }

    public function canBeDeletedBy(User $user, bool $isModuleAdmin = false): bool
    {
        return $this->canBeEditedBy($user, $isModuleAdmin);
    }

    public function visibilityLabel(): string
    {
        return self::visibilityOptions()[$this->visibility] ?? str($this->visibility)->replace('_', ' ')->title()->toString();
    }

    public function cleanFilename(string $extension): string
    {
        $base = str($this->title)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '_')
            ->trim('_')
            ->limit(80, '')
            ->toString();

        return ($base ?: 'LINK_GO_QR').'.'.$extension;
    }

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'is_active' => 'boolean',
            'click_count' => 'integer',
            'copy_count' => 'integer',
            'last_clicked_at' => 'datetime',
            'last_copied_at' => 'datetime',
        ];
    }
}
