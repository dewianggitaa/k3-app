const fs = require('fs');
const path = require('path');

function walkDir(dir, callback) {
    fs.readdirSync(dir).forEach(f => {
        let dirPath = path.join(dir, f);
        let isDirectory = fs.statSync(dirPath).isDirectory();
        if (isDirectory) {
            walkDir(dirPath, callback);
        } else {
            callback(dirPath);
        }
    });
}

const replacements = [
    // Corners
    [/\brounded-(sm|lg|xl|2xl|3xl)\b/g, 'rounded-md'],
    // Padding/margins
    [/\bp-(5|6|8|10|12)\b/g, 'p-4'],
    [/\bpx-(5|6|8|10|12)\b/g, 'px-4'],
    [/\bpy-(5|6|8|10|12)\b/g, 'py-4'],
    [/\bm-(5|6|8|10|12)\b/g, 'm-4'],
    [/\bmx-(5|6|8|10|12)\b/g, 'mx-4'],
    [/\bmy-(5|6|8|10|12)\b/g, 'my-4'],
    [/\bgap-(5|6|8|10|12)\b/g, 'gap-4'],

    // Backgrounds (Light)
    [/\bbg-white\b/g, 'bg-surface'],
    [/\bbg-(gray|slate|zinc)-(50|100)\b/g, 'bg-ghost'],
    [/\bhover:bg-(gray|slate|zinc)-(50|100|200)\b/g, 'hover:bg-ghost-hover'],
    [/\bhover:bg-white\b/g, 'hover:bg-ghost-hover'],

    // Backgrounds (Dark)
    [/\bdark:bg-(gray|slate|zinc)-(800|900)\b/g, 'dark:bg-surface-dark'],
    [/\bdark:hover:bg-(gray|slate|zinc)-(700|800)\b/g, 'dark:hover:bg-ghost-dark'],

    // Text (Light)
    [/\btext-(gray|slate|zinc)-(800|900)\b/g, 'text-ink'],
    [/\btext-black\b/g, 'text-ink'],
    [/\btext-(gray|slate|zinc)-(400|500|600)\b/g, 'text-ink-light'],

    // Text (Dark)
    [/\bdark:text-white\b/g, 'dark:text-ink-dark'],
    [/\bdark:text-(gray|slate|zinc)-(100|200)\b/g, 'dark:text-ink-dark'],
    [/\bdark:text-(gray|slate|zinc)-(300|400|500)\b/g, 'dark:text-ink-light'],

    // Borders
    [/\bborder-(gray|slate|zinc)-(100|200|300)\b/g, 'border-ghost-hover'],
    [/\bdark:border-(gray|slate|zinc)-(600|700|800)\b/g, 'dark:border-ghost-dark'],

    // Primary (Indigo/Blue -> primary)
    [/\bbg-(indigo|blue|teal|cyan)-(500|600)\b/g, 'bg-primary'],
    [/\bbg-(indigo|blue|teal|cyan)-50\b/g, 'bg-primary/10'],
    [/\bhover:bg-(indigo|blue|teal|cyan)-(600|700)\b/g, 'hover:bg-primary-hover'],
    [/\btext-(indigo|blue|teal|cyan)-(500|600|700)\b/g, 'text-primary'],
    [/\bdark:text-(indigo|blue|teal|cyan)-(300|400)\b/g, 'dark:text-primary-light'],
    [/\bborder-(indigo|blue|teal|cyan)-(200|300|500|600)\b/g, 'border-primary'],
    [/\bring-(indigo|blue|teal|cyan)-(500|600)\b/g, 'ring-primary'],
    [/\bfocus:border-(indigo|blue|teal|cyan)-(500|600)\b/g, 'focus:border-primary'],
    [/\bfocus:ring-(indigo|blue|teal|cyan)-(500|600)\b/g, 'focus:ring-primary'],

    // Danger (Red -> danger)
    [/\bbg-red-(500|600)\b/g, 'bg-danger'],
    [/\bbg-red-100\b/g, 'bg-danger/20'],
    [/\bbg-red-50\b/g, 'bg-danger/10'],
    [/\btext-red-(500|600|700)\b/g, 'text-danger'],
    [/\bdark:text-red-(300|400)\b/g, 'dark:text-danger/80'],
    [/\bborder-red-(200|300|500)\b/g, 'border-danger/30'],

    // Success (Emerald/Green/Purple -> success/warning depending on case)
    // Purple is used in some lists, let's keep purple or map to primary or warning. I'll stick to strictly emerald/green.
    [/\bbg-(emerald|green)-(500|600)\b/g, 'bg-success'],
    [/\bbg-(emerald|green)-100\b/g, 'bg-success/20'],
    [/\bbg-(emerald|green)-50\b/g, 'bg-success/10'],
    [/\btext-(emerald|green)-(500|600|700)\b/g, 'text-success'],
    [/\bdark:text-(emerald|green)-(300|400)\b/g, 'dark:text-success/80'],
    [/\bborder-(emerald|green)-(200|300|500)\b/g, 'border-success/30'],

    // Warning (Amber/Yellow/Orange -> warning)
    [/\bbg-(amber|yellow|orange)-(500|600)\b/g, 'bg-warning'],
    [/\bbg-(amber|yellow|orange)-100\b/g, 'bg-warning/20'],
    [/\bbg-(amber|yellow|orange)-50\b/g, 'bg-warning/10'],
    [/\btext-(amber|yellow|orange)-(500|600|700)\b/g, 'text-warning'],
    [/\bdark:text-(amber|yellow|orange)-(300|400)\b/g, 'dark:text-warning/80'],
    [/\bborder-(amber|yellow|orange)-(200|300|500)\b/g, 'border-warning/30'],
];

walkDir(path.join(__dirname, 'resources/js'), function (filePath) {
    if (filePath.endsWith('.vue')) {
        let content = fs.readFileSync(filePath, 'utf8');
        let original = content;

        // 1. Remove HTML comments (multiline)
        content = content.replace(/<!--[\s\S]*?-->/g, '');

        // 2. Remove purely descriptive single-line JS comments
        // We look for ' // ' preceded by start of line or spaces, followed by non-slashes.
        content = content.replace(/^[ \t]*\/\/\s+.*$/gm, '');
        content = content.replace(/^[ \t]*\/\/-+.*$/gm, '');
        content = content.replace(/\/\/\s+───.*$/gm, '');

        // 3. Taililwind classes replacements
        replacements.forEach(([regex, replacement]) => {
            content = content.replace(regex, replacement);
        });

        // Clean up multiple newlines that might occur after removing comments
        content = content.replace(/\n\s*\n\s*\n/g, '\n\n');

        if (content !== original) {
            fs.writeFileSync(filePath, content, 'utf8');
            console.log('Updated: ' + filePath);
        }
    }
});
