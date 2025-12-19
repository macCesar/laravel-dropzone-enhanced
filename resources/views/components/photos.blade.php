@props(['model', 'thumbnailDimensions' => '288x288', 'locale' => null])

@php
  // Filter photos by locale if specified
  if (config('dropzone.multilingual.enabled') && $locale !== null) {
    $photos = $model->photosByLocale($locale);
  } else {
    $photos = $model->photos;
  }

  $containerId = 'photos-container-' . ($locale ?? 'default');
@endphp

<div class="photos-container" data-model-id="{{ $model->id }}" data-model-type="{{ get_class($model) }}" data-locale="{{ $locale ?? '' }}" id="{{ $containerId }}">
  @if ($photos->count() > 0)
    <div class="photos-grid">
      @foreach ($photos as $photo)
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
  <style>
    .photos-container {
      margin-top: 20px;
    }

    .photos-grid {
      gap: 15px;
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }

    .photo-item {
      cursor: default;
      overflow: hidden;
      aspect-ratio: 1/1;
      position: relative;
      border-radius: 5px;
      transition: all 0.2s;
      background-color: #f8f9fa;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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
      gap: 5px;
      top: 5px;
      right: 5px;
      opacity: 0;
      z-index: 10;
      display: flex;
      position: absolute;
      flex-direction: column;
      transition: opacity 0.2s;
    }

    .photo-item:hover .photo-actions {
      opacity: 1;
    }

    .photo-action {
      color: #333;
      width: 28px;
      height: 28px;
      border: none;
      display: flex;
      cursor: pointer;
      border-radius: 50%;
      align-items: center;
      transition: all 0.2s;
      justify-content: center;
      background: rgba(255, 255, 255, 0.9);
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
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
      color: #888;
      padding: 30px;
      text-align: center;
      border-radius: 5px;
      background: #f9f9f9;
      border: 1px dashed #ddd;
    }

    .drag-handle {
      opacity: 0;
      right: 5px;
      z-index: 10;
      bottom: 5px;
      width: 28px;
      height: 28px;
      color: white;
      cursor: grab;
      display: flex;
      position: absolute;
      border-radius: 4px;
      align-items: center;
      justify-content: center;
      transition: opacity 0.2s;
      background: rgba(0, 0, 0, 0.5);
    }

    .photo-item:hover .drag-handle {
      opacity: 1;
    }

    .drag-handle:hover {
      transform: scale(1.1);
      background: rgba(0, 0, 0, 0.8);
    }

    .photo-item-ghost {
      opacity: 0.5;
    }
  </style>

  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Function to initialize the photo actions
      function initPhotoActions() {
        const container = document.getElementById('photos-container');

        if (!container) {
          console.error('Photos container not found');
          return;
        }

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
            showLightbox(photoUrl);
          });
        });

        // Initialize drag and drop for photo reordering
        const photosGrid = container.querySelector('.photos-grid');
        if (photosGrid) {
          try {
            const sortable = new Sortable(photosGrid, {
              animation: 150,
              ghostClass: 'photo-item-ghost',
              handle: '.drag-handle',
              onStart: function(evt) {},
              onEnd: function(evt) {
                updatePhotoOrder();
              }
            });

            // Save global reference for debugging
            window.photoSortable = sortable;
          } catch (error) {
            console.error('Error initializing Sortable:', error);
          }
        } else {
          console.warn('Photos grid element not found');
        }
      }

      // Function to show a simple lightbox
      function showLightbox(imageUrl) {
        // Create lightbox elements
        const lightbox = document.createElement('div');
        lightbox.className = 'photo-lightbox';
        lightbox.innerHTML = `
          <div class="photo-lightbox-backdrop"></div>
          <div class="photo-lightbox-content">
            <img src="${imageUrl}" alt="Lightbox image" />
            <button class="photo-lightbox-close">&times;</button>
          </div>
        `;

        // Add styles
        const style = document.createElement('style');
        style.textContent = `
          .photo-lightbox {
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9999;
            display: flex;
            position: fixed;
            align-items: center;
            justify-content: center;
          }
          .photo-lightbox-backdrop {
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            position: absolute;
            background: rgba(0, 0, 0, 0.8);
          }
          .photo-lightbox-content {
            z-index: 10000;
            max-width: 90%;
            max-height: 90%;
            position: relative;
          }
          .photo-lightbox-content img {
            display: block;
            max-width: 100%;
            max-height: 90vh;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.5);
          }
          .photo-lightbox-close {
            top: -20px;
            width: 40px;
            right: -20px;
            height: 40px;
            border: none;
            cursor: pointer;
            font-size: 24px;
            background: #fff;
            position: absolute;
            border-radius: 50%;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
          }
        `;

        document.head.appendChild(style);
        document.body.appendChild(lightbox);

        // Close lightbox when clicking backdrop or close button
        lightbox.querySelector('.photo-lightbox-backdrop').addEventListener('click', function() {
          document.body.removeChild(lightbox);
        });

        lightbox.querySelector('.photo-lightbox-close').addEventListener('click', function() {
          document.body.removeChild(lightbox);
        });
      }

      // Set a photo as the main photo
      function setMainPhoto(photoId) {
        fetch("{{ route('dropzone.setMain', ['id' => '__id__']) }}".replace('__id__', photoId), {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'X-CSRF-TOKEN': "{{ csrf_token() }}"
            }
          })
          .then(response => response.json())
          .then(data => {
            // If server returns success, update UI accordingly
            if (data.success) {
              // Check if this image was the main one and is being unmarked
              const photoItem = document.querySelector(`.photo-item[data-photo-id="${photoId}"]`);
              const wasMain = photoItem && photoItem.classList.contains('is-main');

              // Deactivate all images
              document.querySelectorAll('.photo-item').forEach(item => {
                item.classList.remove('is-main');
                item.querySelector('.photo-action-main').classList.remove('active');
              });

              // If not unmarking, set the new main
              if (!wasMain || (wasMain && data.is_main)) {
                if (photoItem) {
                  photoItem.classList.add('is-main');
                  photoItem.querySelector('.photo-action-main').classList.add('active');
                }
              }
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
                'Accept': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
              }
            })
            .then(response => {
              if (!response.ok) {
                if (response.status === 403) {
                  // For 403 Forbidden errors, we still want to parse the JSON to get the error message
                  return response.json().then(errorData => {
                    throw new Error(errorData.message || 'You do not have permission to delete this photo');
                  });
                }
                throw new Error('Error ' + response.status + ': ' + response.statusText);
              }
              return response.json();
            })
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
              alert(error.message || 'Error deleting photo. Please try again.');
            });
        }
      }

      // Update photo order
      function updatePhotoOrder() {
        const container = document.getElementById('photos-container');
        const photos = [];

        // Collect photo IDs and new order
        container.querySelectorAll('.photo-item').forEach((item, index) => {
          const order = index + 1;
          const id = item.dataset.photoId;

          photos.push({
            id: id,
            order: order
          });

          // Update data attribute
          item.dataset.sortOrder = order;
        });

        const requestBody = JSON.stringify({
          photos
        });

        // Send to server
        fetch("{{ route('dropzone.reorder') }}", {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            body: requestBody
          })
          .then(response => {
            if (!response.ok) {
              return response.text().then(text => {
                console.error('Error in response text:', text);
                throw new Error(`Server response error: ${response.status} ${response.statusText} - ${text}`);
              });
            }
            return response.json();
          })
          .then(data => {})
          .catch(error => {
            alert('Error updating order: ' + error.message);
          });
      }

      // Wait for SortableJS to load
      function checkSortableLoaded() {
        if (typeof Sortable !== 'undefined') {
          // Initialize on load
          initPhotoActions();
        } else {
          console.error('SortableJS failed to load');
          setTimeout(checkSortableLoaded, 500);
        }
      }

      checkSortableLoaded();

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
@endonce
