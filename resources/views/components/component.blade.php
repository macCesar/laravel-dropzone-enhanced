<x-dropzone-enhanced::area :dimensions="$dimensions ?? config('dropzone.images.default_dimensions')" :directory="$directory" :maxFiles="$maxFiles ?? 10" :maxFilesize="$maxFilesize ?? config('dropzone.images.max_filesize') / 1000" :model="$model" :preResize="$preResize ?? config('dropzone.images.pre_resize')" />

<x-dropzone-enhanced::photos :model="$model" :thumbnailDimensions="$thumbnailDimensions ?? config('dropzone.images.thumbnails.dimensions')" />

<x-dropzone-enhanced::lightbox :model="$model" />
