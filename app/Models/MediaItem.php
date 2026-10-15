<?php

namespace App\Models;

use App\Enums\MediaType;
use App\Support\Format;
use App\Support\Media;
use Database\Factories\MediaItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['type', 'title', 'image', 'youtube_url', 'duration', 'poster_line1', 'poster_line2', 'poster_sub', 'poster_tone', 'taken_at', 'sort', 'is_published'])]
class MediaItem extends Model
{
    /** @use HasFactory<MediaItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MediaType::class,
            'taken_at' => 'date',
            'is_published' => 'boolean',
            'sort' => 'integer',
        ];
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true)->orderBy('sort')->orderBy('id');
    }

    public function isVideo(): bool
    {
        return $this->type === MediaType::Video;
    }

    public function hasPoster(): bool
    {
        return ! $this->image && filled($this->poster_line1);
    }

    /**
     * The 11-character YouTube id taken from any common YouTube link form.
     */
    protected function youtubeId(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->youtube_url) {
                return null;
            }

            preg_match('~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/))([A-Za-z0-9_-]{11})~', $this->youtube_url, $m);

            return $m[1] ?? null;
        });
    }

    protected function embedUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->youtube_id
            ? 'https://www.youtube-nocookie.com/embed/'.$this->youtube_id.'?autoplay=1&rel=0'
            : null);
    }

    /**
     * Uploaded image, or the YouTube thumbnail for a video without one.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->image) {
                return Media::url($this->image);
            }

            return $this->youtube_id ? 'https://i.ytimg.com/vi/'.$this->youtube_id.'/hqdefault.jpg' : null;
        });
    }

    /**
     * Gallery date, e.g. "Okt 2026".
     */
    protected function dateLabel(): Attribute
    {
        return Attribute::get(fn (): string => Format::monthYear($this->taken_at ?? $this->created_at));
    }

    /**
     * Shape read by the gallery component.
     *
     * @return array{id: int, type: string, title: string, date: string, image: ?string, embed: ?string, duration: ?string, poster: ?array{line1: string, line2: ?string, sub: ?string, tone: string}}
     */
    public function toGalleryItem(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'title' => $this->title,
            'date' => $this->date_label,
            'image' => $this->image_url,
            'embed' => $this->isVideo() ? $this->embed_url : null,
            'duration' => $this->duration,
            'poster' => $this->hasPoster() ? [
                'line1' => $this->poster_line1,
                'line2' => $this->poster_line2,
                'sub' => $this->poster_sub,
                'tone' => $this->poster_tone ?: 'laut',
            ] : null,
        ];
    }
}
