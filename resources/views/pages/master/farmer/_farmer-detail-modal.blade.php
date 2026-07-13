<div x-data="farmerDetail()">
    <x-modal name="farmer-detail-modal" maxWidth="max-w-7xl">
        <div class="flex flex-col max-h-[80vh] -m-6 lg:-m-10">
            <!-- Header (Fixed) -->
            <div class="px-6 lg:px-10 pt-6 lg:pt-10 pb-0 mb-4">
                <h4 class="text-lg font-medium text-gray-800 dark:text-white/90" x-text="farmer?.name"></h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">Riwayat Mutasi Hutang</p>
            </div>

            <!-- Content (Scrollable) -->
            <div class="flex-1 overflow-y-auto px-6 lg:px-10">
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                    <x-data-table id="ledgerTable" :rows="[]" :columns="$ledgerColumns" :url="null" perPage="5">
                        <x-slot:ledgerDate>
                            <span class="text-xs text-gray-600 dark:text-gray-400"
                                x-text="new Date(item.created_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'})"></span>
                        </x-slot:ledgerDate>

                        <x-slot:reference>
                            <div class="flex flex-col">
                                <span class="text-sm font-medium text-gray-800 dark:text-white/90"
                                    x-text="
                                        item.reference?.batch_code ||
                                        (item.reference_type.includes('Loan') ? 'Pinjaman' : '') ||
                                        (item.reference_type.includes('FarmerPayment') ? 'Pembayaran Tunai' : '') ||
                                        'Panen'
                                    "></span>
                                <span class="text-[10px] text-gray-400 uppercase font-medium"
                                    x-text="item.reference_type.split('\\').pop()"></span>
                            </div>
                        </x-slot:reference>

                        <x-slot:typeBadge>
                            <div class="flex justify-center">
                                <span
                                    :class="item.type === 'debit' ? 'bg-red-50 text-red-700 ring-1 ring-red-600/20' :
                                        'bg-green-50 text-green-700 ring-1 ring-green-600/20'"
                                    class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold uppercase"
                                    x-text="item.type">
                                </span>
                            </div>
                        </x-slot:typeBadge>

                        <x-slot:ledgerAmount>
                            <div class="text-right text-sm font-semibold"
                                :class="item.type === 'debit' ? 'text-red-600' : 'text-green-600'"
                                x-text="(item.type === 'debit' ? '+' : '-') + ' ' + Number(item.amount).toLocaleString('id-ID')">
                            </div>
                        </x-slot:ledgerAmount>

                        <x-slot:ledgerBalance>
                            <div class="text-right text-sm font-bold text-gray-800 dark:text-white/90"
                                x-text="Number(item.balance_after).toLocaleString('id-ID')"></div>
                        </x-slot:ledgerBalance>
                    </x-data-table>
                </div>
            </div>

            <!-- Footer (Fixed) -->
            <div class="px-6 lg:px-10 pb-6 lg:pb-10 pt-0 mt-4 flex justify-end gap-3">
                <button type="button" @click="openPaymentModal()"
                    class="rounded-lg bg-green-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-green-700 transition-colors">
                    Bayar Hutang
                </button>
                <button type="button" @click="$dispatch('close-modal', 'farmer-detail-modal')"
                    class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                    Tutup
                </button>
            </div>
        </div>
    </x-modal>

    <x-modal name="farmer-pay-modal">
        <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90">Bayar Hutang Tunai</h4>
        <form @submit.prevent="submitPayment">
            <div class="space-y-4">
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/70">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Petani</span>
                        <span class="font-semibold text-gray-900 dark:text-white/90" x-text="farmer?.name || '-'"></span>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Sisa Hutang</span>
                        <span class="font-semibold text-red-600" x-text="'Rp ' + Number(farmer?.current_debt || 0).toLocaleString('id-ID')"></span>
                    </div>
                </div>

                <x-input label="Tanggal Bayar" name="payment_date" type="date" x-model="paymentForm.data.payment_date"
                    alpine-error="paymentForm.getError('payment_date')" required />

                <x-input label="Jumlah Bayar" name="amount" x-model="paymentForm.data.amount" :isCurrency="true"
                    alpine-error="paymentForm.getError('amount')" required />

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Keterangan</label>
                    <textarea x-model="paymentForm.data.notes"
                        class="block min-h-[80px] w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        placeholder="Opsional"></textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="$dispatch('close-modal', 'farmer-pay-modal')"
                    class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">Batal</button>
                <button type="submit" :disabled="paymentForm.processing"
                    class="rounded-lg bg-green-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-green-700 transition-colors">
                    <span x-show="!paymentForm.processing">Simpan Pembayaran</span>
                    <span x-show="paymentForm.processing">Menyimpan...</span>
                </button>
            </div>
        </form>
    </x-modal>
</div>

@push('scripts')
    <script>
        function farmerDetail() {
            return {
                farmer: null,

                paymentForm: useForm({
                    farmer_id: null,
                    payment_date: window.getLocalDateInputValue(),
                    amount: 0,
                    notes: '',
                }),

                init() {
                    window.addEventListener('detail-farmer', (event) => {
                        this.open(event.detail);
                    });
                },

                async open(farmer) {
                    this.farmer = farmer;
                    const url = `{{ url('manajemen-petani') }}/${farmer.id}/ledgers`;

                    this.$dispatch('open-modal', 'farmer-detail-modal');

                    setTimeout(() => {
                        const instances = window.getDataTableInstances();
                        const tableInstance = instances['ledgerTable'];
                        if (tableInstance) {
                            tableInstance.url = url;
                            tableInstance.refreshData();
                        }
                    }, 50);
                },

                openPaymentModal() {
                    this.paymentForm.reset();
                    this.paymentForm.clearErrors();
                    this.paymentForm.data.farmer_id = this.farmer?.id;
                    this.paymentForm.data.payment_date = window.getLocalDateInputValue();
                    this.paymentForm.data.amount = 0;
                    this.paymentForm.data.notes = '';
                    this.$dispatch('open-modal', 'farmer-pay-modal');
                },

                async submitPayment() {
                    await this.paymentForm.submit('post', '{{ route("farmer.pay") }}', {
                        onSuccess: (res) => {
                            toast.success('Berhasil', res.data.message);
                            this.$dispatch('close-modal', 'farmer-pay-modal');

                            // Refresh farmer data and ledger table
                            this.refreshFarmerData();
                        },
                        onError: (err) => {
                            if (err.response?.status !== 422) {
                                toast.error('Gagal', err.response?.data?.message || 'Terjadi kesalahan sistem');
                            }
                        }
                    });
                },

                async refreshFarmerData() {
                    const url = `{{ url('manajemen-petani') }}/${this.farmer.id}`;
                    try {
                        const response = await fetch(url);
                        if (response.ok) {
                            const data = await response.json();
                            if (data.data) {
                                this.farmer.current_debt = data.data.current_debt;
                            }
                        }
                    } catch (e) {
                        console.error('Failed to refresh farmer data', e);
                    }

                    const instances = window.getDataTableInstances();
                    const tableInstance = instances['ledgerTable'];
                    if (tableInstance) {
                        tableInstance.url = `{{ url('manajemen-petani') }}/${this.farmer.id}/ledgers`;
                        tableInstance.refreshData();
                    }

                    refreshDataTable('farmerTable');
                }
            }
        }
    </script>
@endpush
