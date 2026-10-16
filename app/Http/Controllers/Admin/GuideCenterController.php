<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\GuideCatalog;

class GuideCenterController extends Controller
{
    public function index(GuideCatalog $guideCatalog)
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        return view('admin.guides.index', [
            'guideGroups' => $guideCatalog->groupsForCenter(),
            'guideStats' => $guideCatalog->stats(),
        ]);
    }
}