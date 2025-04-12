@props(['object', 'directory', 'dimensions' => null, 'preResize' => true, 'maxFiles' => 10, 'maxFilesize' => 5])

<div class="dropzone-container" data-dimensions="{{ $dimensions }}" data-directory="{{ $directory }}" data-model-id="{{ $object->id }}" data-model-type="{{ get_class($object) }}" data-pre-resize="{{ $preResize ? 'true' : 'false' }}" id="dropzone-container">
  <div class="dropzone" id="dropzone-upload">
    <div class="dz-message">
      {{ __('dropzone-enhanced::messages.dropzone.message') }}
      <div class="dz-instructions">
        {{ __('dropzone-enhanced::messages.dropzone.instructions') }}
      </div>
      <div class="dz-preview-container"></div>
    </div>
  </div>

  <div class="upload-progress-container" id="upload-progress">
    <div class="upload-progress">
      <div class="upload-progress-bar"></div>
    </div>
    <div class="upload-progress-text">
      <span class="upload-progress-percentage">0%</span>
      <span class="upload-progress-filename"></span>
    </div>
  </div>
</div>

@once
  @push('styles')
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

      .upload-progress-container {
        display: none;
        margin-top: 15px;
      }

      .upload-progress {
        height: 6px;
        background-color: #f5f5f5;
        border-radius: 3px;
        margin-bottom: 5px;
        overflow: hidden;
      }

      .upload-progress-bar {
        height: 100%;
        background-color: #0d6efd;
        width: 0;
        transition: width 0.3s ease;
      }

      .upload-progress-text {
        display: flex;
        justify-content: space-between;
        font-size: 0.8rem;
        color: #555;
      }
    </style>
  @endpush

  @push('scripts')
    <script src="{{ asset('vendor/dropzone-enhanced/dropzone-min.js') }}"></script>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // Inicialización directa de Dropzone en lugar de usar discover
        const dropzoneElement = document.getElementById('dropzone-upload');
        if (dropzoneElement) {
          const myDropzone = new Dropzone('#dropzone-upload', {
            url: "{{ route('dropzone.upload') }}",
            paramName: "file",
            maxFiles: {{ $maxFiles }},
            maxFilesize: {{ $maxFilesize }}, // MB
            acceptedFiles: "image/*",
            headers: {
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            init: function() {
              const dropzone = this;
              const container = document.getElementById('dropzone-container');
              const progressContainer = document.getElementById('upload-progress');
              const progressBar = document.querySelector('.upload-progress-bar');
              const progressPercentage = document.querySelector('.upload-progress-percentage');
              const progressFilename = document.querySelector('.upload-progress-filename');

              // Add additional data
              this.on("sending", function(file, xhr, formData) {
                formData.append("model_id", container.dataset.modelId);
                formData.append("model_type", container.dataset.modelType);
                formData.append("directory", container.dataset.directory);
                formData.append("dimensions", container.dataset.dimensions);
                formData.append("pre_resize", container.dataset.preResize);

                // Show progress
                progressContainer.style.display = 'block';
                progressFilename.textContent = file.name;
              });

              // Update progress bar
              this.on("uploadprogress", function(file, progress) {
                progressBar.style.width = progress + '%';
                progressPercentage.textContent = Math.round(progress) + '%';
              });

              // Handle success
              this.on("success", function(file, response) {
                // Hide progress after a delay
                setTimeout(function() {
                  progressContainer.style.display = 'none';
                  progressBar.style.width = '0%';
                  progressPercentage.textContent = '0%';
                }, 1000);

                // En lugar de disparar un evento, recargar la página después de completar todas las subidas
                if (dropzone.getActiveFiles().length === 0) {
                  // Solo recarga si no hay más archivos en cola
                  setTimeout(function() {
                    window.location.reload();
                  }, 1500);
                }

                // Remove the file from the dropzone
                dropzone.removeFile(file);
              });

              // Handle errors
              this.on("error", function(file, errorMessage) {
                // Hide progress
                progressContainer.style.display = 'none';

                // Display error
                alert(errorMessage);

                // Remove the file from the dropzone
                dropzone.removeFile(file);
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
          });
        }
      });
    </script>
  @endpush
@endonce
