<?php
declare(strict_types=1);

namespace App\Models;

use Database\Factories\SyncFactory;
use Eloquent\Pathogen\AbsolutePath;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sync extends Model {
    use HasFactory;
    
    protected $table = 'sync';

    protected $fillable = [
        'user_id',
        'filename', 
        'completed',
        'sync_id'
    ];

    protected $casts = [
        'completed' => 'bool'
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    /**
     * @param Builder $query
     * @param User $user
     * @return Builder
     */
    public function scopeForUser(Builder $query, User $user): Builder {
        return $query->where('user_id', $user->id);
    }

    public function scopeWhereIsCompleted(Builder $query) {
        return $query->where('completed', true)->whereNotNull('sync_id');
    }

    public function logs(): HasMany {
        return $this->hasMany(SyncLog::class);
    }

    public function hasError(): bool
    {
        $definiteError = $this->logs()->where('severity', 'error')->count() > 0;
        $isOld = $this->created_at->addMinutes(10)->lessThan(now());

        return (!$this->completed && $isOld) || $definiteError;
    }

    public function complete(): void
    {
        $this->completed = true;
        $this->save();
    }

    public static function formatForResponse(self $sync): array {
        return [
            'id' => $sync->id,
            'filename' => AbsolutePath::fromString($sync->filename)->name(),
            'path' => $sync->filename,
            'created_at' => $sync->created_at->diffForHumans(),
            'completed' => $sync->completed,
            'error' => $sync->hasError()
        ];
    }
}
