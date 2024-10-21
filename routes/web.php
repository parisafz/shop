<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Authentication
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

// مسیر برای انتقال به صفحه احراز هویت
Route::get('/redirect', function (Request $request) {
    // ساخت کوئری برای ارسال به سرور OAuth
    $query = http_build_query([
        'client_id' => 3,   // شناسه کلاینت
        'redirect_uri' => 'http://localhost:8001/info-bank/callback',  // URI بازگشتی بعد از احراز هویت
        'response_type' => 'code',   // نوع پاسخ
        'scope' => '*'   // سطح دسترسی
    ]);

    // انتقال به صفحه احراز هویت با کوئری ساخته شده
    return redirect('http://localhost:8000/oauth/authorize?' . $query);
})->name('info.bank');

// مسیر برای دریافت پاسخ از سرور بعد از احراز هویت
Route::get('info-bank/callback', function (Request $request) {

    // بررسی وجود خطا در پاسخ
    if ($request->has('error')) {
        return 'access denied';   // در صورت وجود خطا، دسترسی رد می‌شود
    }

    $http = new GuzzleHttp\Client;

    // ارسال درخواست برای دریافت توکن
    $response = $http->post(
        'http://localhost:8000/oauth/token',
        ['form_params' => [
            'grant_type' => 'authorization_code',
            'client_id' => 3,
            'client_secret' => 'VFfoFhcoG9y5w3OQQCX61xTsNcY0dEhl6wu6LrFm',
            'redirect_uri' => 'http://localhost:8001/info-bank/callback',
            'code' => $request->code,   // کد دریافتی از سرور
        ],]
    );

    // دریافت توکن دسترسی
    $accessToken = json_decode((string) $response->getBody(), true);
    // نمایش توکن دسترسی جهت تست
    dd($accessToken['access_token']);
});
