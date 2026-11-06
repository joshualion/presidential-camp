<?php

use App\Http\Controllers\CampaignFinanceController;
use App\Http\Controllers\CampaignFinanceSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'enterprise.feature:campaign_finance,finance.view'])->prefix('campaign-finance')->name('finance.')->group(function () {
    Route::get('/', [CampaignFinanceController::class, 'index'])->name('index');
    Route::get('/settings', [CampaignFinanceSettingsController::class, 'show'])->name('settings');
    Route::post('/settings', [CampaignFinanceSettingsController::class, 'save'])->name('settings.save');
    Route::post('/settings/governance', [CampaignFinanceSettingsController::class, 'saveGovernance'])->name('settings.governance');
    Route::post('/settings/appointments', [CampaignFinanceSettingsController::class, 'appoint'])->name('appointments.store');
    Route::post('/settings/appointments/{id}/revoke', [CampaignFinanceSettingsController::class, 'revoke'])->whereNumber('id')->name('appointments.revoke');
    Route::get('/section/{section}', [CampaignFinanceController::class, 'section'])->name('section');
    Route::post('/accounts/{id}/reconcile', [CampaignFinanceController::class, 'reconcile'])->whereNumber('id')->name('reconcile');
    Route::post('/requests/{id}/cancel', [CampaignFinanceController::class, 'cancel'])->whereNumber('id')->name('requests.cancel');
    Route::post('/accounts', [CampaignFinanceController::class, 'account'])->name('accounts.store');
    Route::post('/budgets', [CampaignFinanceController::class, 'budget'])->name('budgets.store');
    Route::post('/requests', [CampaignFinanceController::class, 'paymentRequest'])->name('requests.store');
    Route::post('/funding-proposals', [CampaignFinanceController::class, 'submitFundingProposal'])->name('proposals.store');
    Route::post('/requests/{id}/decision', [CampaignFinanceController::class, 'decide'])->whereNumber('id')->name('requests.decide');
    Route::post('/accounts/{accountId}/entries/{entryId}/reverse', [CampaignFinanceController::class, 'reverse'])
        ->whereNumber(['accountId', 'entryId'])->name('entries.reverse');
    Route::post('/requests/{id}/movement', [CampaignFinanceController::class, 'postMovement'])->whereNumber('id')->name('movements.store');
    Route::post('/requests/{id}/settlement', [CampaignFinanceController::class, 'settleAdvance'])->whereNumber('id')->name('advances.settle');
    Route::post('/manual-expenses', [CampaignFinanceController::class, 'manualExpense'])->name('expenses.store');
    Route::post('/entries', [CampaignFinanceController::class, 'record'])->name('entries.store');
    Route::post('/documents', [CampaignFinanceController::class, 'document'])->name('documents.store');
    Route::get('/documents/{id}', [CampaignFinanceController::class, 'downloadDocument'])->whereNumber('id')->name('documents.download');
    Route::get('/accounts/{id}/statement', [CampaignFinanceController::class, 'statement'])->whereNumber('id')->name('statement');
});
