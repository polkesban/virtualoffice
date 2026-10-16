<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\UnitEvent;
use Illuminate\Support\Str;

$event = UnitEvent::findOrFail(70);
$oldSlug = $event->slug;
$newSlug = Str::slug($event->judul);

echo "Old slug: $oldSlug\n";
echo "New slug: $newSlug\n";

if ($oldSlug !== $newSlug) {
    $event->slug = $newSlug;
    $event->save();
    echo "Slug successfully updated to match September title!\n";
} else {
    echo "Slug is already up to date.\n";
}
