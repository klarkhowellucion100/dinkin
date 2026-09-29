<x-main-layout>

    <div class="container-fluid">

        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>
                <h1 class="h3 mb-1 font-weight-bold">
                    User Management
                </h1>

                <p class="text-muted mb-0">
                    Manage user accounts, approval status, and access roles.
                </p>
            </div>

        </div>


        {{-- Success Toast --}}
        @if (session('success'))
            <div class="toast-container position-fixed" style="top: 20px; right: 20px; z-index: 1100;">
                <div class="toast border-0 shadow" id="successToast" role="alert" aria-live="assertive" aria-atomic="true"
                    data-delay="5000">

                    <div class="toast-header bg-success text-white">

                        <i class="fas fa-check-circle mr-2"></i>

                        <strong class="mr-auto">
                            Success
                        </strong>

                        <button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast"
                            aria-label="Close">
                            <span aria-hidden="true">
                                &times;
                            </span>
                        </button>

                    </div>

                    <div class="toast-body">
                        {{ session('success') }}
                    </div>

                </div>
            </div>
        @endif


        {{-- Validation Errors --}}
        @if ($errors->any())

            <div class="toast-container position-fixed" style="top: 20px; right: 20px; z-index: 1100;">

                @foreach ($errors->all() as $error)
                    <div class="toast border-0 shadow mb-2" role="alert" aria-live="assertive" aria-atomic="true"
                        data-delay="5000">

                        <div class="toast-header bg-danger text-white">

                            <i class="fas fa-exclamation-triangle mr-2"></i>

                            <strong class="mr-auto">
                                Error
                            </strong>

                            <button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast"
                                aria-label="Close">
                                <span aria-hidden="true">
                                    &times;
                                </span>
                            </button>

                        </div>

                        <div class="toast-body">
                            {{ $error }}
                        </div>

                    </div>
                @endforeach

            </div>

        @endif


        {{-- Users Table --}}
        <div class="card shadow-sm">

            <div class="card-header">

                <h3 class="card-title">
                    <i class="fas fa-users mr-2"></i>
                    Registered Users
                </h3>

                <div class="card-tools">

                    <span class="badge badge-primary">
                        {{ $users->count() }} Users
                    </span>

                </div>

            </div>


            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-hover mb-0">

                        <thead class="thead-light">

                            <tr>
                                <th style="width: 60px;">
                                    #
                                </th>

                                <th>
                                    Name
                                </th>

                                <th>
                                    Email
                                </th>

                                <th>
                                    Role
                                </th>

                                <th>
                                    Approval
                                </th>

                                <th>
                                    Registered
                                </th>

                                <th class="text-right" style="width: 120px;">
                                    Action
                                </th>
                            </tr>

                        </thead>


                        <tbody>

                            @forelse ($users as $user)
                                <tr>

                                    <td>
                                        {{ $loop->iteration }}
                                    </td>


                                    <td>

                                        <strong>
                                            {{ $user->name }}
                                        </strong>

                                    </td>


                                    <td>
                                        {{ $user->email }}
                                    </td>


                                    {{-- Role --}}
                                    <td>

                                        @if ($user->role == 1)
                                            <span class="badge badge-danger">
                                                <i class="fas fa-user-shield mr-1"></i>
                                                Admin
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">
                                                <i class="fas fa-user mr-1"></i>
                                                User
                                            </span>
                                        @endif

                                    </td>


                                    {{-- Approval --}}
                                    <td>

                                        @if ($user->approval == 1)
                                            <span class="badge badge-success">
                                                <i class="fas fa-check-circle mr-1"></i>
                                                Approved
                                            </span>
                                        @else
                                            <span class="badge badge-warning">
                                                <i class="fas fa-clock mr-1"></i>
                                                Pending
                                            </span>
                                        @endif

                                    </td>


                                    {{-- Registered --}}
                                    <td>

                                        <small class="text-muted">
                                            {{ $user->created_at->format('M d, Y h:i A') }}
                                        </small>

                                    </td>


                                    {{-- Action --}}
                                    <td class="text-right">

                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            data-toggle="modal" data-target="#editUserModal-{{ $user->id }}">
                                            <i class="fas fa-user-edit"></i>
                                        </button>

                                    </td>

                                </tr>


                                {{-- Edit User Modal --}}
                                <div class="modal fade" id="editUserModal-{{ $user->id }}" tabindex="-1"
                                    role="dialog" aria-labelledby="editUserModalLabel-{{ $user->id }}"
                                    aria-hidden="true">

                                    <div class="modal-dialog modal-dialog-centered" role="document">

                                        <div class="modal-content">

                                            <form method="POST"
                                                action="{{ route('users.update', Crypt::encryptString($user->id)) }}">

                                                @csrf
                                                @method('PUT')


                                                <div class="modal-header">

                                                    <h5 class="modal-title"
                                                        id="editUserModalLabel-{{ $user->id }}">
                                                        <i class="fas fa-user-edit mr-2"></i>
                                                        Manage User
                                                    </h5>

                                                    <button type="button" class="close" data-dismiss="modal"
                                                        aria-label="Close">
                                                        <span aria-hidden="true">
                                                            &times;
                                                        </span>
                                                    </button>

                                                </div>


                                                <div class="modal-body">

                                                    {{-- User Information --}}
                                                    <div class="mb-4">

                                                        <h6 class="font-weight-bold">
                                                            User Information
                                                        </h6>

                                                        <div class="bg-light rounded p-3">

                                                            <div class="mb-2">
                                                                <small class="text-muted">
                                                                    Name
                                                                </small>

                                                                <div class="font-weight-bold">
                                                                    {{ $user->name }}
                                                                </div>
                                                            </div>


                                                            <div>
                                                                <small class="text-muted">
                                                                    Email
                                                                </small>

                                                                <div>
                                                                    {{ $user->email }}
                                                                </div>
                                                            </div>

                                                        </div>

                                                    </div>


                                                    {{-- Approval --}}
                                                    <div class="form-group">

                                                        <label for="approval-{{ $user->id }}">
                                                            Account Approval
                                                        </label>

                                                        <select name="approval" id="approval-{{ $user->id }}"
                                                            class="form-control">

                                                            <option value="0"
                                                                {{ $user->approval == 0 ? 'selected' : '' }}>
                                                                Pending
                                                            </option>

                                                            <option value="1"
                                                                {{ $user->approval == 1 ? 'selected' : '' }}>
                                                                Approved
                                                            </option>

                                                        </select>

                                                    </div>


                                                    {{-- Role --}}
                                                    <div class="form-group mb-0">

                                                        <label for="role-{{ $user->id }}">
                                                            User Role
                                                        </label>

                                                        <select name="role" id="role-{{ $user->id }}"
                                                            class="form-control">

                                                            <option value="0"
                                                                {{ $user->role == 0 ? 'selected' : '' }}>
                                                                User
                                                            </option>

                                                            <option value="1"
                                                                {{ $user->role == 1 ? 'selected' : '' }}>
                                                                Admin
                                                            </option>

                                                        </select>

                                                    </div>

                                                </div>


                                                <div class="modal-footer">

                                                    <button type="button" class="btn btn-secondary"
                                                        data-dismiss="modal">
                                                        Cancel
                                                    </button>

                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-save mr-1"></i>
                                                        Save Changes
                                                    </button>

                                                </div>

                                            </form>

                                        </div>

                                    </div>

                                </div>

                            @empty

                                <tr>

                                    <td colspan="7" class="text-center text-muted py-5">

                                        <i class="fas fa-users-slash fa-3x mb-3"></i>

                                        <p class="mb-0">
                                            No registered users found.
                                        </p>

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>


    {{-- Toast Script --}}
    <script>
        $(document).ready(function() {

            $('.toast').toast('show');

        });
    </script>

</x-main-layout>
