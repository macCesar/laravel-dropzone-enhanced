#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

console.log('🚀 Building Dropzone assets...');

// Source and destination paths
const rootDir = path.resolve(__dirname, '..');
const destDir = path.join(rootDir, 'resources', 'assets');

// Files to copy
const filesToCopy = [
  {
    source: 'node_modules/dropzone/dist/dropzone-min.js',
    dest: 'dropzone-min.js'
  },
  {
    source: 'node_modules/dropzone/dist/dropzone-min.js.map',
    dest: 'dropzone-min.js.map'
  },
  {
    source: 'node_modules/dropzone/dist/dropzone.css',
    dest: 'dropzone.css'
  },
  {
    source: 'node_modules/dropzone/dist/dropzone.css.map',
    dest: 'dropzone.css.map'
  },
  {
    source: 'node_modules/sortablejs/Sortable.min.js',
    dest: 'Sortable.min.js'
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
  const sourcePath = path.join(rootDir, file.source);
  const destPath = path.join(destDir, file.dest);

  try {
    if (fs.existsSync(sourcePath)) {
      fs.copyFileSync(sourcePath, destPath);
      console.log(`✅ Copied: ${file.dest}`);
      copiedFiles++;
    } else {
      console.error(`❌ Missing: ${file.source}`);
      skippedFiles++;
    }
  } catch (error) {
    console.error(`❌ Error copying ${file.source}:`, error.message);
    skippedFiles++;
  }
});

// Get version info
try {
  const packageJson = JSON.parse(fs.readFileSync(path.join(rootDir, 'node_modules', 'dropzone', 'package.json'), 'utf8'));
  console.log(`\n📦 Dropzone version: ${packageJson.version}`);
} catch (error) {
  console.log('\n📦 Could not read Dropzone version');
}

console.log(`\n🎉 Build complete! Copied ${copiedFiles} files, skipped ${skippedFiles} files.`);

if (skippedFiles > 0) {
  process.exitCode = 1;
}
