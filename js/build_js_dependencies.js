const fs = require('fs');
const path = require('path');
const copy = (from, to) => {
    fs.mkdirSync(path.dirname(to), { recursive: true });
    fs.copyFileSync(from, to);
};

// Map sources to /lib (our explicit dependency contract)
// todo: make an object and simply process all of them (should be in a list)
copy('node_modules/jquery/dist/jquery.min.js', 'lib/js/jquery-3.6.0.min.js');