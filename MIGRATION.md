# Migration Guide v1.3.1 → v1.3.2

## 🔧 Disk Configuration Change

Starting with v1.3.2, Laravel Dropzone Enhanced now uses the same disk configuration as Laravel Glide Enhanced for better compatibility.

### Before (v1.3.1 and earlier)
```php
// config/dropzone.php
'storage' => [
    'disk' => 'public',  // ← Fixed value
    'directory' => 'images',
],
```

### After (v1.3.2+)
```php
// config/dropzone.php
'storage' => [
    'disk' => config('images.disk', 'public'),  // ← Falls back to Glide config
    'directory' => 'images',
],
```

## ✅ What You Need To Do

### If you DON'T have Laravel Glide Enhanced installed:
**Nothing!** The fallback is `'public'`, so everything works as before.

### If you DO have Laravel Glide Enhanced installed:
1. **Check your `config/images.php`**:
   ```php
   'disk' => 'public',  // ← Make sure this matches your desired disk
   ```

2. **If you had custom disk in `config/dropzone.php`**:
   - Move that setting to `config/images.php` instead
   - Both packages will now use the same disk automatically

## 🎯 Benefits

- ✅ **No more broken images** when both packages are installed
- ✅ **Unified configuration** - one place to configure disk for both packages
- ✅ **Automatic compatibility** - packages work together seamlessly
- ✅ **Simpler maintenance** - less configuration to manage

## 🔍 Example Scenarios

### Scenario 1: Both packages use default 'public' disk
```php
// config/images.php (or don't change anything)
'disk' => 'public',
```
✅ **Result**: Both packages use 'public' disk, images work perfectly.

### Scenario 2: You want to use 's3' disk for images
```php
// config/images.php
'disk' => 's3',
```
✅ **Result**: Both packages use 's3' disk automatically.

### Scenario 3: You want separate disks (advanced)
If you really need separate disks for some reason, you can override in your service provider:
```php
// In AppServiceProvider::boot()
config(['dropzone.storage.disk' => 'uploads']);
```

But this is **not recommended** as it defeats the purpose of the compatibility fix.
