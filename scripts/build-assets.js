#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

console.log('🚀 Building Dropzone assets...');

// Source and destination paths
const sourceDir = path.join(__dirname, 'node_modules', 'dropzone', 'dist');
const destDir = path.join(__dirname, 'resources', 'assets');

// Files to copy
const filesToCopy = [
  {
    source: 'dropzone-min.js',
    dest: 'dropzone-min.js'
  },
  {
    source: 'dropzone-min.js.map',
    dest: 'dropzone-min.js.map'
  },
  {
    source: 'dropzone.css',
    dest: 'dropzone.css'
  },
  {
    source: 'dropzone.css.map',
    dest: 'dropzone.css.map'
  }
];

// Create destination directory if it doesn't exist
if (!fs.existsSync(destDir)) {
  fs.mkdirSync(destDir, { recursive: true });
}

// Copy files
let copiedFiles = 0;
let skippedFiles = 0;

filesToCopy.forEach(file => {
  const sourcePath = path.join(sourceDir, file.source);
  const destPath = path.join(destDir, file.dest);

  try {
    if (fs.existsSync(sourcePath)) {
      fs.copyFileSync(sourcePath, destPath);
      console.log(`✅ Copied: ${file.source} → ${file.dest}`);
      copiedFiles++;
    } else {
      console.log(`⚠️  Skipped: ${file.source} (not found)`);
      skippedFiles++;
    }
  } catch (error) {
    console.error(`❌ Error copying ${file.source}:`, error.message);
  }
});

// Get version info
try {
  const packageJson = JSON.parse(fs.readFileSync(path.join(__dirname, 'node_modules', 'dropzone', 'package.json'), 'utf8'));
  console.log(`\n📦 Dropzone version: ${packageJson.version}`);
} catch (error) {
  console.log('\n📦 Could not read Dropzone version');
}

console.log(`\n🎉 Build complete! Copied ${copiedFiles} files, skipped ${skippedFiles} files.`);
