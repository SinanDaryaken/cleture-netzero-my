<?php

namespace App\Http\Controllers\IdentityAccess;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationNoticeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('identity-access/VerifyEmail');
    }
}
