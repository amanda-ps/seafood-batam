<?php
// Script to generate 20+ realistic restaurants JSON locally to avoid massive token generation manually
$districts = ['Batam Kota', 'Lubuk Baja', 'Batu Ampar', 'Bengkong', 'Sekupang', 'Nongsa', 'Sagulung', 'Batu Aji', 'Belakang Padang', 'Bulang', 'Galang', 'Sei Beduk'];

$restaurant_names = [
    "Kepiting Ajjohn Batam", "Seafood 1986", "RM. Lembur Laut", "Golden Prawn 933", 
    "Rezeki Seafood", "Wey Wey Seafood", "Love Seafood PIK", "Sri Rejeki Seafood", 
    "Barelang Seafood", "Bella Seafood", "Piayu Laut Seafood", "Aman Seafood", 
    "Sagulung Fresh Seafood", "Belakang Padang Crab House", "Tanjung Riau Resto", 
    "Ocean Kopitiam & Seafood", "Mutiara Kelong", "Jodoh Seafood Market", 
    "Ocarina Coast Dining", "Bintang Laut", "Harbour Bay Seafood", "Kopak Jaya 007 Kelong"
];

$unsplash_images_location = [
    "https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=800&q=80",
    "https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=800&q=80",
    "https://images.unsplash.com/photo-1550966871-3ed3cdb5ed0c?w=800&q=80",
    "https://images.unsplash.com/photo-1559339352-11d035aa65de?w=800&q=80",
    "https://images.unsplash.com/photo-1537047902294-62a40c20a6ae?w=800&q=80"
];

$unsplash_images_food = [
    "https://images.unsplash.com/photo-1565557623262-b51c2513a641?w=800&q=80", // crab
    "https://images.unsplash.com/photo-1599084924200-ce1b4fc0ce18?w=800&q=80", // squid
    "https://images.unsplash.com/photo-1574781489061-cecf30536417?w=800&q=80", // general
    "https://images.unsplash.com/photo-1627885934571-70bfb3100be0?w=800&q=80", // prawn
    "https://images.unsplash.com/photo-1544521406-8d1dcbd5bc4c?w=800&q=80", // fish
    "https://images.unsplash.com/photo-1533682805518-48d1f5a8bb3a?w=800&q=80", // lobster
    "https://images.unsplash.com/photo-1620062758113-d02f7415ab54?w=800&q=80" // oysters
];

$menu_pool = [
    ['name' => 'Kepiting Saus Padang', 'price' => 'Rp 250.000', 'description' => 'Kepiting segar dimasak dengan saus pandang pedas manis khas Batam.'],
    ['name' => 'Udang Bakar Madu', 'price' => 'Rp 120.000', 'description' => 'Udang segar dibakar dengan madu asli manis gurih.'],
    ['name' => 'Gonggong Rebus', 'price' => 'Rp 85.000', 'description' => 'Siput laut khas Kepri disajikan dengan sambal cocol.'],
    ['name' => 'Cumi Goreng Tepung', 'price' => 'Rp 75.000', 'description' => 'Cumi segar digoreng renyah dengan tepung bumbu.'],
    ['name' => 'Ikan Kerapu Steam', 'price' => 'Rp 180.000', 'description' => 'Ikan kerapu segar disteam dengan jahe dan kecap asin sedap.'],
    ['name' => 'Ikan Bakar Bumbu Rujak', 'price' => 'Rp 150.000', 'description' => 'Ikan kakap dibakar dengan saus rujak pedas.']
];

$restaurants = [];
foreach ($restaurant_names as $index => $name) {
    // Generate 3 location photos and 4 food photos randomly
    shuffle($unsplash_images_location);
    shuffle($unsplash_images_food);
    $photos = array_merge(array_slice($unsplash_images_location, 0, 3), array_slice($unsplash_images_food, 0, 4));
    
    // Select 3 to 5 random menu items
    shuffle($menu_pool);
    $menus = array_slice($menu_pool, 0, rand(3, 5));
    
    // Generate random ratings and reviews count to be realistic
    $rest_rating = round(rand(41, 49) / 10, 1);

    $restaurants[] = [
        'id' => $index + 1,
        'name' => $name,
        'description' => "Restoran seafood ternama di Batam menyajikan hidangan laut segar langsung dari tangkapan nelayan dengan pemandangan dan pelayanan terbaik.",
        'district' => $districts[$index % count($districts)],
        'rating' => $rest_rating,
        'reviews_count' => rand(50, 1500),
        'address' => "Jalan Kuliner Batam No. " . ($index + 10),
        'hours' => "10:00 - 22:00",
        'maps_link' => "https://maps.google.com/?q=" . urlencode($name),
        'photos' => $photos,
        'menus' => $menus,
        'instagram' => "https://instagram.com/" . preg_replace('/[^a-z0-9]/', '', strtolower($name)),
        'tiktok' => "https://tiktok.com/@" . preg_replace('/[^a-z0-9]/', '', strtolower($name)),
        'whatsapp' => "+62811" . rand(1000000, 9999999)
    ];
}

file_put_contents(__DIR__ . '/../data/restaurants.json', json_encode($restaurants, JSON_PRETTY_PRINT));
echo "Generated restaurants.json successfully!";
?>
