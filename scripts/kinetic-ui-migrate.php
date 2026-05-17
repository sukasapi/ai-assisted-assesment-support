<?php

$replacements = [
    'text-zinc-600 hover:text-zinc-900' => 'text-on-surface-variant hover:text-primary',
    'text-zinc-600' => 'text-on-surface-variant',
    'text-zinc-500' => 'text-on-surface-variant',
    'text-zinc-700' => 'text-on-surface-variant',
    'text-zinc-800' => 'text-on-surface',
    'text-zinc-900' => 'text-on-surface',
    'text-zinc-400' => 'text-on-surface-variant/70',
    'bg-zinc-50' => 'bg-surface-container-low',
    'bg-zinc-100' => 'bg-surface-container',
    'bg-zinc-200' => 'bg-surface-container-high',
    'border-zinc-200' => 'border-outline-variant/40',
    'border-zinc-100' => 'border-outline-variant/20',
    'border-zinc-300' => 'border-outline-variant',
    'hover:bg-zinc-50' => 'hover:bg-surface-container-low',
    'hover:bg-zinc-100' => 'hover:bg-surface-container',
    'rounded-md bg-zinc-900' => 'rounded-lg accent-gradient',
    'bg-zinc-900' => 'bg-primary',
    'hover:bg-zinc-800' => 'hover:opacity-90',
    'border border-violet-300 bg-violet-50' => 'border border-primary/30 bg-secondary-container',
    'border-violet-100 bg-violet-50/60' => 'border-outline-variant bg-secondary-container',
    'border-violet-100 bg-violet-50' => 'border-outline-variant bg-secondary-container',
    'bg-violet-50/90' => 'bg-secondary-container',
    'text-violet-950' => 'text-on-surface',
    'text-violet-900' => 'text-primary',
    'text-violet-800' => 'text-primary',
    'text-violet-700' => 'text-primary',
    'divide-violet-100' => 'divide-outline-variant/20',
    'divide-violet-50' => 'divide-outline-variant/10',
    'border-violet-100/80' => 'border-outline-variant/40',
    'hover:bg-violet-100' => 'hover:bg-primary-fixed/40',
    'text-xs font-medium text-violet-800 underline decoration-violet-300 hover:text-violet-950' => 'text-xs font-semibold text-primary underline decoration-primary/30',
    'has-[:checked]:border-violet-600 has-[:checked]:bg-violet-50/50' => 'has-[:checked]:border-primary has-[:checked]:bg-secondary-container',
    'has-[:checked]:ring-violet-600' => 'has-[:checked]:ring-primary',
    'text-violet-700 focus:ring-violet-500' => 'text-primary focus:ring-primary/20',
    'rounded-lg border border-zinc-200 bg-white p-5 shadow-sm' => 'card-depth p-5',
    'rounded-lg border border-zinc-200 bg-white shadow-sm' => 'card-depth',
    'focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500' => 'focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20',
    'focus:ring-zinc-500' => 'focus:ring-primary/20',
    'text-zinc-300' => 'text-on-surface-variant/50',
    'bg-rose-100 text-[10px] text-rose-900' => 'bg-error-container text-[10px] text-on-error-container',
    'bg-rose-50 px-1.5' => 'bg-error-container/50 px-1.5',
    'text-red-600' => 'text-error',
    'hover:file:bg-primary' => 'hover:file:opacity-90',
    'file:bg-primary' => 'accent-gradient file:border-0',
    'bg-white' => 'bg-surface-container-lowest',
    'sticky left-0 z-10 border-r border-outline-variant/40 bg-surface-container-low' => 'sticky-col-header border-r border-outline-variant/40',
    'sticky left-0 z-10 border-r border-outline-variant/40 bg-surface-container-lowest' => 'sticky-col border-r border-outline-variant/40',
    'divide-y divide-zinc-200' => 'divide-y divide-outline-variant/30',
    'divide-y divide-zinc-100' => 'divide-y divide-outline-variant/20',
    'min-w-max divide-y divide-zinc-200' => 'min-w-max divide-y divide-outline-variant/30',
];

$skip = ['components/ui', 'layouts/partials', 'layouts/app.blade.php', 'welcome.blade.php'];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__.'/../resources/views')
);

foreach ($iterator as $file) {
    if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
        continue;
    }
    $path = str_replace('\\', '/', $file->getPathname());
    foreach ($skip as $part) {
        if (str_contains($path, $part)) {
            continue 2;
        }
    }
    $content = file_get_contents($path);
    foreach ($replacements as $from => $to) {
        $content = str_replace($from, $to, $content);
    }
    file_put_contents($path, $content);
}

echo "Migration complete.\n";
