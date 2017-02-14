<?php

namespace Thaliak\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\MediaLibrary\HasMedia\Interfaces\HasMedia;
use Thaliak\Http\Lodestone\Character as LodestoneCharacter;
use Thaliak\Models\Enum\CharacterStatus;
use Thaliak\Models\Traits\HasVerificationCodes;

class Character extends Model implements HasMedia
{
    use HasMediaTrait, HasVerificationCodes;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = ['world', 'verification', 'media'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['avatar', 'portrait'];

    /**
     * Relationship: User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: World
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function world()
    {
        return $this->belongsTo(World::class);
    }

    /**
     * Accessor: avatar URL
     *
     * @return string
     */
    public function getAvatarAttribute()
    {
        return $this->media->where('name', 'avatar')->first()->getUrl();
    }

    /**
     * Accessor: portrait URL
     *
     * @return string
     */
    public function getPortraitAttribute()
    {
        return $this->media->where('name', 'portrait')->first()->getUrl();
    }

    /**
     * Scope: World
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param World $world
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWorld($query, $world)
    {
        return $query->whereHas('world', function ($query) use ($world) {
            $query->whereRaw('LOWER(name) = ?', [$world->name]);
        });
    }

    /**
     * Create a character from a Lodestone instance
     *
     * @param LodestoneCharacter
     * @param User $user
     * @param World $world
     * @return Character
     */
    public static function createFromLodestone(LodestoneCharacter $character, User $user, World $world)
    {
        return static::create([
            'id'            => $character->id,
            'user_id'       => $user->id,
            'world_id'      => $world->id,
            'verified'      => false,
            'name'          => $character->name,
            'gender'        => $character->gender,
            'race'          => $character->race,
            'clan'          => $character->clan,
            'nameday'       => $character->nameday,
            'guardian'      => $character->guardian,
            'city_state'    => $character->city_state,
            'active_class'  => $character->active_class['id'],
            'status'        => CharacterStatus::ALT
        ]);
    }

    /**
     * Set the current character instance as 'main', changing the status of
     * other characters owned by the same user to 'alt'.
     *
     * @return Character
     */
    public function setMain()
    {
        $this->update(['status' => CharacterStatus::MAIN]);

        $this->user->characters()->where('id', '!=', $this->id)->update([
            'status' => CharacterStatus::ALT
        ]);

        return $this;
    }
}
