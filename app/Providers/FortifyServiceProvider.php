<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Contracts\PasswordResetResponse as PasswordResetResponseContract;
use Laravel\Fortify\Contracts\EmailVerificationNotificationSentResponse as EmailVerificationNotificationSentResponseContract;
use Laravel\Fortify\Contracts\PasswordConfirmedResponse as PasswordConfirmedResponseContract;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse as SuccessfulPasswordResetLinkRequestResponseContract;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Override Fortify's response contracts to return JSON instead of redirects
        $this->app->singleton(LoginResponseContract::class, function () {
            return new class implements LoginResponseContract {
                public function toResponse($request)
                {
                    $user = $request->user();

                    if ($user->is_banned) {
                        auth()->logout();
                        return new JsonResponse([
                            'success' => false,
                            'message' => 'Your account has been suspended.',
                        ], 403);
                    }

                    $token = $user->createToken('api-token')->plainTextToken;

                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Login successful.',
                        'data' => [
                            'user' => $user,
                            'token' => $token,
                        ],
                    ]);
                }
            };
        });

        $this->app->singleton(RegisterResponseContract::class, function () {
            return new class implements RegisterResponseContract {
                public function toResponse($request)
                {
                    $user = $request->user() ?? auth()->user();
                    $token = $user->createToken('api-token')->plainTextToken;

                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Registration successful. Please verify your email.',
                        'data' => [
                            'user' => $user,
                            'token' => $token,
                        ],
                    ], 201);
                }
            };
        });

        $this->app->singleton(LogoutResponseContract::class, function () {
            return new class implements LogoutResponseContract {
                public function toResponse($request)
                {
                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Logged out successfully.',
                    ]);
                }
            };
        });

        $this->app->singleton(PasswordResetResponseContract::class, function () {
            return new class implements PasswordResetResponseContract {
                public function toResponse($request)
                {
                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Password has been reset successfully.',
                    ]);
                }
            };
        });

        $this->app->singleton(SuccessfulPasswordResetLinkRequestResponseContract::class, function () {
            return new class implements SuccessfulPasswordResetLinkRequestResponseContract {
                public function toResponse($request)
                {
                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Password reset link sent to your email.',
                    ]);
                }
            };
        });

        $this->app->singleton(VerifyEmailResponseContract::class, function () {
            return new class implements VerifyEmailResponseContract {
                public function toResponse($request)
                {
                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Email verified successfully.',
                    ]);
                }
            };
        });

        $this->app->singleton(EmailVerificationNotificationSentResponseContract::class, function () {
            return new class implements EmailVerificationNotificationSentResponseContract {
                public function toResponse($request)
                {
                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Verification email sent.',
                    ]);
                }
            };
        });

        $this->app->singleton(PasswordConfirmedResponseContract::class, function () {
            return new class implements PasswordConfirmedResponseContract {
                public function toResponse($request)
                {
                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Password confirmed.',
                    ]);
                }
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
