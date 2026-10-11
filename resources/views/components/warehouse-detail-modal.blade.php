<div x-data="{
    show: false, loading: false, html: '', error: '', requestId: 0, trigger: null,
    close() { this.show = false; this.requestId++; this.trigger?.focus(); },
    trap(event) {
        const items = [...this.$el.querySelectorAll('button, a[href], input, select, textarea, [tabindex]')].filter(el => !el.disabled && el.tabIndex >= 0 && el.getClientRects().length);
        const first = items[0], last = items[items.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    },
    async open(url) {
        this.trigger = document.activeElement;
        this.show = true; this.loading = true; this.html = ''; this.error = '';
        const id = ++this.requestId;
        this.$nextTick(() => this.$refs.close.focus());
        try {
            const response = await fetch(url, { headers: { 'X-Warehouse-Detail': 'modal' } });
            if (!response.ok || response.redirected) throw new Error('load');
            const html = await response.text();
            if (id === this.requestId) this.html = html;
        } catch (error) {
            if (id === this.requestId) this.error = 'Detail gagal dimuat. Tutup modal lalu coba lagi.';
        } finally { if (id === this.requestId) this.loading = false; }
    }
}" @warehouse-detail.window="open($event.detail.url)" @keydown.escape.window="if(show) close()"
    x-show="show" x-cloak class="fixed inset-0 z-9999 flex items-center justify-center p-4"
    role="dialog" aria-modal="true" aria-label="Detail barang dan proses" @keydown.tab="trap($event)">
    <div class="absolute inset-0 bg-gray-900/50" @click="close()"></div>
    <div class="relative max-h-[90vh] w-full max-w-5xl overflow-y-auto rounded-xl bg-white p-5 shadow-xl dark:bg-gray-900">
        <div class="mb-4 flex items-center justify-between gap-3 text-gray-800 dark:text-gray-200">
            <h2 class="text-lg font-semibold">Detail</h2>
            <button type="button" x-ref="close" @click="close()" class="rounded-lg border px-4 py-2">Tutup</button>
        </div>
        <p x-show="loading" role="status" class="p-5 text-gray-500">Memuat detail...</p>
        <p x-show="error" x-text="error" role="alert" class="p-5 text-red-600"></p>
        <div x-html="html"></div>
    </div>
</div>
