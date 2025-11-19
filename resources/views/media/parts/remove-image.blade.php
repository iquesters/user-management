<div id="remove-image-dropbox" class="d-none">
    <div class="text-center p-3">
        <h3>Remove profile picture?</h3>
        <img src="{{ $user_logo ? asset($user_logo) : '' }}" 
            class="img-fluid mb-2" 
            style="height: 120px; width : 120px;border-radius: 60px;"
            alt="Current Image">

        <i class="fa-solid fa-arrow-right p-3"></i>

        @php
            $initial = strtoupper(substr($user->name ?? 'S', 0, 1));
        @endphp
        <img src="https://placehold.co/400x400/faf3e0/d72638/png?text={{ $initial }}" 
            class="img-fluid mb-2" 
            style="height: 120px; width : 120px;border-radius: 60px;"
            alt="Default Image">
        <p class="mt-3">Your previous picture will be saved in your past profile pictures album, and this image will be used instead.</p>
    </div>
</div>
