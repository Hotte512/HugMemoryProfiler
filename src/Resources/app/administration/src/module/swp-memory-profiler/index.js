import './page/swp-memory-profiler-index';
import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const { Module } = Shopware;

Module.register('swp-memory-profiler', {
    type: 'plugin',
    name: 'SwpMemoryProfiler',
    title: 'swp-memory-profiler.general.mainMenuItemTitle',
    description: 'swp-memory-profiler.general.descriptionTextModule',
    color: '#9AA8B5',
    icon: 'regular-chart-bar',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB,
    },

    routes: {
        index: {
            component: 'swp-memory-profiler-index',
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index.system',
            },
        },
    },

    settingsItem: [{
        group: 'system',
        to: 'swp.memory.profiler.index',
        icon: 'regular-chart-bar',
        name: 'swp-memory-profiler',
        label: 'swp-memory-profiler.general.mainMenuItemTitle',
    }],
});
