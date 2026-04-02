<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Seed the home-service categories and subcategories.
     */
    public function run(): void
    {
        $categories = [
            'Roofing' => [
                'icon' => 'roof',
                'subcategories' => [
                    'Roof Repair',
                    'Roof Replacement',
                    'Roof Inspection',
                    'Gutter Installation',
                    'Roof Cleaning',
                    'Metal Roofing',
                    'Flat Roofing',
                ],
            ],
            'Plumbing' => [
                'icon' => 'plumbing',
                'subcategories' => [
                    'Leak Repair',
                    'Drain Cleaning',
                    'Water Heater',
                    'Pipe Installation',
                    'Sewer Line',
                    'Bathroom Plumbing',
                    'Kitchen Plumbing',
                    'Emergency Plumbing',
                ],
            ],
            'Electrical' => [
                'icon' => 'electrical',
                'subcategories' => [
                    'Wiring & Rewiring',
                    'Panel Upgrades',
                    'Lighting Installation',
                    'Outlet & Switch Repair',
                    'Generator Installation',
                    'EV Charger Installation',
                    'Ceiling Fan Installation',
                ],
            ],
            'HVAC' => [
                'icon' => 'hvac',
                'subcategories' => [
                    'AC Installation',
                    'AC Repair',
                    'Heating Repair',
                    'Furnace Installation',
                    'Duct Cleaning',
                    'Thermostat Installation',
                    'Heat Pump',
                ],
            ],
            'Landscaping' => [
                'icon' => 'landscaping',
                'subcategories' => [
                    'Lawn Care',
                    'Tree Trimming',
                    'Garden Design',
                    'Irrigation Systems',
                    'Hardscaping',
                    'Sod Installation',
                    'Fence Installation',
                    'Snow Removal',
                ],
            ],
            'Painting' => [
                'icon' => 'painting',
                'subcategories' => [
                    'Interior Painting',
                    'Exterior Painting',
                    'Cabinet Painting',
                    'Deck Staining',
                    'Wallpaper Installation',
                    'Pressure Washing',
                    'Commercial Painting',
                ],
            ],
            'Flooring' => [
                'icon' => 'flooring',
                'subcategories' => [
                    'Hardwood Floors',
                    'Tile Installation',
                    'Carpet Installation',
                    'Vinyl & Laminate',
                    'Floor Refinishing',
                    'Epoxy Flooring',
                    'Floor Repair',
                ],
            ],
            'Remodeling' => [
                'icon' => 'remodeling',
                'subcategories' => [
                    'Kitchen Remodeling',
                    'Bathroom Remodeling',
                    'Basement Finishing',
                    'Room Additions',
                    'Home Office',
                    'Garage Conversion',
                    'Whole House Remodel',
                ],
            ],
            'Cleaning' => [
                'icon' => 'cleaning',
                'subcategories' => [
                    'House Cleaning',
                    'Deep Cleaning',
                    'Move-In/Move-Out',
                    'Carpet Cleaning',
                    'Window Cleaning',
                    'Post-Construction Cleanup',
                    'Commercial Cleaning',
                ],
            ],
        ];

        $sortOrder = 0;

        foreach ($categories as $name => $data) {
            $parent = Category::create([
                'name' => $name,
                'slug' => \Illuminate\Support\Str::slug($name),
                'icon' => $data['icon'],
                'sort_order' => $sortOrder++,
            ]);

            $subSortOrder = 0;
            foreach ($data['subcategories'] as $subName) {
                Category::create([
                    'name' => $subName,
                    'slug' => \Illuminate\Support\Str::slug($name . '-' . $subName),
                    'parent_id' => $parent->id,
                    'sort_order' => $subSortOrder++,
                ]);
            }
        }
    }
}
