{{--
    Record component "user.details": the Users table's right panel.
    Plain HTML with __PLACEHOLDER__ tokens, filled in the browser from the
    record (the table row plus the user-account business entity record, so
    "labels.roles" are the user's role names). See user-interface
    docs/record-components.md.
--}}
@php
    // Uploaded pictures are stored by file name and served at /profile-picture/{file}.
    $pictureBase = Route::has('profile.picture')
        ? Str::beforeLast(route('profile.picture', ['filename' => 'x'], false), 'x')
        : '';

    // Render the shared status component once and preserve the placeholder token for client-side binding replacement.
    $labStatusTemplate = trim(preg_replace(
        '/\s+/',
        ' ',
        \Illuminate\Support\Facades\Blade::render('<x-userinterface::status status="__VALUE__" class="mt-2">__VALUE__</x-userinterface::status>')
    ));
@endphp

<div class="px-3 px-lg-4 pb-3 pb-lg-4">
    <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3 mb-4">
        <div
            class="d-flex align-items-center justify-content-center overflow-hidden rounded-circle bg-light border flex-shrink-0"
            style="width: 72px; height: 72px;"
        >
            __LAB_AVATAR_BLOCK__
        </div>

        <div class="min-w-0">
            <h4 class="mb-1 fw-semibold text-break">__LAB_NAME__</h4>
            __LAB_EMAIL_BLOCK__
            __LAB_STATUS_BLOCK__
        </div>
    </div>

    __LAB_ROLES_BLOCK__

    <div class="d-flex flex-column gap-2">
        __LAB_INFO_ROWS__
    </div>
</div>
<script type="application/json" data-component-bindings>
{
    "__LAB_NAME__": {
        "type": "text",
        "paths": ["name", "user__name", "full_name", "display_name", "username", "detail_summary.user_name"],
        "fallback": "Unknown User"
    },
    "__LAB_AVATAR_BLOCK__": {
        "type": "template",
        "fields": {
            "src": {
                "paths": ["meta:profile_picture", "meta:logo", "profile_image", "avatar"],
                "prefix": @json($pictureBase),
                "transform": "asset_url"
            },
            "alt": {
                "paths": ["name", "user__name", "full_name", "display_name", "username"],
                "fallback": "Unknown User"
            },
            "initials": {
                "paths": ["name", "user__name", "full_name", "display_name", "username", "email", "user__email"],
                "transform": "initials",
                "fallback": "U"
            }
        },
        "condition": "src",
        "template": "<img src=\"__SRC__\" alt=\"__ALT__\" class=\"img-fluid rounded-circle\" style=\"width: 100%; height: 100%; object-fit: cover;\" referrerpolicy=\"no-referrer\">",
        "fallback_template": "<span class=\"fw-semibold text-secondary fs-5\">__INITIALS__</span>"
    },
    "__LAB_EMAIL_BLOCK__": {
        "type": "template",
        "fields": {
            "value": {
                "paths": ["email", "user__email", "meta:email"]
            }
        },
        "condition": "value",
        "template": "<div class=\"small text-muted text-break\">__VALUE__</div>",
        "fallback_template": ""
    },
    "__LAB_STATUS_BLOCK__": {
        "type": "template",
        "fields": {
            "value": {
                "paths": ["status"]
            }
        },
        "condition": "value",
        "template": @json($labStatusTemplate),
        "fallback_template": ""
    },
    "__LAB_ROLES_BLOCK__": {
        "type": "badges",
        "paths": ["labels.roles"],
        "class": "badge text-bg-light border me-1 mb-1",
        "template": "<div class=\"mb-3\"><div class=\"small text-muted fw-semibold mb-1\">Roles</div>__BADGES__</div>",
        "fallback_template": "<div class=\"mb-3\"><div class=\"small text-muted fw-semibold mb-1\">Roles</div><span class=\"small text-muted\">No roles</span></div>"
    },
    "__LAB_INFO_ROWS__": {
        "type": "key_value_rows",
        "rows": [
            { "label": "Email", "paths": ["email", "user__email", "meta:email"] },
            { "label": "Phone", "paths": ["phone", "user__phone", "mobile", "meta:phone"] },
            { "label": "Status", "paths": ["status"] },
            { "label": "User ID", "paths": ["uid", "id"] },
            { "label": "Registered At", "paths": ["registered_at", "created_at", "detail_summary.display_datetime"] },
            { "label": "Login IP", "paths": ["login_ip_address", "meta:login_ip_address", "meta:login_ip"] },
            { "label": "Updated By", "paths": ["updated_by_name", "detail_summary.user_name"] },
            { "label": "Updated At", "paths": ["updated_at", "created_at", "detail_summary.display_datetime"] }
        ],
        "row_template": "<div class=\"d-flex flex-column flex-sm-row gap-1 gap-sm-3 py-1 border-bottom border-light-subtle\"><div class=\"text-muted fw-semibold\" style=\"min-width: 140px;\">__LABEL__</div><div class=\"text-break\">__VALUE__</div></div>",
        "fallback_template": "<div class=\"text-center text-muted py-4\">No user details available.</div>"
    }
}
</script>
