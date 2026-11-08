<?php

// Override parsial: aset Livewire dipasang manual di layout
// (@livewireStyles di <head>, @livewireScripts di akhir <body>)
// agar script tidak memblokir render pertama.
return [
    'inject_assets' => false,
];
