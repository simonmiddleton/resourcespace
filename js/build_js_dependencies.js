const fs = require('fs');
const path = require('path');

/**
 * Copy file/directory.
 *
 * When copying a directory you may specify `patterns`:
 * @example copy everything (default) - ['** /*']
 * @example copy only CSS & JS - ['** /*.css', '** /*.js']
 * @example copy everything except maps - ['** /*', '!** /*.map']
 * @example copy only /public subtree - ['public/ **']
 * NOTE: all the above patterns have a space between `**` and `/` ONLY to not break the JS docblock.
 *
 * Negated patterns start with "!".
 * Matching is done against POSIX-style relative paths.
 */
const copy = (from, to, { patterns = ['**/*'] } = {}) => {
    if (!fs.existsSync('node_modules/')) {
        console.error('Missing node_modules/. Run `pnpm install` first');
        return;
    }

    if (!fs.existsSync(from)) {
        console.error('Missing source file %o', from);
        return;
    }

    console.group('%o => %o (patterns: %o)', from, to, JSON.stringify(patterns));

    // Single file copy (patterns do not apply)
    if (!fs.statSync(from).isDirectory()) {
        fs.mkdirSync(path.dirname(to), { recursive: true });
        fs.copyFileSync(from, to);
        console.groupEnd();
        return;
    }

    // Directory copy (with pattern matching)
    fs.mkdirSync(to, { recursive: true });

    const match = makePatternMatcher(patterns);

    for (const rel of walkRelativeFiles(from)) {
        // console.debug('- match(%o) = %o', rel, match(rel)); // useful for troubleshooting but too verbose otherwise
        if (!match(rel)) continue;

        const src = path.join(from, rel);
        const dst = path.join(to, rel);

        fs.mkdirSync(path.dirname(dst), { recursive: true });
        fs.copyFileSync(src, dst);
    }

    console.groupEnd();
};

function* walkRelativeFiles(root) {
    const stack = [root];

    while (stack.length) {
        const dir = stack.pop();

        for (const ent of fs.readdirSync(dir, { withFileTypes: true })) {
            const abs = path.join(dir, ent.name);

            if (ent.isDirectory()) {
                stack.push(abs);
            } else if (ent.isFile()) {
                yield path
                    .relative(root, abs)
                    .split(path.sep)
                    .join("/");
            }
        }
    }
}

function makePatternMatcher(patterns) {
    const rules = patterns.map(parsePattern);
    const hasPositive = rules.some((r) => !r.negated);

    // gitignore-like semantics:
    //  - start excluded
    //  - first positive match includes
    //  - later negated match excludes
    return (relPath) => {
        let matched = !hasPositive;

        for (const rule of rules) {
            if (!rule.regex.test(relPath)) continue;
            matched = !rule.negated;
        }

        return matched;
    };
}

function parsePattern(pat) {
    const negated = pat.startsWith('!');
    const raw = negated ? pat.slice(1) : pat;
    return {
        negated,
        regex: globToRegExp(raw),
    };
}

