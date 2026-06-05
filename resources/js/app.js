import './bootstrap';

import Alpine from 'alpinejs';

window.loadApexCharts = () => {
    if (window.ApexCharts) {
        return Promise.resolve(window.ApexCharts);
    }

    return import('apexcharts').then(({ default: ApexCharts }) => {
        window.ApexCharts = ApexCharts;

        return ApexCharts;
    });
};

window.Alpine = Alpine;

Alpine.start();
