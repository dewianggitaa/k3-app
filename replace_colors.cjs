const fs = require('fs');
const path = require('path');

const targetDir = path.join(__dirname, 'resources/js');

function processFile(filePath) {
    if (!filePath.endsWith('.vue')) return;

    let content = fs.readFileSync(filePath, 'utf8');
    let original = content;

    // We only want to replace classes that do not look like they belong in a style block easily, or generic things.
    // Replace gray-700 and gray-900 texts with ink and dark mode support if it doesn't already have one
    content = content.replace(/text-gray-900(?!\/)/g, 'text-ink dark:text-ink-dark');
    content = content.replace(/text-gray-800(?!\/)/g, 'text-ink dark:text-ink-dark');
    content = content.replace(/text-gray-700(?!\/)/g, 'text-ink dark:text-ink-dark/90');
    content = content.replace(/text-gray-600(?!\/)/g, 'text-ink-light dark:text-ink-dark/70');
    content = content.replace(/text-gray-500(?!\/)/g, 'text-ink-light dark:text-ink-dark/60');

    // Replace primary-ish colors with the semantic token
    content = content.replace(/bg-indigo-600/g, 'bg-primary');
    content = content.replace(/bg-indigo-700/g, 'bg-primary-hover');
    content = content.replace(/bg-indigo-50/g, 'bg-primary/5');
    content = content.replace(/bg-indigo-100/g, 'bg-primary/10');
    content = content.replace(/bg-blue-50/g, 'bg-primary/5');
    content = content.replace(/bg-blue-100/g, 'bg-primary/10');

    content = content.replace(/text-indigo-600/g, 'text-primary');
    content = content.replace(/text-indigo-400/g, 'text-primary-dark');
    content = content.replace(/text-blue-600/g, 'text-primary');

    content = content.replace(/border-indigo-100/g, 'border-primary/20');
    content = content.replace(/border-indigo-200/g, 'border-primary/30');

    // Replace the specific audit trail "black" primary buttons to "primary"
    content = content.replace(/bg-gray-800 text-white/g, 'bg-primary text-white');

    if (content !== original) {
        fs.writeFileSync(filePath, content, 'utf8');
        console.log(`Updated colors in: ${filePath}`);
    }
}

function traverseDirectory(dir) {
    const files = fs.readdirSync(dir);

    for (const file of files) {
        const fullPath = path.join(dir, file);
        if (fs.statSync(fullPath).isDirectory()) {
            // Skip node_modules and vendor
            if (file !== 'node_modules' && file !== 'vendor') {
                traverseDirectory(fullPath);
            }
        } else {
            processFile(fullPath);
        }
    }
}

traverseDirectory(targetDir);
console.log('Finished updating tailwind colors across Vue files.');
