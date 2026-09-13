<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nav_links', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('url');
            $table->boolean('is_external')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // banner | hero | richtext | steps
            $table->boolean('is_visible')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('data');
            $table->timestamps();
        });

        // Seed default front-page content so the page isn't empty after this migration —
        // admins can freely edit/reorder/delete all of it afterward.
        $now = now();

        DB::table('nav_links')->insert([
            ['label' => 'Főoldal', 'url' => '/', 'is_external' => false, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['label' => 'Csatlakozz hozzánk', 'url' => '/jelentkezes', 'is_external' => false, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['label' => 'Belső oldalak', 'url' => '/dashboard', 'is_external' => false, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('page_sections')->insert([
            [
                'type' => 'banner', 'is_visible' => true, 'sort_order' => 1,
                'data' => json_encode(['text' => 'VÉSZHELYZET ESETÉN HÍVD A 9-1-1-ET', 'url' => null, 'variant' => 'dark']),
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'type' => 'hero', 'is_visible' => true, 'sort_order' => 2,
                'data' => json_encode(['eyebrow' => 'ÜDVÖZLÜNK A', 'title' => 'LOS SANTOS FIRE DEPARTMENT', 'image_url' => null, 'show_seal' => true]),
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'type' => 'richtext', 'is_visible' => true, 'sort_order' => 3,
                'data' => json_encode(['heading' => null, 'body' => 'Üdvözlünk a Los Santos Fire Department hivatalos weboldalán. Csapatunk Los Santos és környékének lakosságát szolgálja tűzoltóként és mentőként.', 'show_seal' => false]),
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'type' => 'steps', 'is_visible' => true, 'sort_order' => 4,
                'data' => json_encode(['heading' => 'Hogyan tudsz csatlakozni?', 'items' => [
                    ['title' => 'Regisztráció', 'body' => 'Hozz létre egy fiókot felhasználónévvel és jelszóval.'],
                    ['title' => 'Jelentkezés', 'body' => 'Küldd el jelentkezésed a Csatlakozz hozzánk oldalon.'],
                    ['title' => 'Elbírálás', 'body' => 'Egy adminisztrátor elbírálja a jelentkezésed, és értesítést kapsz az eredményről.'],
                ]]),
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('page_sections');
        Schema::dropIfExists('nav_links');
    }
};
