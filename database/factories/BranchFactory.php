<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'name_ar' => 'فرع ' . $this->faker->unique()->city(),
            'name_en' => $this->faker->unique()->city() . ' Branch',
            'code' => strtoupper($this->faker->unique()->bothify('BR-###')),
            'phone' => '05' . $this->faker->numerify('########'),
            'email' => $this->faker->unique()->safeEmail(),
            'city' => $this->faker->city(),
            'address' => $this->faker->address(),
            'is_active' => true,
            'is_main' => false,
            'sort_order' => 0,
        ];
    }

    public function inactive(): self
    {
        return $this->state(['is_active' => false]);
    }

    public function main(): self
    {
        return $this->state(['is_main' => true, 'sort_order' => 0]);
    }
}
