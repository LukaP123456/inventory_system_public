<?php

namespace Database\Seeders;

use App\Models\Archive_inventory;
use App\Models\Company;
use App\Models\Inventory;
use App\Models\ListingStatus;
use App\Models\Room;
use App\Models\Room_team;
use App\Models\Room_team_listings;
use App\Models\Messages;
use App\Models\Listing;
use App\Models\Product;
use App\Models\QR;
use App\Models\Team;
use App\Models\Team_user;
use App\Models\Temp_inventory;
use App\Models\User;
use App\Models\User_company;
use Database\Factories\ListingFactory;
use Database\Factories\TempInventoryFactory;
use Faker\Factory;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        User::factory(10)->create();
        Company::factory(10)->create();
        Product::factory(10)->create();
        Room::factory(10)->create();
        Messages::factory(10)->create();
        Listing::factory(10)->create();
        Team::factory(10)->create();
        QR::factory(10)->create();
        Temp_inventory::factory(10)->create();
        Archive_inventory::factory(10)->create();
        Inventory::factory(10)->create();
        Room_team::factory(10)->create();
        Room_team_listings::factory(10)->create();
        Team_user::factory(10)->create();
        User_company::factory(10)->create();
        ListingStatus::factory(10)->create();




        User::factory()->create([
            'first_name' => 'Default',
            'last_name' => 'korisnik',
            'verified' => 1,
            'role' => 'worker',
            'email' => 'bobsagott17@gmail.com',
            'email_verified_at' => now(),
            'password' => bcrypt('sifra123456!'),
        ]);
        Company::factory()->create([
            'co_name' => 'Kompanija 1',
            'description' => 'Kompanija 1 opis',
            'created_at' => now(),
        ]);
        Company::factory()->create([
            'co_name' => 'Kompanija 2',
            'description' => 'Kompanija 2 opis',
            'created_at' => now(),
        ]);
        User_company::factory()->create([
            'company_id' => 11,
            'user_id' => 11,
            'created_at' => now()
        ]);
        User_company::factory()->create([
            'company_id' => 12,
            'user_id' => 11,
            'created_at' => now()
        ]);
        Team::factory()->create([
            'name' => 'Prvi tim',
            'location' => 'Subotica',
            'company_id' => 11,
            'description' => 'Prvi tim opis',
            'created_at' => now(),
        ]);
        Team::factory()->create([
            'name' => 'Drugi tim',
            'location' => 'Kanjiza',
            'company_id' => 12,
            'description' => 'Drugi tim opis',
            'created_at' => now(),
        ]);
        Team_user::factory()->create([
            'team_id' => 11,
            'user_id' => 11,
            'created_at' => now()
        ]);
        Team_user::factory()->create([
            'team_id' => 12,
            'user_id' => 11,
            'created_at' => now()
        ]);
        Room::factory()->create([
            'company_id' => 11,
            'size' => 100,
            'name' => 'Room 1 name',
            'location' => 'Subotica',
            'description' => 'Room 1 opis',
            'created_at' => now(),
        ]);
        Room::factory()->create([
            'company_id' => 12,
            'size' => 200,
            'name' => 'Room 2 name',
            'location' => 'Kanjiza',
            'description' => 'Room 2 opis',
            'created_at' => now(),
        ]);
        Room_team::factory()->create([
            'team_id' => 11,
            'room_id' => 11,
            'description' => 'Neki opis',
            'created_at' => now()
        ]);
        Room_team::factory()->create([
            'team_id' => 12,
            'room_id' => 12,
            'description' => 'Neki opis',
            'created_at' => now()
        ]);
        Room_team::factory()->create([
            'team_id' => 12,
            'room_id' => 11,
            'description' => 'Neki opis',
            'created_at' => now()
        ]);
        Listing::factory()->create([
            'start_time' => now(),
            'company_id' => 11,
            'listing_name' => 'Prvi listing',
            'description' => 'Prvi listing opis',
            'status' => 'ongoing',
            'created_at' => now()
        ]);
        Listing::factory()->create([
            'start_time' => now(),
            'company_id' => 12,
            'listing_name' => 'Drugi listing',
            'description' => 'Drugi listing opis',
            'status' => 'ongoing',
            'created_at' => now()
        ]);
        Room_team_listings::factory()->create([
            'team_id' => 11,
            'listing_id' => 11,
            'room_id' => 11
        ]);
        Room_team_listings::factory()->create([
            'team_id' => 12,
            'listing_id' => 12,
            'room_id' => 12
        ]);
        ListingStatus::factory()->create([
            'listing_id' => 11,
            'user_id' => 11,
            'room_id' => 11,
            'status' => 'ongoing',
            'created_at' => now(),
        ]);
        ListingStatus::factory()->create([
            'listing_id' => 12,
            'user_id' => 11,
            'room_id' => 12,
            'status' => 'ongoing',
            'created_at' => now(),
        ]);
        Product::factory()->create([
            'name' => 'Moj proizvod',
            'producer' => 'Kompanija 1',
            'company_id' => 11,
            'description' => 'Opis proizovda 11',
            'price' => 123,
            'created_at' => now(),
        ]);
        Product::factory()->create([
            'name' => 'Moj proizvod2',
            'producer' => 'Kompanija 2',
            'company_id' => 12,
            'description' => 'Opis proizovda 12',
            'price' => 222,
            'created_at' => now(),
        ]);

        for ($i = 1 ; $i <= 5; $i++){
            Temp_inventory::factory()->create([
                'product_id'=>$i,
                'user_id'=>$i,
                'room_id'=>11,
                'listing_id'=>11,
                'quantity'=>111+$i,
                'created_at'=>now()
            ]);
        }

        for ($i = 1 ; $i <= 5; $i++){
            Inventory::factory()->create([
                'product_id'=>$i,
                'room_id'=>11,
                'quantity'=>111+$i,
                'created_at'=>now()
            ]);
        }
    }
}
