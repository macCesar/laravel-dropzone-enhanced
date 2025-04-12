@props(['object', 'thumbnailDimensions' => '288x288'])

<div class="photos-container" data-model-id="{{ $object->id }}" data-model-type="{{ get_class($object) }}" id="photos-container">
  @if ($object->photos->count() > 0)
    <div class="photos-grid">
      @foreach ($object->photos as $photo)
        <div class="photo-item {{ $photo->is_main ? 'is-main' : '' }}" data-photo-id="{{ $photo->id }}" data-sort-order="{{ $photo->sort_order }}">
          <div class="photo-actions">
            <button class="photo-action photo-action-view" data-photo-id="{{ $photo->id }}" data-photo-url="{{ $photo->getUrl() }}" title="{{ __('dropzone-enhanced::messages.photos.view') }}" type="button">
              <svg fill="none" height="16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="16" xmlns="http://www.w3.org/2000/svg">
                <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7" />
              </svg>
            </button>

            <button class="photo-action photo-action-main {{ $photo->is_main ? 'active' : '' }}" data-photo-id="{{ $photo->id }}" title="{{ __('dropzone-enhanced::messages.photos.set_as_main') }}" type="button">
              <svg fill="none" height="16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="16" xmlns="http://www.w3.org/2000/svg">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
              </svg>
            </button>

            <button class="photo-action photo-action-delete" data-photo-id="{{ $photo->id }}" title="{{ __('dropzone-enhanced::messages.photos.delete') }}" type="button">
              <svg fill="none" height="16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="16" xmlns="http://www.w3.org/2000/svg">
                <polyline points="3 6 5 6 21 6" />
                <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" />
                <line x1="10" x2="10" y1="11" y2="17" />
                <line x1="14" x2="14" y1="11" y2="17" />
              </svg>
            </button>
          </div>

          <div class="drag-handle" title="{{ __('dropzone-enhanced::messages.photos.drag_to_reorder') }}">
            <svg fill="none" height="16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="16" xmlns="http://www.w3.org/2000/svg">
              <line x1="8" x2="21" y1="6" y2="6"></line>
              <line x1="8" x2="21" y1="12" y2="12"></line>
              <line x1="8" x2="21" y1="18" y2="18"></line>
              <line x1="3" x2="3.01" y1="6" y2="6"></line>
              <line x1="3" x2="3.01" y1="12" y2="12"></line>
              <line x1="3" x2="3.01" y1="18" y2="18"></line>
            </svg>
          </div>

          <img alt="{{ $photo->original_filename }}" class="photo-thumb" loading="lazy" src="{{ $photo->getThumbnailUrl($thumbnailDimensions) }}" />
        </div>
      @endforeach
    </div>
  @else
    <div class="photos-empty">
      {{ __('dropzone-enhanced::messages.photos.no_photos') }}
    </div>
  @endif
</div>

