<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Rank;
use App\Models\Option;
use App\Models\Area;
use App\Models\Codeword;
use App\Models\Cast;
use App\Models\Driver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 管理者ユーザー
        User::firstOrCreate(['email' => 'admin@example.com'], [
            'name'     => '管理者',
            'password' => Hash::make('password'),
        ]);

        // ランク
        $ranks = [
            ['name' => '新人',     'designation_fee' =>    0, 'color' => 'bg-gray-400',   'order' => 1],
            ['name' => 'レギュラー','designation_fee' => 1000, 'color' => 'bg-blue-500',   'order' => 2],
            ['name' => 'Aランク',  'designation_fee' => 2000, 'color' => 'bg-purple-500', 'order' => 3],
            ['name' => 'Sランク',  'designation_fee' => 3000, 'color' => 'bg-yellow-500', 'order' => 4],
            ['name' => 'SSランク', 'designation_fee' => 5000, 'color' => 'bg-red-500',    'order' => 5],
        ];
        foreach ($ranks as $r) {
            Rank::firstOrCreate(['name' => $r['name']], $r);
        }

        // オプション
        $options = [
            ['name' => 'AF',        'price' =>  3000, 'description' => null],
            ['name' => 'NN',        'price' =>  3000, 'description' => null],
            ['name' => 'オプション1', 'price' =>  2000, 'description' => null],
            ['name' => 'オプション2', 'price' =>  2000, 'description' => null],
            ['name' => '撮影',      'price' =>  5000, 'description' => null],
        ];
        foreach ($options as $o) {
            Option::firstOrCreate(['name' => $o['name']], $o);
        }

        // エリア交通費（東京23区）
        $areas = [
            ['area' => '千代田区', 'fee' => 1000],
            ['area' => '中央区',   'fee' => 1000],
            ['area' => '港区',     'fee' => 1000],
            ['area' => '新宿区',   'fee' => 1000],
            ['area' => '文京区',   'fee' => 1500],
            ['area' => '台東区',   'fee' => 1500],
            ['area' => '墨田区',   'fee' => 1500],
            ['area' => '江東区',   'fee' => 1500],
            ['area' => '品川区',   'fee' => 1500],
            ['area' => '目黒区',   'fee' => 1500],
            ['area' => '大田区',   'fee' => 2000],
            ['area' => '世田谷区', 'fee' => 2000],
            ['area' => '渋谷区',   'fee' => 1000],
            ['area' => '中野区',   'fee' => 2000],
            ['area' => '杉並区',   'fee' => 2000],
            ['area' => '豊島区',   'fee' => 1500],
            ['area' => '北区',     'fee' => 2000],
            ['area' => '荒川区',   'fee' => 2000],
            ['area' => '板橋区',   'fee' => 2000],
            ['area' => '練馬区',   'fee' => 2500],
            ['area' => '足立区',   'fee' => 2500],
            ['area' => '葛飾区',   'fee' => 2500],
            ['area' => '江戸川区', 'fee' => 2500],
        ];
        foreach ($areas as $a) {
            Area::firstOrCreate(['area' => $a['area']], $a);
        }

        // 合言葉
        $codewords = [
            ['site_name' => 'デリヘルA',  'word' => 'サイトA見た',   'discount_type' => 'fixed',   'discount_value' => 1000, 'description' => '初回1000円引き', 'is_active' => true],
            ['site_name' => 'デリヘルB',  'word' => 'Bから来た',     'discount_type' => 'percent', 'discount_value' =>   10, 'description' => '10%オフ',        'is_active' => true],
            ['site_name' => 'Xサイト',   'word' => 'X経由',         'discount_type' => 'fixed',   'discount_value' => 2000, 'description' => null,              'is_active' => false],
        ];
        foreach ($codewords as $c) {
            Codeword::firstOrCreate(['site_name' => $c['site_name'], 'word' => $c['word']], $c);
        }

        // キャスト
        $rankIds = Rank::pluck('id', 'name');
        $casts = [
            ['name' => '葵', 'age' => 22, 'status' => '待機中', 'rank_id' => $rankIds['SSランク'] ?? null],
            ['name' => '蘭', 'age' => 24, 'status' => '待機中', 'rank_id' => $rankIds['Sランク']  ?? null],
            ['name' => '桜', 'age' => 20, 'status' => '稼働中', 'rank_id' => $rankIds['Aランク']  ?? null],
            ['name' => '涼', 'age' => 21, 'status' => '待機中', 'rank_id' => $rankIds['レギュラー'] ?? null],
            ['name' => '心', 'age' => 23, 'status' => '休み',   'rank_id' => $rankIds['新人']     ?? null],
        ];
        foreach ($casts as $c) {
            Cast::firstOrCreate(['name' => $c['name']], $c);
        }

        // ドライバー
        $drivers = [
            ['name' => '山田 一郎', 'status' => '稼働中', 'car' => 'トヨタ アルファード',    'phone' => '090-1111-1001'],
            ['name' => '佐藤 健',   'status' => '待機中', 'car' => '日産 エルグランド',      'phone' => '090-1111-1002'],
            ['name' => '中村 誠',   'status' => '稼働中', 'car' => 'トヨタ ヴェルファイア',  'phone' => '090-1111-1003'],
            ['name' => '渡辺 浩',   'status' => '待機中', 'car' => '日産 セレナ',            'phone' => '090-1111-1004'],
        ];
        foreach ($drivers as $d) {
            Driver::firstOrCreate(['name' => $d['name']], $d);
        }
    }
}
