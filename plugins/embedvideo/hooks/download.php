<?php

function HookEmbedvideoAllDirectdownloadaccess(){
    return getval('ext', '') === 'vtt';
} 