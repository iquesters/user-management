@props([
    'permissions',
    'selectedPermissions' => [],
    'mode' => 'edit' // default mode
])

<div class="">
    <label class="form-label">Permissions</label>

    @php
        // Group permissions by their type
        $groupedPermissions = [];
        foreach($permissions as $permission) {
            $parts = explode('-', $permission->name);
            $type = count($parts) >= 3
                ? implode(' ', array_slice($parts, -2))
                : end($parts);

            if (!isset($groupedPermissions[$type])) {
                $groupedPermissions[$type] = [];
            }
            $groupedPermissions[$type][] = $permission;
        }
        ksort($groupedPermissions);
    @endphp

    <div class="row g2">
        @foreach($groupedPermissions as $type => $permissionsGroup)
        <div class="col-lg-4 col-md-6 col-12 mb-2">
            <div class="bg-light p-2 rounded h-100">
                <div class="d-flex align-items-center">
                    <div class="form-check">
                        {{-- Disable group checkbox in view mode --}}
                        <input type="checkbox"
                               class="form-check-input group-select-all"
                               id="group-{{ str_replace(' ', '-', $type) }}"
                               data-group="{{ str_replace(' ', '-', $type) }}"
                               @if($mode === 'view') disabled @endif
                               @if(count(array_intersect($selectedPermissions, array_map(fn($p) => $p->name, $permissionsGroup))) === count($permissionsGroup)) checked @endif>
                        <label class="@if($mode === 'edit') form-check-label @endif fw-semibold text-muted" for="group-{{ str_replace(' ', '-', $type) }}">
                            {{ ucwords($type) }}
                        </label>
                    </div>
                </div>
                <div class="row">
                    @foreach($permissionsGroup as $permission)
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox"
                                   class="form-check-input permission-checkbox group-{{ str_replace(' ', '-', $type) }}"
                                   id="permission-{{ $permission->id }}"
                                   name="permissions[]"
                                   value="{{ $permission->name }}"
                                   @if(in_array($permission->name, $selectedPermissions)) checked @endif
                                   @if($mode === 'view') disabled @endif>
                            <label class="@if($mode === 'edit') form-check-label @endif text-muted" for="permission-{{ $permission->id }}">
                                {{ ucwords(str_replace('-', ' ', $permission->name)) }}
                            </label>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @error('permissions')
        <span class="text-danger text-sm">{{ $message }}</span>
    @enderror
</div>

@push('scripts')
@if($mode === 'edit')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const groupSelectAlls = document.querySelectorAll('.group-select-all');

    groupSelectAlls.forEach(checkbox => {
        const groupName = checkbox.getAttribute('data-group');
        const groupCheckboxes = document.querySelectorAll(`.group-${groupName}`);

        function updateSelectAllState() {
            const checkedCount = Array.from(groupCheckboxes).filter(cb => cb.checked).length;
            checkbox.checked = checkedCount === groupCheckboxes.length;
            checkbox.indeterminate = checkedCount > 0 && checkedCount < groupCheckboxes.length;
        }

        updateSelectAllState();

        checkbox.addEventListener('click', function() {
            const isChecked = this.checked;
            groupCheckboxes.forEach(cb => {
                cb.checked = isChecked;
            });
            updateSelectAllState();
        });

        groupCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateSelectAllState);
        });
    });
});
</script>
@endif
@endpush