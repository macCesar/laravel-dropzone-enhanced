@props([
  'model',
  'directory',
  'reloadOnSuccess' => false,
  'dimensions' => config('dropzone.images.default_dimensions', '1920x1080'),
  'preResize' => config('dropzone.images.pre_resize', true),
  'maxFiles' => config('dropzone.images.max_files', 10),
  'maxFilesize' => config('dropzone.images.max_filesize', 10000) / 1000,
  'keepOriginalName' => false,
  'locale' => null,
])

@php
  $containerId = 'dropzone-container-' . ($locale ?? 'default');
  $uploadId = 'dropzone-upload-' . ($locale ?? 'default');
@endphp

<div class="dropzone-container" data-dimensions="{{ $dimensions }}" data-directory="{{ $directory }}" data-model-id="{{ $model->id }}" data-model-type="{{ get_class($model) }}" data-pre-resize="{{ $preResize ? 'true' : 'false' }}" data-keep-original-name="{{ $keepOriginalName ? 'true' : 'false' }}" data-locale="{{ $locale ?? '' }}" data-max-files="{{ $maxFiles }}" data-max-filesize="{{ $maxFilesize }}" data-reload-on-success="{{ $reloadOnSuccess ? 'true' : 'false' }}" id="{{ $containerId }}">
  <div class="dropzone" id="{{ $uploadId }}">
    <div class="dz-message">
      {{ __('dropzone-enhanced::messages.dropzone.message') }}
      <div class="dz-instructions">
        {{ __('dropzone-enhanced::messages.dropzone.instructions') }}
      </div>
      <div class="dz-preview-container"></div>
    </div>
  </div>
</div>

