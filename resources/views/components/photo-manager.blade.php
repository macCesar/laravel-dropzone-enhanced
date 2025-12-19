@props(['model', 'directory', 'locales' => [], 'defaultLocale' => null, 'thumbnailDimensions' => '288x288', 'previewLimit' => 8])

@php
  $managerId = 'photo-manager-' . $model->id . '-' . uniqid();

  $normalizedLocales = collect($locales)->map(function ($locale) {
      $key = $locale['key'] ?? null;
      $keyForData = $key === null ? '' : (string) $key;
      $label = $locale['label'] ?? ($keyForData !== '' ? $keyForData : __('dropzone-enhanced::messages.photo_manager.generic'));
      $color = $locale['color'] ?? ($keyForData === 'es' ? 'blue' : ($keyForData === 'en' ? 'purple' : 'gray'));
      $badge = $locale['badge'] ?? strtoupper($keyForData !== '' ? $keyForData : 'GEN');

      return [
          'key' => $key,
          'key_for_data' => $keyForData,
          'label' => $label,
          'icon' => $locale['icon'] ?? null,
          'color' => $color,
          'badge' => $badge,
      ];
  });

  $defaultLocaleKey = $defaultLocale !== null ? (string) $defaultLocale : 'all';

  $photosGrouped = $model->photosGroupedByLocale();

  $photosForLocale = function ($localeKey) use ($photosGrouped) {
      $groupKey = $localeKey === null ? '' : (string) $localeKey;
      return $photosGrouped->get($groupKey, collect());
  };

  $localesForJs = $normalizedLocales->mapWithKeys(function ($locale) {
      return [
          $locale['key_for_data'] => [
              'label' => $locale['label'],
              'color' => $locale['color'],
              'badge' => $locale['badge'],
              'icon' => $locale['icon'],
          ],
      ];
  });
@endphp

