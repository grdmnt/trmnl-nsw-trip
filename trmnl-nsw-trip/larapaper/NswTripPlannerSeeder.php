<?php

namespace Database\Seeders;

use App\Models\Plugin;
use Illuminate\Database\Seeder;

class NswTripPlannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run($user_id = 1): void
    {
        Plugin::updateOrCreate(
            ['uuid' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890'],
            [
                'name' => 'NSW Trip Planner',
                'user_id' => $user_id,
                'data_payload' => null,
                'data_stale_minutes' => 5,
                'data_strategy' => 'polling',
                'configuration_template' => [
                    'custom_fields' => [
                        [
                            'keyname' => 'origin_name',
                            'field_type' => 'text',
                            'name' => 'Origin Stop Name',
                            'default' => 'West Ryde',
                            'description' => 'Enter the origin stop name (e.g., West Ryde, Town Hall, Central)',
                        ],
                        [
                            'keyname' => 'destination_name',
                            'field_type' => 'text',
                            'name' => 'Destination Stop Name',
                            'default' => 'Town Hall',
                            'description' => 'Enter the destination stop name (e.g., Town Hall, Bondi Junction, Parramatta)',
                        ],
                    ],
                ],
                'configuration' => [
                    'origin_name' => 'West Ryde',
                    'destination_name' => 'Town Hall',
                ],
                'polling_url' => 'http://localhost:8080/trips?origin={{origin_name}}&destination={{destination_name}}',
                'polling_verb' => 'get',
                'polling_header' => null,
                'render_markup' => null,
                'render_markup_view' => 'recipes.nsw-trip',
                'detail_view_route' => null,
                'icon_url' => null,
                'flux_icon_name' => 'train-front',
            ]
        );
    }
}
