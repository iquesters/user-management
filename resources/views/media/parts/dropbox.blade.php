@push('styles')
<style>
    .dropbox__file,
    .dropbox__button,
    .dropbox__dragndrop,
    .dropbox__uploading,
    .dropbox__success,
    .dropbox__error {
        display: none;
    }

    .dropbox.has-advanced-upload {
        /* background-color: white; */
        background-color: var(--bs-body-bg);
        /* border: var(--bs-border-width) solid var(--bs-border-color); */
        border-radius: var(--bs-border-radius);
        outline: 2px dashed var(--bs-info-border-subtle);
        outline-offset: -8px;
    }

    .dropbox.has-advanced-upload .dropbox__dragndrop {
        display: block;
    }

    .dropbox.is-dragover {
        background-color: var(--bs-info-bg-subtle);
    }

    .dropbox.is-uploading .dropbox__input {
        visibility: none;
    }

    .dropbox.is-uploading .dropbox__uploading {
        display: block;
    }

    .dropbox {
        min-height: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
    }


    #resize-handle {
        width: 12px;
        height: 12px;
        background: red;
        position: absolute;
        right: -6px;
        bottom: -6px;
        cursor: se-resize;
        border-radius: 50%;
    }
</style>
@endpush


<div id = "media-dropbox" class="">
    <div class="col-md-6 col-lg-6 media-dropbox-container">
        {{-- <div class="text-center p-3">
        <img src="https://placehold.co/400x400/faf3e0/d72638/png?text=A" 
            class="img-fluid mb-2" 
            style="height: 200px; width : 200px; border-radius: 100px;"
            alt="Dropbox Image">
        <p>Drag photo here</p>
        <p>-- or --</p>
        </div> --}}
        <div class="img-thumbnail h-100 d-flex align-items-center row">
            <div class="col-md-6">
                <form class="dropbox h-100 w-100 d-flex align-items-center justify-contents-center"
                    {{-- method="post" --}}
                    {{-- action="{{ route('media.upload') }}" --}}
                    enctype="multipart/form-data">
                    @csrf
                    <div class="dropbox__input w-100 mx-3 text-center">
                        <input class="dropbox__file" type="file" name="media[]" 
                            id="modal-media-upload" data-multiple-caption="{count} files selected" accept="image/*" multiple />
                        <label for="modal-media-upload">
                            <a class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-fw fa-arrow-up-from-bracket"></i>
                                <span class="d-none d-md-inline-block">Upload new Media</span>
                            </a>
                        </label>
                        <span class="dropbox__dragndrop d-none d-md-block">or drop files here</span>
                        <button class="dropbox__button" type="submit">Upload</button>
                    </div>

                    <div class="dropbox__uploading">Uploading...</div>
                    <div class="dropbox__success">Done!</div>
                    <div class="dropbox__error">Error! <span></span>.</div>

                </form>
            </div>


            <!-- Open Camera -->
            <div class="col-md-6 text-center p-3">
                {{-- <h6>Take Photo</h6> --}}

                <form method="post" action="{{ route('media.upload') }}" enctype="multipart/form-data">
                    @csrf

                    <input type="file" 
                        name="media[]" 
                        accept="image/*" 
                        capture="environment" 
                        id="cameraInput"
                        class="d-none">

                    <label for="cameraInput">
                        <a class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-camera fa-arrow-up-from-bracket"></i>
                            <span class="d-none d-md-inline-block">Take a picture</span> 
                        </a>
                    </label>
                </form>
            </div>


        </div>
    </div>

    <div id="image-container" style="position: relative; display: none; margin-top: 20px;">
        <img id="image" src="" alt="" style="max-width: 100%; display: block;">
        <div id="crop-area" 
            style="position: absolute; border: 2px dashed red; background-color: rgba(255,0,0,0.15); cursor: move; display: none;">
        <div id="resize-handle"></div>
        </div>
    </div>
    <button id="cropBtn" class="btn btn-outline-primary btn-sm mt-2" style="display: none;">Crop Image</button>
    <h6 id="cropPreview" class="mt-3" style="display: none;">Cropped Preview</h6>
    <canvas id="canvas" style="display:none; border:1px solid #ccc;"></canvas>
    <button id="saveBtn" class="btn btn-success btn-sm mt-2" style="display:none;">Save Cropped Image</button>
</div>


@pushonce('scripts')
<script>
var isAdvancedUpload = function() {
    var div = document.createElement('div');
    return (('draggable' in div) || ('ondragstart' in div && 'ondrop' in div)) && 'FormData' in window && 'FileReader' in window;
}();

$('.dropbox').each(function() {
    var $form = $(this);
    var $input = $form.find('input[type="file"]');
    var droppedFiles = false;

    if (isAdvancedUpload) {
        $form.addClass('has-advanced-upload');

        $form.on('drag dragstart dragend dragover dragenter dragleave drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
            })
            .on('dragover dragenter', function() {
                $form.addClass('is-dragover');
            })
            .on('dragleave dragend drop', function() {
                $form.removeClass('is-dragover');
            })
            .on('drop', function(e) {
                droppedFiles = e.originalEvent.dataTransfer.files;

                if (droppedFiles) {
                    $input.prop("files", droppedFiles);
                }

                $form.trigger('submit');
            });
    }

    $input.on('change', function() {
        // console.log("pppppp",this.files);
        // if (this.files && this.files[0]) {
        //     var reader = new FileReader();
        //     reader.onload = function(e) {
        //         $('#dropbox-placeholder').attr('src', e.target.result);
        //     }
        //     reader.readAsDataURL(this.files[0]);
        // }
        // $form.trigger('submit');
    });
});



