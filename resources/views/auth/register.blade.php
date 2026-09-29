<x-guest-layout>
    <!-- Session Status -->



    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="login-box">
        <div class="login-logo">
            <img src="{{ url('frontend/img/logo.png') }}" alt="" height="50" width="50">
            <a href="#" class="small">{{ config('app.aclsappname') }}</a>
        </div>

        <!-- /.login-logo -->
        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg">Sign in to start your session</p>

                <form method="POST" action="{{ route('register') }}">
                    @csrf

                    <div class="input-group mb-3">
                        <input type="text" class="form-control" placeholder="Name" name="name"
                            :value="old('name')" required autofocus autocomplete="name">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-building"></span>
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>


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

                    <div class="input-group mb-3">
                        <input type="password" class="form-control" placeholder="Password" name="password" required
                            autocomplete="current-password">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="input-group mb-3">
                        <input type="password" class="form-control" placeholder="Confirm Password"
                            name="password_confirmation" required autocomplete="current-password">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end mt-4">

                        <div class="row">
                            <div class="col-8">
                                <p class="mb-1">
                                    <a href="{{ route('login') }}">Already Registered?</a>
                                </p>
                            </div>
                            <!-- /.col -->
                            <div class="col-4">
                                <button type="submit" class="btn btn-primary btn-block">Register</button>
                            </div>
                            <!-- /.col -->
                        </div>

                    </div>
                </form>
            </div>
            <!-- /.login-card-body -->
        </div>
    </div>


</x-guest-layout>
