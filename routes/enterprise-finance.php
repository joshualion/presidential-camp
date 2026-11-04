<?php

use App\Http\Controllers\CampaignFinanceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'enterprise.feature:campaign_finance,finance.view'])->prefix('campaign-finance')->name('finance.')->group(function () {
    Route::get('/', [CampaignFinanceController::class, 'index'])->name('index');
    Route::post('/accounts', [CampaignFinanceController::class, 'account'])->name('accounts.store');
    Route::post('/budgets', [CampaignFinanceController::class, 'budget'])->name('budgets.store');
    Route::post('/requests', [CampaignFinanceController::class, 'paymentRequest'])->name('requests.store');
    Route::post('/requests/{id}/decision', [CampaignFinanceController::class, 'decide'])->whereNumber('id')->name('requests.decide');
    Route::post('/entries', [CampaignFinanceController::class, 'record'])->name('entries.store');
    Route::post('/documents', [CampaignFinanceController::class, 'document'])->name('documents.store');
    Route::get('/documents/{id}', [CampaignFinanceController::class, 'downloadDocument'])->whereNumber('id')->name('documents.download');
    Route::get('/accounts/{id}/statement', [CampaignFinanceController::class, 'statement'])->whereNumber('id')->name('statement');
});