@once
  <link href="{{ asset('vendor/dropzone-enhanced/dropzone.css') }}" rel="stylesheet" type="text/css" />

  <style>
    .dropzone {
      border-radius: 5px;
      border: 2px dashed #ccc;
      background: #f9f9f9;
      padding: 30px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s ease;
      min-height: 180px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }

    .dropzone:hover {
      border-color: #0d6efd;
      background: #f5f5f5;
    }

    .dropzone .dz-message {
      margin: 0;
      font-size: 1.2rem;
      color: #555;
    }

    .dropzone .dz-instructions {
      margin-top: 10px;
      font-size: 0.9rem;
      color: #888;
    }
  </style>

  <script src="{{ asset('vendor/dropzone-enhanced/dropzone-min.js') }}"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof Dropzone === 'undefined') {
        return;
      }

      const parseDimensions = function(dimensions) {
        const fallback = { width: 1920, height: 1080 };
        if (!dimensions) {
          return fallback;
        }
        const parts = dimensions.split('x').map(value => parseInt(value, 10));
        if (parts.length !== 2 || Number.isNaN(parts[0]) || Number.isNaN(parts[1])) {
          return fallback;
        }
        return { width: parts[0], height: parts[1] };
      };

      document.querySelectorAll('.dropzone-container').forEach(function(container) {
        const dropzoneElement = container.querySelector('.dropzone');
        if (!dropzoneElement || dropzoneElement.dataset.dzInitialized === 'true') {
          return;
        }
        dropzoneElement.dataset.dzInitialized = 'true';

        const maxFiles = parseInt(container.dataset.maxFiles || '10', 10);
        const maxFilesize = parseFloat(container.dataset.maxFilesize || '10');
        const reloadOnSuccess = container.dataset.reloadOnSuccess === 'true';
        const dimensions = parseDimensions(container.dataset.dimensions);
        const preResize = container.dataset.preResize === 'true';

        const options = {
          url: "{{ route('dropzone.upload') }}",
          paramName: "file",
          maxFiles: Number.isNaN(maxFiles) ? 10 : maxFiles,
          maxFilesize: Number.isNaN(maxFilesize) ? 10 : maxFilesize, // MB
          acceptedFiles: "image/*",
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          thumbnailWidth: 576,
          thumbnailHeight: 576,
          thumbnailMethod: "crop",
          createImageThumbnails: true,
          init: function() {
            const dropzone = this;

            // Add additional data
            this.on("sending", function(file, xhr, formData) {
              // Convert the ID to integer to ensure it is an integer and not a string
              formData.append("model_id", parseInt(container.dataset.modelId, 10));
              formData.append("model_type", container.dataset.modelType);
              formData.append("directory", container.dataset.directory);
              // Ensure dimensions always has a value
              formData.append("dimensions", container.dataset.dimensions || "1920x1080");
              formData.append("keep_original_name", container.dataset.keepOriginalName === "true" ? "1" : "0");

              // Add locale if present
              const locale = container.dataset.locale;
              if (locale && locale !== '') {
                formData.append("locale", locale);
              }
            });

            // Handle success
            this.on("success", function(file, response) {
              // After 2.5 seconds, fade out and remove the file
              setTimeout(() => {
                dropzone.removeFile(file);
              }, 2500);

              // If all files in the queue are finished, dispatch an event
              if (dropzone.getUploadingFiles().length === 0 && dropzone.getQueuedFiles().length === 0) {
                // Dispatch a custom event to notify other components (like the photo gallery)
                document.dispatchEvent(new CustomEvent('dropzone-uploads-finished', {
                  detail: {
                    modelId: container.dataset.modelId
                  }
                }));
              }
            });

            // Handle queue completion
            this.on("queuecomplete", function() {
              // Check if the reloadOnSuccess prop is true and if there were no errors
              if (reloadOnSuccess && this.getRejectedFiles().length === 0) {
                // Wait a moment for the last success animation to be seen, then reload
                setTimeout(() => {
                  window.location.reload();
                }, 1000); // 1 second delay before reload
              }
            });

            // Handle errors
            this.on("error", function(file, errorMessage) {
              // Display error - with improved handling for objects
              let errorText = '';

              // If it is an object (JSON response from server)
              if (typeof errorMessage === 'object') {
                console.error('Error de Dropzone:', errorMessage);

                // If it has a specific message in Laravel format
                if (errorMessage.errors && Object.keys(errorMessage.errors).length > 0) {
                  // Extract the first error message
                  const firstErrorField = Object.keys(errorMessage.errors)[0];
                  errorText = errorMessage.errors[firstErrorField][0];
                }
                // If it has a general message
                else if (errorMessage.message) {
                  errorText = errorMessage.message;
                }
                // Generic fallback
                else {
                  errorText = 'Error al subir la imagen. Inténtalo de nuevo.';
                }
              } else {
                // If it is a simple string
                errorText = errorMessage;
              }

              // Find the error message element in the preview and set its text
              const errorNode = file.previewElement.querySelector("[data-dz-errormessage]");
              if (errorNode) {
                errorNode.textContent = errorText;
              }
            });
          },
          dictDefaultMessage: "{{ __('dropzone-enhanced::messages.dropzone.message') }}",
          dictFallbackMessage: "{{ __('dropzone-enhanced::messages.dropzone.fallback') }}",
          dictFallbackText: "{{ __('dropzone-enhanced::messages.dropzone.fallback_text') }}",
          dictFileTooBig: "{{ __('dropzone-enhanced::messages.dropzone.file_too_big') }}",
          dictInvalidFileType: "{{ __('dropzone-enhanced::messages.dropzone.invalid_file_type') }}",
          dictResponseError: "{{ __('dropzone-enhanced::messages.dropzone.response_error') }}",
          dictCancelUpload: "{{ __('dropzone-enhanced::messages.dropzone.cancel_upload') }}",
          dictCancelUploadConfirmation: "{{ __('dropzone-enhanced::messages.dropzone.cancel_confirmation') }}",
          dictRemoveFile: "{{ __('dropzone-enhanced::messages.dropzone.remove_file') }}",
          dictMaxFilesExceeded: "{{ __('dropzone-enhanced::messages.dropzone.max_files_exceeded') }}"
        };

        if (preResize) {
          options.transformFile = function(file, done) {
            const reader = new FileReader();
            reader.onload = function(e) {
              const img = new Image();
              img.onload = function() {
                const ratio = Math.min(dimensions.width / img.naturalWidth, dimensions.height / img.naturalHeight);
                if (ratio >= 1) return done(file);

                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                canvas.width = Math.round(img.naturalWidth * ratio);
                canvas.height = Math.round(img.naturalHeight * ratio);
                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                const outputType = file.type === 'image/png' ? 'image/png' : file.type;
                const quality = file.type === 'image/png' ? undefined : {{ config('dropzone.images.quality', 100) / 100 }};
                canvas.toBlob(blob => done(new File([blob], file.name, { type: outputType })), outputType, quality);
              };
              img.src = e.target.result;
            };
            reader.readAsDataURL(file);
          };
        }

        const myDropzone = new Dropzone(dropzoneElement, options);
      });
    });
  </script>
@endonce
