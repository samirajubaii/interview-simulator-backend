<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Frontend',
            'Backend',
            'Full Stack',
            'Data Engineer',
            'Mobile',
            'DevOps',
        ];

        foreach ($categories as $name) {
            Category::create(['name' => $name]);
        }
    }
}