<div class="dz-photo-manager" data-csrf="{{ csrf_token() }}" data-default-locale="{{ $defaultLocaleKey }}" data-locales='@json($localesForJs)' data-manager-id="{{ $managerId }}" id="{{ $managerId }}">
  <div class="dz-zones" role="list">
    @foreach ($normalizedLocales as $locale)
      @php
        $localePhotos = $photosForLocale($locale['key']);
      @endphp
      <div class="dz-zone" data-locale="{{ $locale['key_for_data'] }}" role="listitem">
        <div class="dz-zone-header">
          <span class="dz-zone-label">{{ $locale['label'] }}</span>
          <span class="dz-count-pill" data-dz-count="{{ $locale['key_for_data'] }}">{{ $localePhotos->count() }}</span>
        </div>

        <div class="dz-zone-body">
          <x-dropzone-enhanced::area :directory="$directory" :locale="$locale['key']" :model="$model" />

        </div>
      </div>
    @endforeach
  </div>

  <div class="dz-gallery">
    <div class="dz-gallery-header">
      <div class="dz-gallery-title">{{ __('dropzone-enhanced::messages.photo_manager.manage_photos') }}</div>
      <div aria-label="{{ __('dropzone-enhanced::messages.photo_manager.filters') }}" class="dz-filter-pills" role="tablist">
        <button class="dz-filter-pill is-active" data-dz-filter="all" type="button">
          {{ __('dropzone-enhanced::messages.photo_manager.filter_all') }}
          <span class="dz-filter-count" data-dz-count="all">{{ $model->photos->count() }}</span>
        </button>
        @foreach ($normalizedLocales as $locale)
          @php
            $localePhotos = $photosForLocale($locale['key']);
          @endphp
          <button class="dz-filter-pill" data-dz-filter="{{ $locale['key_for_data'] }}" type="button">
            <span>{{ $locale['label'] }}</span>
            <span class="dz-filter-count" data-dz-count="{{ $locale['key_for_data'] }}">{{ $localePhotos->count() }}</span>
          </button>
        @endforeach
      </div>
    </div>

    <div class="dz-gallery-tip" data-dz-tip>
      <div class="dz-tip-title">{{ __('dropzone-enhanced::messages.photo_manager.tip_title') }}</div>
      <div class="dz-tip-body">{{ __('dropzone-enhanced::messages.photo_manager.tip_body') }}</div>
    </div>

    <div class="dz-gallery-sections">
      @foreach ($normalizedLocales as $locale)
        @php
          $localePhotos = $photosForLocale($locale['key']);
        @endphp
        <section class="dz-gallery-section" data-dz-section="{{ $locale['key_for_data'] }}">
          <div class="dz-gallery-section-header">
            <span class="dz-section-label">{{ $locale['label'] }}</span>
            <span class="dz-count-pill" data-dz-count="{{ $locale['key_for_data'] }}">{{ $localePhotos->count() }}</span>
          </div>

          <div class="dz-photo-area">
            <div class="dz-photo-grid" data-empty-message="{{ __('dropzone-enhanced::messages.photo_manager.no_photos_locale') }}" data-empty="{{ $localePhotos->count() === 0 ? 'true' : 'false' }}" data-locale="{{ $locale['key_for_data'] }}">
              @foreach ($localePhotos as $photo)
                <div class="dz-photo-item dz-locale-{{ $locale['color'] }} {{ $photo->is_main ? 'is-main' : '' }}" data-locale="{{ $locale['key_for_data'] }}" data-photo-id="{{ $photo->id }}" data-sort-order="{{ $photo->sort_order }}">
                  <div class="dz-photo-actions">
                    <button class="dz-photo-action dz-photo-action-view" data-photo-id="{{ $photo->id }}" data-photo-url="{{ $photo->getUrl() }}" title="{{ __('dropzone-enhanced::messages.photos.view') }}" type="button">
                      <svg fill="none" height="16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="16" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7" />
                      </svg>
                    </button>

                    <button class="dz-photo-action dz-photo-action-main {{ $photo->is_main ? 'active' : '' }}" data-photo-id="{{ $photo->id }}" title="{{ __('dropzone-enhanced::messages.photos.set_as_main') }}" type="button">
                      <svg fill="none" height="16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="16" xmlns="http://www.w3.org/2000/svg">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                      </svg>
                    </button>

                    <button class="dz-photo-action dz-photo-action-delete" data-photo-id="{{ $photo->id }}" title="{{ __('dropzone-enhanced::messages.photos.delete') }}" type="button">
                      <svg fill="none" height="16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="16" xmlns="http://www.w3.org/2000/svg">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" />
                        <line x1="10" x2="10" y1="11" y2="17" />
                        <line x1="14" x2="14" y1="11" y2="17" />
                      </svg>
                    </button>
                  </div>

                  <div class="dz-drag-handle" title="{{ __('dropzone-enhanced::messages.photos.drag_to_reorder') }}">
                    <svg fill="none" height="16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="16" xmlns="http://www.w3.org/2000/svg">
                      <line x1="8" x2="21" y1="6" y2="6"></line>
                      <line x1="8" x2="21" y1="12" y2="12"></line>
                      <line x1="8" x2="21" y1="18" y2="18"></line>
                      <line x1="3" x2="3.01" y1="6" y2="6"></line>
                      <line x1="3" x2="3.01" y1="12" y2="12"></line>
                      <line x1="3" x2="3.01" y1="18" y2="18"></line>
                    </svg>
                  </div>

                  <div class="dz-photo-badge">{{ $locale['badge'] }}</div>

                  <img alt="{{ $photo->original_filename }}" class="dz-photo-thumb" loading="lazy" src="{{ $photo->getThumbnailUrl($thumbnailDimensions) }}" />
                </div>
              @endforeach
            </div>
          </div>
        </section>
      @endforeach
    </div>
  </div>
</div>

