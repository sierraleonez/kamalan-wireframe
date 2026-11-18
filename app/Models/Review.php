<?php

namespace App\Models;

use App\Models\Concerns\FlushesCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Ulasan pengunjung untuk satu vendor. Hanya yang 'approved' tampil dan masuk rata-rata. */
class Review extends Model
{
    use FlushesCatalog;

    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['event_month' => 'date', 'reviewed_at' => 'datetime', 'rating' => 'integer'];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function occasion(): BelongsTo
    {
        return $this->belongsTo(Occasion::class);
    }

    /** "Rina P.": nama depan + inisial nama belakang; email tidak pernah tampil. */
    public static function displayName(?string $name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);
        if (! $parts) {
            return 'Pengunjung';
        }

        return count($parts) > 1 ? $parts[0].' '.mb_strtoupper(mb_substr(end($parts), 0, 1)).'.' : $parts[0];
    }
}
