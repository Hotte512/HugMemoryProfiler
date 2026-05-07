import template from './swp-memory-profiler-index.html.twig';
import './swp-memory-profiler-index.scss';

const { Component } = Shopware;

Component.register('swp-memory-profiler-index', {
    template,

    inject: ['acl'],

    data() {
        return {
            isLoading: false,
            stats: null,
            entries: [],
            total: 0,

            filter: {
                context: '',
                status: '',
                route: '',
                hours: 24,
                minPeak: '',
            },

            page: 1,
            limit: 50,
            sortBy: 'peak_memory_mb',
            sortDirection: 'DESC',

            showClearModal: false,
            clearOlderThan: 30,
        };
    },

    computed: {
        httpClient() {
            return Shopware.Application.getContainer('init').httpClient;
        },

        authHeaders() {
            const token = Shopware.Service('loginService').getToken();
            return { Authorization: `Bearer ${token}` };
        },

        columns() {
            return [
                { property: 'created_at', label: 'Zeit', allowResize: true, primary: true, sortable: true },
                { property: 'context', label: 'Kontext', allowResize: true },
                { property: 'method', label: 'Method', allowResize: true },
                { property: 'status_code', label: 'Status', allowResize: true, sortable: true },
                { property: 'peak_memory_mb', label: 'Peak (MB)', allowResize: true, sortable: true },
                { property: 'delta_memory_mb', label: 'Delta (MB)', allowResize: true },
                { property: 'duration_ms', label: 'Dauer (ms)', allowResize: true, sortable: true },
                { property: 'path', label: 'Route', allowResize: true },
            ];
        },

        contextOptions() {
            return [
                { label: 'Alle', value: '' },
                { label: 'Storefront', value: 'STORE' },
                { label: 'API', value: 'API' },
                { label: 'Admin', value: 'ADMIN' },
            ];
        },

        timeRangeOptions() {
            return [
                { label: 'Letzte Stunde', value: 1 },
                { label: 'Letzte 24h', value: 24 },
                { label: 'Letzte 7 Tage', value: 168 },
                { label: 'Letzte 30 Tage', value: 720 },
                { label: 'Alle', value: '' },
            ];
        },
    },

    created() {
        this.loadData();
    },

    watch: {
        filter: {
            handler() {
                this.page = 1;
                this.loadData();
            },
            deep: true,
        },
    },

    methods: {
        buildQuery() {
            const q = new URLSearchParams();
            if (this.filter.context) q.append('context', this.filter.context);
            if (this.filter.status) q.append('status', this.filter.status);
            if (this.filter.route) q.append('route', this.filter.route);
            if (this.filter.hours !== '' && this.filter.hours !== null) q.append('hours', this.filter.hours);
            if (this.filter.minPeak) q.append('minPeak', this.filter.minPeak);
            return q;
        },

        async loadData() {
            this.isLoading = true;
            try {
                await Promise.all([this.loadStats(), this.loadEntries()]);
            } catch (e) {
                this.createNotificationError({ message: 'Fehler beim Laden: ' + e.message });
            } finally {
                this.isLoading = false;
            }
        },

        async loadStats() {
            const q = this.buildQuery();
            const resp = await this.httpClient.get(`/_action/swp-memory-profiler/stats?${q}`, {
                headers: this.authHeaders,
            });
            this.stats = resp.data;
        },

        async loadEntries() {
            const q = this.buildQuery();
            q.append('limit', this.limit);
            q.append('offset', (this.page - 1) * this.limit);
            q.append('sort', this.sortBy);
            q.append('order', this.sortDirection);

            const resp = await this.httpClient.get(`/_action/swp-memory-profiler/list?${q}`, {
                headers: this.authHeaders,
            });
            this.entries = resp.data.data;
            this.total = resp.data.total;
        },

        onSortColumn(column) {
            const prop = column.dataIndex || column.property;
            if (this.sortBy === prop) {
                this.sortDirection = this.sortDirection === 'ASC' ? 'DESC' : 'ASC';
            } else {
                this.sortBy = prop;
                this.sortDirection = 'DESC';
            }
            this.loadEntries();
        },

        onPageChange({ page, limit }) {
            this.page = page;
            this.limit = limit;
            this.loadEntries();
        },

        openClearModal() {
            this.showClearModal = true;
        },

        async onClearConfirm() {
            this.isLoading = true;
            try {
                const formData = new FormData();
                if (this.clearOlderThan) {
                    formData.append('olderThanDays', this.clearOlderThan);
                }
                const resp = await this.httpClient.post('/_action/swp-memory-profiler/clear', formData, {
                    headers: this.authHeaders,
                });
                this.createNotificationSuccess({ message: `${resp.data.deleted} Einträge gelöscht.` });
                this.showClearModal = false;
                this.loadData();
            } catch (e) {
                this.createNotificationError({ message: 'Fehler: ' + e.message });
            } finally {
                this.isLoading = false;
            }
        },

        rowClass(row) {
            if (row.peak_memory_mb >= 200) return 'is--critical';
            if (row.peak_memory_mb >= 100) return 'is--warning';
            return '';
        },
    },
});
