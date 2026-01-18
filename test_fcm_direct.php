<?php
// test_fcm_direct.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/app/Helpers/FCMHelper.php';

use App\Helpers\FCMHelper;
use App\Models\User;

echo "=== DIAGNOSTIK PUSH NOTIFICATION ===\n";

// 1. Cek Service Account
$env = getenv('FIREBASE_SERVICE_ACCOUNT');
$file = __DIR__ . '/service-account.json';

if ($env) {
    echo "✅ ENV Variable terdeteksi.\n";
} else {
    echo "⚠️ ENV Variable kosong.\n";
}

if (file_exists($file)) {
    echo "✅ File service-account.json terdeteksi.\n";
} else {
    echo "❌ File service-account.json tidak ditemukan.\n";
}

if (!$env && !file_exists($file)) {
    die("⛔ CRITICAL: Tidak ada kredensial Firebase!\n");
}

// 2. Test Get Access Token
echo "\n⏳ Mencoba Authentikasi ke Google...\n";
// Reflection untuk akses method private jika perlu, tapi kita coba kirim langsung saja.
// Method getAccessToken private, jadi kita test lewat sendToTopic.

// 3. Test Kirim ke Topic Dummy
$topic = 'test_topic'; // User bisa coba subscribe ke ini kalau mau debug frontend
$title = 'Test System';
$body = 'Ini adalah pesan test dari backend PHP. ' . date('H:i:s');

echo "⏳ Mengirim pesan ke topic '$topic'...\n";
$result = FCMHelper::sendToTopic($topic, $title, $body);

if ($result) {
    echo "\n✅ SUKSES! Backend berhasil mengirim pesan ke Google.\n";
    echo "Info: Jika HP tidak bunyi, berarti masalahnya ada di Setting Flutter (belum subscribe topic atau package name beda).\n";
} else {
    echo "\n❌ GAGAL! Backend tidak bisa mengirim pesan.\n";
    echo "Cek pesan error di atas (jika ada) atau pastikan Private Key valid.\n";
}
?>
