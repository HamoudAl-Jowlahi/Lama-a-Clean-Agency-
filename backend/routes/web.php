<?php

use Illuminate\Support\Facades\Route;

// لا توجد واجهة ويب عامة: التطبيق يستخدم /api/v1، والإدارة على /admin
Route::redirect('/', '/admin');