function globToRegExp(glob) {
    // Normalize slashes
    let g = glob.replace(/\\/g, '/');

    // Escape regex specials first
    g = g.replace(/[.+^${}()|[\]\\]/g, '\\$&');

    // Treat "**/" as "zero or more directories" to allow matching files in the directories' root
    g = g.replace(/\*\*\//g, '§§DS_SLASH§§');

    // Now replace the remaining glob tokens
    // - "*"  matches anything except "/"
    // - "**" matches anything including "/"
    // - "?"  matches one char except "/"
    g = g
        .replace(/\*\*/g, '§§DS§§')
        .replace(/\*/g, '[^/]*')
        .replace(/\?/g, '[^/]')
        .replace(/§§DS_SLASH§§/g, '(?:.*/)?')
        .replace(/§§DS§§/g, '.*');

    return new RegExp(`^${g}$`);
}

// Map sources to /lib (our explicit dependency contract)
const files = [
    { src: 'node_modules/jquery/dist/jquery.min.js', dest: 'lib/js/jquery-3.6.0.min.js' },
    { src: 'node_modules/jquery/LICENSE.txt', dest: 'documentation/licenses/jquery.txt' },
    {
        src: 'node_modules/jquery-tageditor',
        dest: 'lib/jquery_tag_editor',
        filter: { patterns: ['**/*.min.js', '**/*.css'] }
    },
    { src: 'node_modules/jquery-ui/dist/jquery-ui.min.js', dest: 'lib/js/jquery-ui-1.13.2.min.js' },
    { src: 'node_modules/jquery-ui/LICENSE.txt', dest: 'documentation/licenses/jquery-ui.txt' },
    { src: 'node_modules/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js', dest: 'lib/js/jquery.ui.touch-punch.min.js' },
    { src: 'node_modules/capslockstate-jquery-plugin#1.2.1/src/jquery.capslockstate.js', dest: 'lib/js/jquery.capslockstate.js' },
    { src: 'node_modules/capslockstate-jquery-plugin#1.2.1/MIT-LICENSE.txt', dest: 'documentation/licenses/jquery.capslockstate.txt' },
    { src: 'node_modules/jquery-validation/dist/jquery.validate.min.js', dest: 'lib/js/jquery.validate.min.js' },
    { src: 'node_modules/jquery-validation/dist/additional-methods.min.js', dest: 'lib/js/jquery.validate.additional.js' },
    { src: 'node_modules/jquery-validation/LICENSE.md', dest: 'documentation/licenses/jquery-validation.txt' },
    {
        src: 'node_modules/jstree/dist',
        dest: 'lib/jstree',
        filter: { patterns: ['**/*.min.js', 'themes/**'] }
    },
    { src: 'node_modules/jstree/LICENSE-MIT', dest: 'documentation/licenses/jstree.txt' },
    {
        src: 'node_modules/chosen-js',
        dest: 'lib/chosen',
        filter: {
            patterns: [
                '**/*.png',
                '**/*.min.*',
                '!**/*proto.min.js',
            ]
        }
    },
    { src: 'node_modules/chosen-js/LICENSE.md', dest: 'documentation/licenses/chosen.txt' },
    { src: 'node_modules/dompurify/dist/purify.min.js', dest: 'lib/js/purify.min.js' },
    { src: 'node_modules/dompurify/dist/purify.min.js.map', dest: 'lib/js/purify.min.js.map' }, // not versioned, only helps devs
    { src: 'node_modules/dompurify/LICENSE', dest: 'documentation/licenses/DOMPurify.txt' },
    {
        src: 'node_modules/lightbox2/dist',
        dest: 'lib/lightbox',
        filter: {
            patterns: [
                '**/lightbox.min.js',
                '**/lightbox.min.map', // not versioned, only helps devs
                '**/lightbox.min.css',
                'images/**',
            ]
        }
    },
    { src: 'node_modules/lightbox2/LICENSE', dest: 'documentation/licenses/lightbox.txt' },
    {
        src: 'node_modules/tinymce',
        dest: 'lib/tinymce',
        filter: {
            patterns: [
                '**/*.min.js',
                // Include skins (only the minified CSS)
                'skins/**',
                '!skins/**/*.css',
                'skins/**/*.min.css',
            ]
        }
    },
    { src: 'node_modules/tinymce/license.md', dest: 'documentation/licenses/tinymce.md' },
    { src: 'node_modules/toastify-js', dest: 'lib/toastify-js', filter: { patterns: ['src/**'] } },
    { src: 'node_modules/toastify-js/LICENSE', dest: 'documentation/licenses/toastify-js' },
];

console.log('Copying distribution files to ResourceSpace');
for (const file of files) {
    copy(file.src, file.dest, file.filter);
}