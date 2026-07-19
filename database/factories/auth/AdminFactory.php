<?php

namespace Database\Factories\auth;

use App\Models\auth\Admin;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class AdminFactory extends Factory
{
    protected $model = Admin::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'username' => $this->faker->unique()->userName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => '05' . $this->faker->numerify('########'),
            'whatsapp' => '05' . $this->faker->numerify('########'),
            'password' => Hash::make('password'),
            'admin_type' => 'admin',
            'status' => 'active',
            'branch_id' => null,
            'is_developer' => 0,
            'sex_type' => 0,
        ];
    }

    /** An admin scoped to a single branch (branch-level user). */
    public function forBranch(Branch|int $branch): self
    {
        return $this->state([
            'branch_id' => $branch instanceof Branch ? $branch->id : $branch,
        ]);
    }

    /** An admin with no branch — sees every branch. */
    public function global(): self
    {
        return $this->state(['branch_id' => null]);
    }

    public function superAdmin(): self
    {
        return $this->state(['admin_type' => 'super_admin']);
    }

    public function suspended(): self
    {
        return $this->state(['status' => 'suspended']);
    }
}
