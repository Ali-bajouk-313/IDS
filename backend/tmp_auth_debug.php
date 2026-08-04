<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$user = User::where('email', 'admin@helpdeskpro.com')->first();
if (!$user) {
    echo "NO_USER\n";
    exit;
}

echo 'user=' . $user->email . PHP_EOL;
echo 'stored_hash=' . $user->password . PHP_EOL;
echo 'check_password=' . (Hash::check('12345678', $user->password) ? 'true' : 'false') . PHP_EOL;
echo 'check_password2=' . (Hash::check('password', $user->password) ? 'true' : 'false') . PHP_EOL;
echo 'verified=' . ($user->email_verified_at ? $user->email_verified_at : 'null') . PHP_EOL;