@once
  <style>
    .dz-photo-manager {
      gap: 28px;
      display: flex;
      flex-direction: column;
    }

    .dz-zones {
      gap: 16px;
      display: flex;
    }

    .dz-zone {
      flex: 1;
      min-width: 0;
      padding: 16px;
      border-radius: 10px;
      background: #f8fafc;
      border: 1px dashed #d1d5db;
      transition: flex 0.5s ease, background 0.3s ease, border-color 0.3s ease;
    }

    .dz-zone .dropzone .dz-message {
      opacity: 1;
      transform: translateY(0);
      transition-property: opacity, transform;
      transition-duration: 0.12s;
      transition-timing-function: ease;
      transition-delay: 0.35s;
    }

    .dz-zone.is-collapsed .dropzone .dz-message {
      opacity: 0;
      transform: translateY(-6px);
      pointer-events: none;
      transition-delay: 0s;
    }

    .dz-zone.is-active {
      flex: 4;
      background: #eff6ff;
      border-color: #3b82f6;
    }

    .dz-zone.is-collapsed {
      flex: 1;
    }

    .dz-zone-header {
      gap: 10px;
      display: flex;
      align-items: center;
      font-weight: 600;
      color: #1f2937;
    }

    .dz-count-pill {
      margin-left: auto;
      font-size: 12px;
      padding: 2px 10px;
      border-radius: 999px;
      background: #eef2f7;
      color: #374151;
      font-weight: 600;
    }

    .dz-gallery-header {
      gap: 12px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
    }

    .dz-gallery-title {
      font-weight: 700;
      color: #111827;
      letter-spacing: -0.01em;
    }

    .dz-filter-pills {
      gap: 8px;
      display: flex;
      flex-wrap: wrap;
    }

    .dz-filter-pill {
      gap: 6px;
      border: none;
      display: inline-flex;
      align-items: center;
      border-radius: 999px;
      padding: 6px 12px;
      background: #f3f4f6;
      color: #374151;
      font-size: 13px;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .dz-filter-pill.is-active {
      background: #2563eb;
      color: #fff;
    }

    .dz-filter-count {
      font-size: 12px;
      padding: 2px 8px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.6);
      color: inherit;
    }

    .dz-filter-pill.is-active .dz-filter-count {
      background: rgba(255, 255, 255, 0.2);
    }

    .dz-gallery-tip {
      margin-top: 16px;
      padding: 12px 14px;
      border-radius: 10px;
      background: #eff6ff;
      border: 1px solid #bfdbfe;
      color: #1e3a8a;
    }

    .dz-tip-title {
      font-weight: 600;
      margin-bottom: 4px;
    }

    .dz-gallery-sections {
      margin-top: 18px;
      display: flex;
      flex-direction: column;
      gap: 24px;
    }

    .dz-gallery-section-header {
      gap: 8px;
      display: flex;
      align-items: center;
      font-weight: 600;
      color: #1f2937;
      margin-bottom: 12px;
    }

    .dz-gallery-section {
      padding: 14px;
      border-radius: 12px;
      border: 1px solid #e5e7eb;
    }

    .dz-gallery-section[data-dz-section=""] {
      background: #f3f4f6;
    }

    .dz-gallery-section[data-dz-section="es"] {
      background: #eff6ff;
      border-color: #dbeafe;
    }

    .dz-gallery-section[data-dz-section="en"] {
      background: #f5f3ff;
      border-color: #ede9fe;
    }

    .dz-photo-grid {
      gap: 14px;
      display: grid;
      min-height: 154px;
      grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    }

    .dz-photo-item {
      position: relative;
      border-radius: 8px;
      overflow: hidden;
      background: #f9fafb;
      border: 1px solid #e5e7eb;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .dz-photo-item:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(15, 23, 42, 0.1);
    }

    .dz-photo-thumb {
      width: 100%;
      height: 100%;
      object-fit: cover;
      aspect-ratio: 1 / 1;
    }

    .dz-photo-badge {
      position: absolute;
      top: 8px;
      left: 8px;
      font-size: 11px;
      font-weight: 600;
      padding: 2px 8px;
      border-radius: 999px;
      background: rgba(15, 23, 42, 0.75);
      color: #fff;
      z-index: 2;
      opacity: 0;
      transition: opacity 0.2s ease;
    }

    .dz-photo-item:hover .dz-photo-badge {
      opacity: 1;
    }

    .dz-photo-actions {
      gap: 6px;
      top: 8px;
      right: 8px;
      opacity: 0;
      z-index: 2;
      display: flex;
      position: absolute;
      flex-direction: column;
      transition: opacity 0.2s ease;
    }

    .dz-photo-item:hover .dz-photo-actions {
      opacity: 1;
    }

    .dz-photo-action {
      color: #111827;
      width: 28px;
      height: 28px;
      border: none;
      display: flex;
      cursor: pointer;
      border-radius: 50%;
      align-items: center;
      transition: all 0.2s;
      justify-content: center;
      background: rgba(255, 255, 255, 0.95);
      box-shadow: 0 2px 6px rgba(15, 23, 42, 0.2);
    }

    .dz-photo-action:hover {
      transform: scale(1.05);
    }

    .dz-photo-action.dz-photo-action-main.active {
      background: #2563eb;
      color: #fff;
    }

    .dz-photo-action.dz-photo-action-delete:hover {
      background: #dc2626;
      color: #fff;
    }

    .dz-drag-handle {
      opacity: 0;
      right: 8px;
      z-index: 2;
      bottom: 8px;
      width: 28px;
      height: 28px;
      color: #fff;
      cursor: grab;
      display: flex;
      position: absolute;
      border-radius: 6px;
      align-items: center;
      justify-content: center;
      transition: opacity 0.2s;
      background: rgba(15, 23, 42, 0.65);
    }

    .dz-photo-item:hover .dz-drag-handle {
      opacity: 1;
    }

    .dz-photo-item.is-main {
      border: 2px solid #2563eb;
    }

    .dz-photo-item-ghost {
      opacity: 0.5;
      border: none;
      box-shadow: none;
    }

    .dz-photo-item-ghost.is-main {
      border: none !important;
    }

    .dz-photo-grid[data-empty="true"] {
      border-radius: 8px;
      border: 1px dashed #cbd5f5;
      background: rgba(0, 0, 0, 0.04);
      position: relative;
    }

    .dz-photo-grid[data-empty="true"]::after {
      content: attr(data-empty-message);
      position: absolute;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      color: #6b7280;
      text-align: center;
      pointer-events: none;
    }

    .dz-locale-gray {
      background: #f3f4f6;
    }

    .dz-locale-blue {
      background: #eff6ff;
    }

    .dz-locale-purple {
      background: #f5f3ff;
    }

    @media (max-width: 900px) {
      .dz-zones {
        flex-direction: column;
      }

      .dz-zone.is-active,
      .dz-zone.is-collapsed {
        flex: 1;
      }
    }
  </style>

  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

  <script>
    const initPhotoManager = function(manager) {
      if (!manager || manager.dataset.dzInitialized === 'true') {
        return;
      }
      manager.dataset.dzInitialized = 'true';

      const managerId = manager.dataset.managerId;
      const locales = JSON.parse(manager.dataset.locales || '{}');
      const csrfToken = manager.dataset.csrf || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

      const zones = Array.from(manager.querySelectorAll('.dz-zone'));
      const zonesWrapper = manager.querySelector('.dz-zones');

      const setActiveZone = function(localeKey) {
        zones.forEach(zone => {
          if (zone.dataset.locale === localeKey) {
            zone.classList.add('is-active');
            zone.classList.remove('is-collapsed');
          } else {
            zone.classList.remove('is-active');
            zone.classList.add('is-collapsed');
          }
        });
      };

      const clearActiveZone = function() {
        zones.forEach(zone => {
          zone.classList.remove('is-active');
          zone.classList.remove('is-collapsed');
        });
      };

      zones.forEach(zone => {
        const localeKey = zone.dataset.locale;
        zone.addEventListener('mouseenter', function() {
          setActiveZone(localeKey);
        });
        zone.addEventListener('focusin', function() {
          setActiveZone(localeKey);
        });
        zone.addEventListener('dragenter', function() {
          setActiveZone(localeKey);
        });
      });

      zonesWrapper?.addEventListener('mouseleave', function() {
        clearActiveZone();
      });

      const filterButtons = Array.from(manager.querySelectorAll('[data-dz-filter]'));
      const sections = Array.from(manager.querySelectorAll('[data-dz-section]'));
      const tip = manager.querySelector('[data-dz-tip]');

      const setFilter = function(filterKey) {
        filterButtons.forEach(button => {
          button.classList.toggle('is-active', button.dataset.dzFilter === filterKey);
        });

        sections.forEach(section => {
          section.style.display = filterKey === 'all' || section.dataset.dzSection === filterKey ? '' : 'none';
        });

        if (tip) {
          tip.style.display = filterKey === 'all' ? '' : 'none';
        }
      };

      const defaultFilter = manager.dataset.defaultLocale || 'all';
      setFilter(defaultFilter === '' ? '' : defaultFilter);

      filterButtons.forEach(button => {
        button.addEventListener('click', function() {
          setFilter(button.dataset.dzFilter);
        });
      });

      const applyLocaleStyles = function(item, localeKey) {
        const config = locales[localeKey] || {
          color: 'gray',
          badge: 'GEN'
        };
        item.dataset.locale = localeKey;
        item.classList.remove('dz-locale-gray', 'dz-locale-blue', 'dz-locale-purple');
        item.classList.add(`dz-locale-${config.color || 'gray'}`);

        const badge = item.querySelector('.dz-photo-badge');
        if (badge) {
          badge.textContent = config.badge || 'GEN';
        }

        item.classList.remove('is-main');
        const mainButton = item.querySelector('.dz-photo-action-main');
        if (mainButton) {
          mainButton.classList.remove('active');
        }
      };

      const updateCounts = function() {
        const totalCount = manager.querySelectorAll('.dz-photo-item').length;
        manager.querySelectorAll('[data-dz-count="all"]').forEach(el => {
          el.textContent = totalCount;
        });

        Object.keys(locales).forEach(localeKey => {
          const count = manager.querySelectorAll(`.dz-photo-item[data-locale="${localeKey}"]`).length;
          manager.querySelectorAll(`[data-dz-count="${localeKey}"]`).forEach(el => {
            el.textContent = count;
          });
        });
      };

      const updateEmptyState = function() {
        manager.querySelectorAll('.dz-photo-grid').forEach(grid => {
          const hasItems = grid.querySelectorAll('.dz-photo-item').length > 0;
          grid.dataset.empty = hasItems ? 'false' : 'true';
        });
      };

      const updatePhotoOrder = function(grid) {
        const items = Array.from(grid.querySelectorAll('.dz-photo-item'));
        if (!items.length) {
          return;
        }
        const photos = items.map((item, index) => {
          item.dataset.sortOrder = index + 1;
          return {
            id: item.dataset.photoId,
            order: index + 1
          };
        });

        fetch("{{ route('dropzone.reorder') }}", {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken || ''
          },
          body: JSON.stringify({
            photos
          })
        }).catch(function(error) {
          console.error('Error updating order:', error);
        });
      };

      const updatePhotoLocale = function(photoId, newLocale, oldLocale, sourceGrid, oldIndex, item) {
        const payload = {
          photo_id: photoId,
          locale: newLocale === '' ? null : newLocale
        };

        fetch("{{ route('dropzone.updateLocale') }}", {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken || ''
          },
          body: JSON.stringify(payload)
        }).then(function(response) {
          if (!response.ok) {
            throw new Error('Locale update failed');
          }
          return response.json();
        }).then(function(data) {
          updateCounts();
          updateEmptyState();

        }).catch(function(error) {
          console.error(error);
          const fallbackItem = item || manager.querySelector(`.dz-photo-item[data-photo-id="${photoId}"]`);
          if (fallbackItem) {
            applyLocaleStyles(fallbackItem, oldLocale);
            if (sourceGrid) {
              const referenceNode = sourceGrid.children[oldIndex] || null;
              sourceGrid.insertBefore(fallbackItem, referenceNode);
              updatePhotoOrder(sourceGrid);
              updateEmptyState();
            }
          }
        });
      };

      const grids = Array.from(manager.querySelectorAll('.dz-photo-grid'));
      const initSortable = function() {
        grids.forEach(function(grid) {
          new Sortable(grid, {
            animation: 150,
            ghostClass: 'dz-photo-item-ghost',
            handle: '.dz-drag-handle',
            group: managerId,
            onEnd: function(evt) {
              updatePhotoOrder(evt.to);
              if (evt.from !== evt.to) {
                updatePhotoOrder(evt.from);
              }
              updateEmptyState();
            },
            onAdd: function(evt) {
              const newLocale = evt.to.dataset.locale || '';
              const oldLocale = evt.item.dataset.locale || '';
              if (newLocale !== oldLocale) {
                const photoId = evt.item.dataset.photoId;
                applyLocaleStyles(evt.item, newLocale);
                updatePhotoLocale(photoId, newLocale, oldLocale, evt.from, evt.oldIndex, evt.item);
              }
            }
          });
        });
      };

      if (typeof Sortable !== 'undefined') {
        initSortable();
      } else {
        const waitSortable = function() {
          if (typeof Sortable !== 'undefined') {
            initSortable();
          } else {
            setTimeout(waitSortable, 500);
          }
        };
        waitSortable();
      }

      const showLightbox = function(container, imageUrl) {
        const existing = document.querySelector('.dz-lightbox');
        if (existing) {
          existing.remove();
        }

        const buttons = Array.from(container.querySelectorAll('.dz-photo-action-view'));
        const urls = buttons.map(button => button.dataset.photoUrl);
        let currentIndex = Math.max(0, urls.indexOf(imageUrl));

        const lightbox = document.createElement('div');
        lightbox.className = 'dz-lightbox';
        lightbox.innerHTML = `
          <div class="dz-lightbox-backdrop"></div>
          <div class="dz-lightbox-content">
            <button class="dz-lightbox-nav dz-lightbox-prev" type="button">{{ __('dropzone-enhanced::messages.lightbox.prev') }}</button>
            <img src="" alt="{{ __('dropzone-enhanced::messages.lightbox.image') }}" />
            <button class="dz-lightbox-nav dz-lightbox-next" type="button">{{ __('dropzone-enhanced::messages.lightbox.next') }}</button>
            <div class="dz-lightbox-counter"></div>
            <button class="dz-lightbox-close" type="button">&times;</button>
          </div>
        `;

        const style = document.createElement('style');
        style.textContent = `
          .dz-lightbox {
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
          .dz-lightbox-backdrop {
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            position: absolute;
            background: rgba(0, 0, 0, 0.8);
          }
          .dz-lightbox-content {
            z-index: 10000;
            max-width: 90%;
            max-height: 90%;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
          }
          .dz-lightbox-content img {
            display: block;
            max-width: 80vw;
            max-height: 80vh;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5);
          }
          .dz-lightbox-close {
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
          .dz-lightbox-nav {
            border: none;
            cursor: pointer;
            font-size: 14px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.9);
            color: #111827;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
          }
          .dz-lightbox-counter {
            left: 50%;
            bottom: -28px;
            color: #fff;
            font-size: 12px;
            position: absolute;
            transform: translateX(-50%);
          }
        `;

        document.head.appendChild(style);
        document.body.appendChild(lightbox);

        const img = lightbox.querySelector('img');
        const prevButton = lightbox.querySelector('.dz-lightbox-prev');
        const nextButton = lightbox.querySelector('.dz-lightbox-next');
        const counter = lightbox.querySelector('.dz-lightbox-counter');

        const updateView = function() {
          if (!urls.length) {
            return;
          }
          img.src = urls[currentIndex];
          counter.textContent = `${currentIndex + 1} {{ __('dropzone-enhanced::messages.lightbox.of') }} ${urls.length}`;
        };

        const goPrev = function() {
          currentIndex = (currentIndex - 1 + urls.length) % urls.length;
          updateView();
        };

        const goNext = function() {
          currentIndex = (currentIndex + 1) % urls.length;
          updateView();
        };

        updateView();

        prevButton.addEventListener('click', goPrev);
        nextButton.addEventListener('click', goNext);

        const close = function() {
          document.body.removeChild(lightbox);
          document.removeEventListener('keydown', onKeyDown);
        };

        const onKeyDown = function(event) {
          if (event.key === 'Escape') {
            close();
          } else if (event.key === 'ArrowLeft') {
            goPrev();
          } else if (event.key === 'ArrowRight') {
            goNext();
          }
        };

        document.addEventListener('keydown', onKeyDown);

        lightbox.querySelector('.dz-lightbox-backdrop').addEventListener('click', close);
        lightbox.querySelector('.dz-lightbox-close').addEventListener('click', close);
      };

      const setMainPhoto = function(photoId) {
        fetch("{{ route('dropzone.setMain', ['id' => '__id__']) }}".replace('__id__', photoId), {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'X-CSRF-TOKEN': csrfToken || ''
            }
          })
          .then(response => response.json())
          .then(data => {
            if (!data.success) {
              return;
            }
            const photoItem = manager.querySelector(`.dz-photo-item[data-photo-id="${photoId}"]`);
            if (!photoItem) {
              return;
            }
            const localeKey = photoItem.dataset.locale || '';
            manager.querySelectorAll(`.dz-photo-item[data-locale="${localeKey}"]`).forEach(item => {
              item.classList.remove('is-main');
              const mainButton = item.querySelector('.dz-photo-action-main');
              if (mainButton) {
                mainButton.classList.remove('active');
              }
            });

            if (data.is_main) {
              photoItem.classList.add('is-main');
              const mainButton = photoItem.querySelector('.dz-photo-action-main');
              if (mainButton) {
                mainButton.classList.add('active');
              }
            }
          })
          .catch(error => console.error('Error setting main photo:', error));
      };

      const deletePhoto = function(photoId) {
        if (!confirm("{{ __('dropzone-enhanced::messages.photos.confirm_delete') }}")) {
          return;
        }

        fetch("{{ route('dropzone.destroy', ['id' => '__id__']) }}".replace('__id__', photoId), {
            method: 'DELETE',
            headers: {
              'Accept': 'application/json',
              'X-CSRF-TOKEN': csrfToken || ''
            }
          })
          .then(response => {
            if (!response.ok) {
              return response.json().then(errorData => {
                throw new Error(errorData.message || 'Delete failed');
              });
            }
            return response.json();
          })
          .then(data => {
            if (!data.success) {
              return;
            }
            const photoItem = manager.querySelector(`.dz-photo-item[data-photo-id="${photoId}"]`);
            if (photoItem) {
              const grid = photoItem.closest('.dz-photo-grid');
              photoItem.remove();
              updateCounts();
              updateEmptyState();
            }
          })
          .catch(error => {
            console.error('Error deleting photo:', error);
            alert(error.message || 'Error deleting photo. Please try again.');
          });
      };

      const initActions = function() {
        manager.querySelectorAll('.dz-photo-action-main').forEach(button => {
          button.addEventListener('click', function() {
            setMainPhoto(button.dataset.photoId);
          });
        });
        manager.querySelectorAll('.dz-photo-action-delete').forEach(button => {
          button.addEventListener('click', function() {
            deletePhoto(button.dataset.photoId);
          });
        });
        manager.querySelectorAll('.dz-photo-action-view').forEach(button => {
          button.addEventListener('click', function() {
            showLightbox(manager, button.dataset.photoUrl);
          });
        });
      };

      initActions();
    };

    const refreshPhotoManager = function(manager) {
      fetch(window.location.href)
        .then(response => response.text())
        .then(html => {
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');
          const updated = doc.getElementById(manager.id);
          if (updated) {
            manager.replaceWith(updated);
            initPhotoManager(updated);
          }
        })
        .catch(error => console.error('Error refreshing photo manager:', error));
    };

    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.dz-photo-manager').forEach(initPhotoManager);
    });

    document.addEventListener('dropzone-uploads-finished', function(event) {
      document.querySelectorAll('.dz-photo-manager').forEach(manager => {
        const modelId = manager.querySelector('.dropzone-container')?.dataset.modelId;
        if (event.detail?.modelId && modelId && event.detail.modelId.toString() !== modelId) {
          return;
        }
        refreshPhotoManager(manager);
      });
    });
  </script>
@endonce
