<x-guest-layout>
    <!-- Session Status -->


    <div class="login-box">
        <div class="login-logo">
            <img src="{{ url('frontend/img/logo.png') }}" alt="" height="50" width="50">
            <a href="#" class="small">{{ config('app.aclsappname') }}</a>
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <!-- /.login-logo -->
        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg">Sign in to start your session</p>

                <form method="POST" action="{{ route('password.store') }}">
                    @csrf

                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <!-- Email Address -->
                    <div class="input-group mb-3">
                        <input type="email" class="form-control" placeholder="Email" name="email"
                            value="{{ $request->email }}" required autofocus autocomplete="username" required autofocus
                            autocomplete="email">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div class="input-group mb-3">
                        <input type="password" class="form-control" placeholder="New Password" name="password" required
                            autocomplete="new-password">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>


                    <div class="input-group mb-3">
                        <input type="password" class="form-control" placeholder="Confirm New Password"
                            name="password_confirmation" required autocomplete="new-password"
                            id="password_confirmation">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>

                    <div class="row">
                        <!-- /.col -->
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
                        </div>
                        <!-- /.col -->
                    </div>
                </form>

            </div>
            <!-- /.login-card-body -->
        </div>
    </div>


</x-guest-layout>
