<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Interfaces\WriterPortfolioRepositoryInterface;
use App\Repositories\WriterPortfolioRepository;
use App\Repositories\Interfaces\ManageTestLinkRepositoryInterface;
use App\Repositories\ManageTestLinkRepository;
use App\Repositories\Interfaces\PaystackWebhookEventRepositoryInterface;
use App\Repositories\PaystackWebhookEventRepository;
use App\Repositories\Interfaces\OutgoingTransactionRepositoryInterface;
use App\Repositories\OutgoingTransactionRepository;
use App\Repositories\Interfaces\PaymentTransferRecipientRepositoryInterface;
use App\Repositories\PaymentTransferRecipientRepository;
use App\Repositories\Interfaces\IncomingTransactionRepositoryInterface;
use App\Repositories\IncomingTransactionRepository;
use App\Repositories\Interfaces\DraftJobRepositoryInterface;
use App\Repositories\DraftJobRepository;
use App\Repositories\Interfaces\JobsProposalAssetsRepositoryInterface;
use App\Repositories\JobsProposalAssetsRepository;
use App\Repositories\Interfaces\JobsPaymentMilestonesRepositoryInterface;
use App\Repositories\JobsPaymentMilestonesRepository;
use App\Repositories\Interfaces\JobsProposalRepositoryInterface;
use App\Repositories\JobsProposalRepository;
use App\Repositories\Interfaces\SavedJobsRepositoryInterface;
use App\Repositories\SavedJobsRepository;
use App\Repositories\Interfaces\JobAssetsRepositoryInterface;
use App\Repositories\JobAssetsRepository;
use App\Repositories\Interfaces\JobsRepositoryInterface;
use App\Repositories\JobsRepository;
use App\Repositories\Interfaces\AdminVerifyWriterRepositoryInterface;
use App\Repositories\AdminVerifyWriterRepository;
use App\Repositories\Interfaces\EmailVerificationTokenRepositoryInterface;
use App\Repositories\EmailVerificationTokenRepository;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\UserRepository;

class RepositoryServiceProvider extends ServiceProvider
{
  public function register()
  {
    //bind your interface here
    $this->app->bind(
      UserRepositoryInterface::class, UserRepository::class
    );

    $this->app->bind(
      EmailVerificationTokenRepositoryInterface::class, EmailVerificationTokenRepository::class
    );
    
  }
}

