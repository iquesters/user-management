@extends(config('usermanagement.layout_app'))

@section('content')
<div class="mb-2">

    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="fs-6 text-muted">Total {{ $permissions->count() }} Permissions</h5>
        <a href="{{ route('permissions.create') }}"
        class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2 shadow-sm rounded @cannot('create-permissions') disabled @endcannot"
            @cannot('create-permissions') disabled @endcannot>
            <i class="fa-regular fa-plus fs-6"></i>Permission
        </a>
    </div>
    <!-- Permissions Table -->
    <div class="">
        <table id="permissionsTable" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Name</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($permissions as $permission)
                <tr>
                    <td>{{ $permission->name }}</td>
                    <td>
                        <a href="{{ route('permissions.edit', $permission) }}" class="btn btn-sm btn-outline-dark @cannot('edit-permissions') disabled @endcannot"
                            @cannot('edit-permissions') disabled @endcannot><i class="fas fa-fw fa-edit me-1"></i></a>
                        <form action="{{ route('permissions.destroy', $permission) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger @cannot('delete-permissions') disabled @endcannot"
                            @cannot('delete-permissions') disabled @endcannot onclick="return confirm('Are you sure you want to delete this permission?')"><i class="fas fa-fw fa-trash me-1"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#permissionsTable').DataTable();
    });
</script>
@endpush