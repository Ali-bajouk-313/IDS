<?php
$hashes = [
    '$2y$12$/kTv1scIQyEnRtm7F8C0PeWMPb8kU3OktHbMqlKXv5vbZcitebSsu',
    '$2y$12$VeV9Fmfk9NKwpkewyH60he5j/k35q7Ol/Rsm0m2tNaAfvhUaejB32',
];
$candidates = ['password', 'Password123!', 'password123', '12345678', 'helpdesk', 'helpdeskpro', 'admin123', 'Admin123!'];
foreach ($hashes as $hash) {
    foreach ($candidates as $pass) {
        if (password_verify($pass, $hash)) {
            echo $pass . PHP_EOL;
        }
    }
}
