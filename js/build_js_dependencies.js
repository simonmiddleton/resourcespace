const fs = require('fs');
const path = require('path');
const copy = (from, to) => {
    if (!fs.existsSync('node_modules/')) {
        console.error('Missing node_modules/. Run `pnpm install` first');
    } else if (!fs.existsSync(from)) {
        console.error('Missing file %o', from);
    }

    fs.mkdirSync(path.dirname(to), { recursive: true });
    fs.copyFileSync(from, to);
};

// Map sources to /lib (our explicit dependency contract)
const files = [
  { src: 'node_modules/jquery/dist/jquery.min.js', dest: 'lib/js/jquery-3.6.0.min.js' },
  { src: 'node_modules/jquery/LICENSE.txt', dest: 'documentation/licenses/jquery.txt' },
  { src: 'node_modules/jquery-tageditor/jquery.tag-editor.min.js', dest: 'lib/jquery_tag_editor/jquery.tag-editor.min.js' },
  { src: 'node_modules/jquery-tageditor/jquery.tag-editor.css', dest: 'lib/jquery_tag_editor/jquery.tag-editor.css' },
  { src: 'node_modules/jquery-tageditor/jquery.caret.min.js', dest: 'lib/jquery_tag_editor/jquery.caret.min.js' },
  { src: 'node_modules/jquery-ui/dist/jquery-ui.min.js', dest: 'lib/js/jquery-ui-1.13.2.min.js' },
  { src: 'node_modules/jquery-ui/LICENSE.txt', dest: 'documentation/licenses/jquery-ui.txt' },
  { src: 'node_modules/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js', dest: 'lib/js/jquery.ui.touch-punch.min.js' },
];

for (const file of files) {
    copy(file.src, file.dest);
}