<x-dropzone-enhanced::area :dimensions="$dimensions ?? config('dropzone.images.default_dimensions')" :directory="$directory" :maxFiles="$maxFiles ?? 10" :maxFilesize="$maxFilesize ?? config('dropzone.images.max_filesize') / 1000" :object="$object" :preResize="$preResize ?? config('dropzone.images.pre_resize')" />

<x-dropzone-enhanced::photos :object="$object" :thumbnailDimensions="$thumbnailDimensions ?? config('dropzone.images.thumbnails.dimensions')" />

<x-dropzone-enhanced::lightbox :object="$object" />
