<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Pegawai;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pegawai>
 */
class PegawaiFactory extends Factory
{
    protected $model = Pegawai::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'no_kp' => fake()->unique()->numerify('############'),
            'ptj_id' => 1,
            'jawatan_id' => 1,
            'gred_id' => 1,
            'rsvp' => fake()->boolean(),
            'no_kerusi' => fake()->optional(0.75)->numberBetween(1, 200),
            'is_attend' => fake()->boolean(35),
            'no_meja' => fn (array $attributes): ?int => ! empty($attributes['is_attend'])
                ? fake()->numberBetween(1, 40)
                : null,
            'no_panggilan_lewat' => null,
            'no_sijil' => fake()->optional(0.55)->numerify('###'),
        ];
    }
}
