@props(['object'])

<div class="lightbox" id="lightbox">
  <div class="lightbox-content">
    <img alt="{{ __('dropzone-enhanced::messages.lightbox.image') }}" id="lightbox-image" src="" />
    <button class="lightbox-close" id="lightbox-close" title="{{ __('dropzone-enhanced::messages.lightbox.close') }}" type="button">
      <svg fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
        <line x1="18" x2="6" y1="6" y2="18" />
        <line x1="6" x2="18" y1="6" y2="18" />
      </svg>
    </button>
  </div>
</div>

@once
  @push('styles')
    <style>
      .lightbox {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.9);
        z-index: 9999;
        justify-content: center;
        align-items: center;
      }

      .lightbox.show {
        display: flex;
      }

      .lightbox-content {
        position: relative;
        max-width: 90%;
        max-height: 90%;
      }

      #lightbox-image {
        max-width: 100%;
        max-height: 90vh;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
      }

      .lightbox-close {
        position: absolute;
        top: -40px;
        right: 0;
        background: transparent;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
        outline: none;
        transition: transform 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .lightbox-close:hover {
        transform: scale(1.1);
      }
    </style>
  @endpush

  @push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const lightbox = document.getElementById('lightbox');
        const lightboxClose = document.getElementById('lightbox-close');

        // Close lightbox when clicking the close button
        if (lightboxClose) {
          lightboxClose.addEventListener('click', function() {
            lightbox.classList.remove('show');
          });
        }

        // Close lightbox when clicking outside the image
        if (lightbox) {
          lightbox.addEventListener('click', function(e) {
            if (e.target === lightbox) {
              lightbox.classList.remove('show');
            }
          });
        }

        // Close lightbox when pressing ESC
        document.addEventListener('keydown', function(e) {
          if (e.key === 'Escape' && lightbox.classList.contains('show')) {
            lightbox.classList.remove('show');
          }
        });
      });
    </script>
  @endpush
@endonce