// ---- Crop UI variables and initial values ----
const imageContainer = document.getElementById('image-container');
const image = document.getElementById('image');
const cropArea = document.getElementById('crop-area');
const resizeHandle = document.getElementById('resize-handle');
const cropBtn = document.getElementById('cropBtn');
const canvas = document.getElementById('canvas');

let startX, startY, isDragging = false, isResizing = false;
let cropX = 50, cropY = 50, cropWidth = 100, cropHeight = 100;

// When a new image is loaded into #dropbox-placeholder, sync to crop UI (and show it!)
function showImageForCropping(src) {
    image.src = src;
    image.onload = () => {
        imageContainer.style.display = "inline-block";
        cropBtn.style.display = "inline-block";
        canvas.style.display = "none";

        cropWidth = Math.max(100, image.width/3);
        cropHeight = Math.max(100, image.height/3);
        cropX = (image.width - cropWidth) / 2;
        cropY = (image.height - cropHeight) / 2;

        cropArea.style.width = cropWidth + "px";
        cropArea.style.height = cropHeight + "px";
        cropArea.style.left = cropX + "px";
        cropArea.style.top = cropY + "px";
        cropArea.style.display = "block";
        cropArea.style.zIndex = 10;
    };
}

// ------------------ DRAG CROP AREA ------------------
cropArea.addEventListener('mousedown', (e) => {
    if (e.target === resizeHandle) return; // don't drag when resizing

    isDragging = true;
    startX = e.clientX - cropArea.offsetLeft;
    startY = e.clientY - cropArea.offsetTop;
    document.body.style.userSelect = "none";
});

document.addEventListener('mousemove', (e) => {
    // DRAG
    if (isDragging) {
        cropX = e.clientX - startX;
        cropY = e.clientY - startY;

        cropX = Math.max(0, Math.min(cropX, image.width - cropArea.offsetWidth));
        cropY = Math.max(0, Math.min(cropY, image.height - cropArea.offsetHeight));

        cropArea.style.left = cropX + "px";
        cropArea.style.top = cropY + "px";
    }

    // RESIZE
    if (isResizing) {
        cropWidth = e.clientX - cropArea.getBoundingClientRect().left;
        cropHeight = e.clientY - cropArea.getBoundingClientRect().top;

        // Keep within image
        cropWidth = Math.min(cropWidth, image.width - cropX);
        cropHeight = Math.min(cropHeight, image.height - cropY);

        // Minimum size
        cropWidth = Math.max(50, cropWidth);
        cropHeight = Math.max(50, cropHeight);

        cropArea.style.width = cropWidth + "px";
        cropArea.style.height = cropHeight + "px";
    }
});

document.addEventListener('mouseup', () => {
    isDragging = false;
    isResizing = false;
    document.body.style.userSelect = "";
});

// ------------------ START RESIZE ------------------
resizeHandle.addEventListener('mousedown', (e) => {
    e.stopPropagation();
    isResizing = true;
});

// ------------------ CROP BUTTON ------------------
cropBtn.addEventListener('click', () => {
    if (!image.src) return;

    const img = new Image();
    img.src = image.src;

    img.onload = () => {
        const scaleX = img.naturalWidth / image.width;
        const scaleY = img.naturalHeight / image.height;

        canvas.width = cropArea.offsetWidth * scaleX;
        canvas.height = cropArea.offsetHeight * scaleY;

        const ctx = canvas.getContext('2d');
        ctx.clearRect(0,0, canvas.width, canvas.height);

        ctx.drawImage(
            img,
            cropX * scaleX,
            cropY * scaleY,
            cropArea.offsetWidth * scaleX,
            cropArea.offsetHeight * scaleY,
            0, 0, canvas.width, canvas.height
        );

        canvas.style.display = "block";
        document.getElementById('cropPreview').style.display = "block";
        document.getElementById('saveBtn').style.display = "inline-block";
    };
});

// ---- Sync with jQuery image upload ----
$('#modal-media-upload').on('change', function() {
    if (this.files && this.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#dropbox-placeholder').attr('src', e.target.result);
            showImageForCropping(e.target.result);
        }
        reader.readAsDataURL(this.files[0]);
    }
});




// document.getElementById('modal-media-upload').addEventListener('change', function() {
//     if (this.files && this.files[0]) {
//         var reader = new FileReader();
//         reader.onload = function(e) {
//             // Change the placeholder (as in your code)
//             document.getElementById('dropbox-placeholder').src = e.target.result;
//             // Also update the cropping image
//             showImageForCropping(e.target.result);
//         }
//         reader.readAsDataURL(this.files[0]);
//     }
// });



document.getElementById('saveBtn').addEventListener('click', function() {
    canvas.toBlob(function(blob) {
        let formData = new FormData();
        formData.append('media[]', blob, 'cropped.png');
        // CSRF for Laravel
        formData.append('_token', '{{ csrf_token() }}');
        fetch('{{ route("media.upload") }}', {  // Use your normal upload route
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.redirected) {
                // If successful, server may redirect — reload or follow location
                window.location = response.url;
                return;
            }
            // Non-redirect response
            return response.json();
        })
        .then(data => {
            if (data && data.success) {
                alert(data.success);
            }
        })
        .catch(error => {
            alert("Upload failed: " + error);
        });
    }, 'image/png');
});



</script>
@endpushonce


