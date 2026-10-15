<?php

namespace App\Models;

use App\Support\Format;
use App\Support\Media;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['category_id', 'user_id', 'title', 'slug', 'excerpt', 'body', 'image', 'author', 'views', 'is_published', 'published_at'])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'views' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Posts visible on the website.
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    #[Scope]
    protected function newest(Builder $query): void
    {
        $query->latest('published_at')->latest('id');
    }

    public function isVisible(): bool
    {
        return $this->is_published && $this->published_at !== null && $this->published_at->lte(now());
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => Media::url($this->image));
    }

    /**
     * Card date, e.g. "12 Okt 2026".
     */
    protected function dateLabel(): Attribute
    {
        return Attribute::get(fn (): string => Format::shortDate($this->published_at ?? $this->created_at));
    }

    /**
     * The body rendered from Markdown with raw HTML stripped.
     */
    protected function bodyHtml(): Attribute
    {
        return Attribute::get(fn (): string => Str::markdown((string) $this->body, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]));
    }

    protected function summary(): Attribute
    {
        return Attribute::get(fn (): string => $this->excerpt ?: Str::limit(strip_tags($this->body_html), 180));
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'berita';
        $slug = $base;
        $n = 2;

        while (static::query()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
