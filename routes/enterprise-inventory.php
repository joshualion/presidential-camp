<?php

use App\Http\Controllers\CampaignInventoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'check_onboarding'])->prefix('campaign-inventory')->name('enterprise.inventory.')->group(function () {
    $gate = fn (string $permission) => 'enterprise.feature:campaign_merchandise,'.$permission;
    Route::get('/', [CampaignInventoryController::class, 'index'])->middleware($gate('merchandise.view'))->name('index');
    Route::get('/locations', [CampaignInventoryController::class, 'locations'])->middleware($gate('merchandise.view'))->name('locations');
    Route::get('/export', [CampaignInventoryController::class, 'export'])->middleware($gate('merchandise.reports.export'))->name('export');
    Route::post('/categories', [CampaignInventoryController::class, 'category'])->middleware($gate('merchandise.categories.manage'))->name('categories');
    Route::post('/materials', [CampaignInventoryController::class, 'material'])->middleware($gate('merchandise.products.manage'))->name('materials');
    Route::post('/adjustments', [CampaignInventoryController::class, 'adjust'])->middleware($gate('merchandise.inventory.manage'))->name('adjustments');
    Route::post('/requests', [CampaignInventoryController::class, 'requestStock'])->middleware($gate('merchandise.orders.view'))->name('requests');
    Route::post('/requests/{id}/cancel', [CampaignInventoryController::class, 'cancel'])->whereNumber('id')->middleware($gate('merchandise.orders.view'))->name('cancel');
    Route::post('/requests/{id}/fulfil', [CampaignInventoryController::class, 'fulfil'])->whereNumber('id')->middleware($gate('merchandise.orders.fulfil'))->name('fulfil');
});
