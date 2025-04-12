@props(['object', 'directory', 'dimensions' => config('dropzone.images.default_dimensions', '1920x1080'), 'preResize' => config('dropzone.images.pre_resize', true), 'maxFiles' => config('dropzone.images.max_files', 10), 'maxFilesize' => config('dropzone.images.max_filesize', 10000) / 1000])

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
            // Configuración de redimensionamiento simplificada
            resizeWidth: {{ $dimensions ? explode('x', $dimensions)[0] : 1920 }},
            resizeHeight: {{ $dimensions ? explode('x', $dimensions)[1] : 1080 }},
            resizeMethod: "contain",
            resizeQuality: {{ config('dropzone.images.quality', 90) / 100 }},
            createImageThumbnails: true,
            init: function() {
              const dropzone = this;
              const container = document.getElementById('dropzone-container');

              // Add additional data
              this.on("sending", function(file, xhr, formData) {
                // Convertir el ID a número entero para asegurar que sea un integer y no un string
                formData.append("model_id", parseInt(container.dataset.modelId));
                formData.append("model_type", container.dataset.modelType);
                formData.append("directory", container.dataset.directory);
                // Asegurar que dimensions siempre tenga valor
                formData.append("dimensions", container.dataset.dimensions || "1920x1080");

                // Imprimir los datos que se están enviando en consola para diagnóstico
                console.log("Enviando datos:", {
                  "model_id": parseInt(container.dataset.modelId),
                  "model_type": container.dataset.modelType,
                  "directory": container.dataset.directory,
                  "dimensions": container.dataset.dimensions || "1920x1080"
                });
              });

              // Handle success
              this.on("success", function(file, response) {
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
                // Display error - con manejo mejorado para objetos
                let errorText = '';

                // Si es un objeto (respuesta JSON del servidor)
                if (typeof errorMessage === 'object') {
                  console.error('Error de Dropzone:', errorMessage);

                  // Si tiene mensaje específico en formato Laravel
                  if (errorMessage.errors && Object.keys(errorMessage.errors).length > 0) {
                    // Extraer el primer mensaje de error
                    const firstErrorField = Object.keys(errorMessage.errors)[0];
                    errorText = errorMessage.errors[firstErrorField][0];
                  }
                  // Si tiene un mensaje general
                  else if (errorMessage.message) {
                    errorText = errorMessage.message;
                  }
                  // Fallback genérico
                  else {
                    errorText = 'Error al subir la imagen. Inténtalo de nuevo.';
                  }
                } else {
                  // Si es un string simple
                  errorText = errorMessage;
                }

                alert(errorText);

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
