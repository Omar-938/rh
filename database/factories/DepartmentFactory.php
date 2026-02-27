<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    private static array $departments = [
        ['name' => 'Ressources Humaines',  'color' => '#9B59B6'],
        ['name' => 'Informatique',         'color' => '#2E86C1'],
        ['name' => 'Commercial',           'color' => '#27AE60'],
        ['name' => 'Finance & Comptabilité','color' => '#F39C12'],
        ['name' => 'Marketing',            'color' => '#E74C3C'],
        ['name' => 'Production',           'color' => '#1ABC9C'],
        ['name' => 'Logistique',           'color' => '#E67E22'],
        ['name' => 'Direction',            'color' => '#1B4F72'],
        ['name' => 'Juridique',            'color' => '#7F8C8D'],
        ['name' => 'Qualité',              'color' => '#16A085'],
    ];

    public function definition(): array
    {
        $dept = $this->faker->unique()->randomElement(self::$departments);

        return [
            'name'        => $dept['name'],
            'color'       => $dept['color'],
            'description' => $this->faker->optional(0.6)->sentence(),
            'manager_id'  => null,
        ];
    }
}