@once
  @push('styles')
    <style>
      .photos-container {
        margin-top: 20px;
      }

      .photos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
      }

      .photo-item {
        position: relative;
        border-radius: 5px;
        overflow: hidden;
        aspect-ratio: 1/1;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        cursor: grab;
        transition: all 0.2s;
        background-color: #f8f9fa;
      }

      .photo-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
      }

      .photo-item.is-main {
        border: 2px solid #0d6efd;
      }

      .photo-thumb {
        width: 100%;
        height: 100%;
        object-fit: contain;
      }

      .photo-actions {
        position: absolute;
        top: 5px;
        right: 5px;
        display: flex;
        flex-direction: column;
        gap: 5px;
        opacity: 0;
        transition: opacity 0.2s;
        z-index: 10;
      }

      .photo-item:hover .photo-actions {
        opacity: 1;
      }

      .photo-action {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        border: none;
        background: rgba(255, 255, 255, 0.9);
        color: #333;
        cursor: pointer;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        transition: all 0.2s;
      }

      .photo-action:hover {
        background: white;
        transform: scale(1.1);
      }

      .photo-action.photo-action-main.active {
        background: #0d6efd;
        color: white;
      }

      .photo-action.photo-action-delete:hover {
        background: #dc3545;
        color: white;
      }

      .photos-empty {
        text-align: center;
        padding: 30px;
        color: #888;
        background: #f9f9f9;
        border-radius: 5px;
        border: 1px dashed #ddd;
      }

      .drag-handle {
        position: absolute;
        bottom: 5px;
        right: 5px;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        background: rgba(0, 0, 0, 0.5);
        border-radius: 4px;
        opacity: 0;
        transition: opacity 0.2s;
        z-index: 10;
        cursor: grab;
      }

      .photo-item:hover .drag-handle {
        opacity: 1;
      }

      .photo-item-ghost {
        opacity: 0.5;
      }
    </style>
  @endpush

  @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // Function to initialize the photo actions
        function initPhotoActions() {
          const container = document.getElementById('photos-container');

          if (!container) return;

          // Set as main
          container.querySelectorAll('.photo-action-main').forEach(button => {
            button.addEventListener('click', function() {
              const photoId = button.dataset.photoId;
              setMainPhoto(photoId);
            });
          });

          // Delete photo
          container.querySelectorAll('.photo-action-delete').forEach(button => {
            button.addEventListener('click', function() {
              const photoId = button.dataset.photoId;
              deletePhoto(photoId);
            });
          });

          // View photo in lightbox
          container.querySelectorAll('.photo-action-view').forEach(button => {
            button.addEventListener('click', function() {
              const photoUrl = button.dataset.photoUrl;
              const lightbox = document.getElementById('lightbox');

              if (lightbox) {
                const lightboxImage = document.getElementById('lightbox-image');
                lightboxImage.src = photoUrl;
                lightbox.classList.add('show');
              }
            });
          });

          // Initialize drag and drop reordering if the Sortable library is available
          if (typeof Sortable !== 'undefined') {
            const photosGrid = container.querySelector('.photos-grid');
            if (photosGrid) {
              new Sortable(photosGrid, {
                animation: 150,
                ghostClass: 'photo-item-ghost',
                onEnd: function() {
                  updatePhotoOrder();
                }
              });
            }
          }
        }

        // Set a photo as main
        function setMainPhoto(photoId) {
          fetch("{{ route('dropzone.setMain', ['id' => '__id__']) }}".replace('__id__', photoId), {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
              }
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                // First, remove 'is-main' and 'active' classes from all photos
                document.querySelectorAll('.photo-item').forEach(item => {
                  item.classList.remove('is-main');
                  item.querySelector('.photo-action-main').classList.remove('active');
                });

                const photoItem = document.querySelector(`.photo-item[data-photo-id="${photoId}"]`);

                // Check if the clicked photo is the same as the current main photo
                // In that case, we are toggling OFF the main status, so we don't need to add the classes back
                // The backend has already handled the toggle, so we only update the UI accordingly

                // We need to verify if the server actually set this photo as main or unset it
                // We'll check by making an additional request to get the current state
                fetch("{{ route('dropzone.checkIsMain', ['id' => '__id__']) }}".replace('__id__', photoId), {
                    method: 'GET',
                    headers: {
                      'Accept': 'application/json'
                    }
                  })
                  .then(response => response.json())
                  .then(checkData => {
                    if (checkData.is_main) {
                      // The photo is main, so add the classes
                      if (photoItem) {
                        photoItem.classList.add('is-main');
                        photoItem.querySelector('.photo-action-main').classList.add('active');
                      }
                    }
                    // If not main, we already removed all the classes, so do nothing
                  })
                  .catch(error => {
                    console.error('Error checking main photo status:', error);
                  });
              }
            })
            .catch(error => {
              console.error('Error setting main photo:', error);
            });
        }

        // Delete a photo
        function deletePhoto(photoId) {
          if (confirm("{{ __('dropzone-enhanced::messages.photos.confirm_delete') }}")) {
            fetch("{{ route('dropzone.destroy', ['id' => '__id__']) }}".replace('__id__', photoId), {
                method: 'DELETE',
                headers: {
                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                  'Accept': 'application/json'
                }
              })
              .then(response => response.json())
              .then(data => {
                if (data.success) {
                  // Remove the photo from the DOM
                  const photoItem = document.querySelector(`.photo-item[data-photo-id="${photoId}"]`);
                  if (photoItem) {
                    photoItem.remove();
                  }

                  // Check if there are no photos left
                  const photoItems = document.querySelectorAll('.photo-item');
                  if (photoItems.length === 0) {
                    const photosGrid = document.querySelector('.photos-grid');
                    if (photosGrid) {
                      photosGrid.innerHTML = `<div class="photos-empty">{{ __('dropzone-enhanced::messages.photos.no_photos') }}</div>`;
                    }
                  }
                }
              })
              .catch(error => {
                console.error('Error deleting photo:', error);
              });
          }
        }

        // Update photo order
        function updatePhotoOrder() {
          const container = document.getElementById('photos-container');
          const photos = [];

          // Collect photo IDs and new order
          container.querySelectorAll('.photo-item').forEach((item, index) => {
            photos.push({
              id: item.dataset.photoId,
              order: index + 1
            });

            // Update data attribute
            item.dataset.sortOrder = index + 1;
          });

          // Send to server
          fetch("{{ route('dropzone.reorder') }}", {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                photos
              })
            })
            .catch(error => {
              console.error('Error updating photo order:', error);
            });
        }

        // Initialize on load
        initPhotoActions();

        // Listen for photo updates
        document.addEventListener('photos-updated', function() {
          // Refresh the photos container
          fetch(window.location.href)
            .then(response => response.text())
            .then(html => {
              const parser = new DOMParser();
              const doc = parser.parseFromString(html, 'text/html');
              const newPhotosContainer = doc.getElementById('photos-container');

              if (newPhotosContainer) {
                const currentContainer = document.getElementById('photos-container');
                currentContainer.outerHTML = newPhotosContainer.outerHTML;

                // Reinitialize actions
                initPhotoActions();
              }
            })
            .catch(error => {
              console.error('Error refreshing photos:', error);
            });
        });
      });
    </script>
  @endpush
@endonce
