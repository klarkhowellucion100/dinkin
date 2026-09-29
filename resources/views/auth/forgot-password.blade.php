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
                <p class="login-box-msg">Forgot your password? No problem. Just let us know your email address and we
                    will email you a password reset link that will allow you to choose a new one.</p>

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf

                    <!-- Email Address -->
                    <div class="input-group mb-3">
                        <input type="email" class="form-control" placeholder="Email" name="email"
                            :value="old('email')" required autofocus autocomplete="email">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div class="row">
                        <!-- /.col -->
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-block">Email Password Reset Link</button>
                        </div>
                        <!-- /.col -->
                    </div>

                </form>
            </div>
            <!-- /.login-card-body -->
        </div>
    </div>


</x-guest-layout>
