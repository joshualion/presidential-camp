<?php

use App\Http\Controllers\CampaignFinanceController;
use App\Http\Controllers\CampaignFinanceSettingsController;
use App\Http\Controllers\CampaignPaystackController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::post('/campaign-finance/paystack/webhook/{mode}', [CampaignPaystackController::class, 'webhook'])
    ->whereIn('mode', ['test', 'live'])->withoutMiddleware(ValidateCsrfToken::class)
    ->middleware('throttle:60,1')->name('finance.paystack.webhook');

Route::middleware(['auth', 'enterprise.feature:campaign_finance,finance.view'])->prefix('campaign-finance')->name('finance.')->group(function () {
    Route::get('/', [CampaignFinanceController::class, 'index'])->name('index');
    Route::get('/paystack/settings', [CampaignPaystackController::class, 'settings'])->name('paystack.settings');
    Route::post('/paystack/settings', [CampaignPaystackController::class, 'save'])->name('paystack.settings.save');
    Route::post('/paystack/recipient', [CampaignPaystackController::class, 'recipient'])->middleware('throttle:10,1')->name('paystack.recipient');
    Route::post('/paystack/beneficiary', [CampaignPaystackController::class, 'linkBeneficiary'])->middleware('throttle:10,1')->name('paystack.beneficiary');
    Route::post('/paystack/batch/{id}/approve', [CampaignPaystackController::class, 'approveBatch'])->middleware('throttle:10,1')->whereNumber('id')->name('paystack.batch.approve');
    Route::post('/paystack/batch/{id}', [CampaignPaystackController::class, 'submitBatch'])->middleware('throttle:10,1')->whereNumber('id')->name('paystack.batch.submit');
    Route::get('/paystack', [CampaignPaystackController::class, 'index'])->name('paystack.index');
    Route::post('/paystack/collect', [CampaignPaystackController::class, 'collect'])->middleware('throttle:10,1')->name('paystack.collect');
    Route::post('/paystack/transfer/{id}', [CampaignPaystackController::class, 'transfer'])->middleware('throttle:10,1')->whereNumber('id')->name('paystack.transfer');
    Route::post('/paystack/settlements/{id}/match', [CampaignPaystackController::class, 'matchSettlement'])
        ->middleware('throttle:10,1')->whereNumber('id')->name('paystack.settlements.match');
    Route::post('/paystack/refunds/{reference}', [CampaignPaystackController::class, 'refund'])->middleware('throttle:10,1')->whereUuid('reference')->name('paystack.refund');
    Route::post('/paystack/verify/{reference}', [CampaignPaystackController::class, 'verify'])->middleware('throttle:10,1')->whereUuid('reference')->name('paystack.verify');
    Route::post('/paystack/transfer/{id}/approve', [CampaignPaystackController::class, 'approveTransfer'])->middleware('throttle:10,1')->whereNumber('id')->name('paystack.approve');
    Route::get('/settings', [CampaignFinanceSettingsController::class, 'show'])->name('settings');
    Route::post('/settings', [CampaignFinanceSettingsController::class, 'save'])->name('settings.save');
    Route::post('/settings/governance', [CampaignFinanceSettingsController::class, 'saveGovernance'])->name('settings.governance');
    Route::post('/settings/appointments', [CampaignFinanceSettingsController::class, 'appoint'])->name('appointments.store');
    Route::post('/settings/appointments/{id}/revoke', [CampaignFinanceSettingsController::class, 'revoke'])->whereNumber('id')->name('appointments.revoke');
    Route::get('/section/{section}', [CampaignFinanceController::class, 'section'])->name('section');
    Route::post('/accounts/{id}/periods/close', [CampaignFinanceController::class, 'closePeriod'])->whereNumber('id')->name('periods.close');
    Route::post('/accounts/{id}/periods/reopen', [CampaignFinanceController::class, 'reopenPeriod'])->whereNumber('id')->name('periods.reopen');
    Route::post('/statements/checks', [CampaignFinanceController::class, 'prepareStatementCheck'])->name('statements.prepare');
    Route::post('/statements/import', [CampaignFinanceController::class, 'importStatement'])->name('statements.import');
    Route::post('/statements/checks/{id}/resolve', [CampaignFinanceController::class, 'resolveStatementVariance'])->whereNumber('id')->name('statements.resolve');
    Route::post('/statements/checks/{id}/review', [CampaignFinanceController::class, 'reviewStatementCheck'])->whereNumber('id')->name('statements.review');
    Route::post('/accounts/{id}/reconcile', [CampaignFinanceController::class, 'reconcile'])->whereNumber('id')->name('reconcile');
    Route::post('/requests/{id}/cancel', [CampaignFinanceController::class, 'cancel'])->whereNumber('id')->name('requests.cancel');
    Route::post('/accounts', [CampaignFinanceController::class, 'account'])->name('accounts.store');
    Route::post('/budgets', [CampaignFinanceController::class, 'budget'])->name('budgets.store');
    Route::post('/requests', [CampaignFinanceController::class, 'paymentRequest'])->name('requests.store');
    Route::post('/funding-proposals', [CampaignFinanceController::class, 'submitFundingProposal'])->name('proposals.store');
    Route::get('/funding-proposals/{id}/history', [CampaignFinanceController::class, 'fundingProposalHistory'])->whereNumber('id')->name('proposals.history');
    Route::post('/funding-proposals/{id}/review', [CampaignFinanceController::class, 'reviewFundingProposal'])->whereNumber('id')->name('proposals.review');
    Route::post('/funding-proposals/{id}/resubmit', [CampaignFinanceController::class, 'resubmitFundingProposal'])->whereNumber('id')->name('proposals.resubmit');
    Route::post('/funding-proposals/{id}/assign', [CampaignFinanceController::class, 'assignFundingProposal'])->whereNumber('id')->name('proposals.assign');
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
