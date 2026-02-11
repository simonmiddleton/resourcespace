const fs = require('fs');
const path = require('path');
const copy = (from, to) => {
    if (!fs.existsSync('node_modules/')) {
        console.error('Missing node_modules/. Run `pnpm install` first');
        return;
    }

    if (!fs.existsSync(from)) {
        console.error('Missing source file %o', from);
        return;
    }

    if (fs.statSync(from).isDirectory()) {
        fs.mkdirSync(to, { recursive: true });
        fs.cpSync(from, to, {
            recursive: true,
            force: true, // overwrite if exists
            errorOnExist: false
        });
    } else {
        fs.mkdirSync(path.dirname(to), { recursive: true });
        fs.copyFileSync(from, to);
    }
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
  { src: 'node_modules/capslockstate-jquery-plugin#1.2.1/src/jquery.capslockstate.js', dest: 'lib/js/jquery.capslockstate.js' },
  { src: 'node_modules/capslockstate-jquery-plugin#1.2.1/MIT-LICENSE.txt', dest: 'documentation/licenses/jquery.capslockstate.txt' },
  { src: 'node_modules/jquery-validation/dist/jquery.validate.min.js', dest: 'lib/js/jquery.validate.min.js' },
  { src: 'node_modules/jquery-validation/dist/additional-methods.min.js', dest: 'lib/js/jquery.validate.additional.js' },
  { src: 'node_modules/jquery-validation/LICENSE.md', dest: 'documentation/licenses/jquery-validation.txt' },
  { src: 'node_modules/jstree/dist/jstree.min.js', dest: 'lib/jstree/jstree.min.js' },
  { src: 'node_modules/jstree/dist/themes', dest: 'lib/jstree/themes' },
  { src: 'node_modules/jstree/LICENSE-MIT', dest: 'documentation/licenses/jstree.txt' },
];

for (const file of files) {
    copy(file.src, file.dest);
}