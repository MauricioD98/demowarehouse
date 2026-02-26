<template>
  <div class="main-content">
    <breadcumb :page="$t('CashClosing') || 'Cierre de Caja'" :folder="$t('Reports')"/>

    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

    <b-card class="wrapper" v-if="!isLoading && !errorMessage">
      <h5 class="mb-3">{{ $t('CashClosing') || 'Resumen de Caja' }}</h5>
      <b-row>
        <b-col md="6">
          <b-row>
            <b-col md="6" class="mb-3">
              <b-card class="text-center">
                <h6>{{ $t('OpeningAmount') || 'Apertura' }}</h6>
                <h4>{{ formatMoney(openingBalance) }}</h4>
              </b-card>
            </b-col>
            <b-col md="6" class="mb-3">
              <b-card class="text-center">
                <h6>{{ $t('TotalInflows') || 'Total Ingresos' }}</h6>
                <h4>{{ formatMoney(totalInflows) }}</h4>
              </b-card>
            </b-col>
            <b-col md="6" class="mb-3">
              <b-card class="text-center">
                <h6>{{ $t('TotalOutflows') || 'Total Egresos' }}</h6>
                <h4>{{ formatMoney(totalOutflows) }}</h4>
              </b-card>
            </b-col>
            <b-col md="6" class="mb-3">
              <b-card class="text-center">
                <h6>{{ $t('ExpectedBalance') || 'Balance Esperado' }}</h6>
                <h4>{{ formatMoney(expectedBalance) }}</h4>
              </b-card>
            </b-col>
          </b-row>
        </b-col>
        <b-col md="6" class="d-flex flex-column justify-content-center">
          <b-form-group :label="$t('CountedCash') || 'Efectivo contado'">
            <b-form-input v-model.number="closingForm.counted_cash" type="number" step="0.01" min="0" />
          </b-form-group>
          <b-button variant="primary" size="lg" @click="CloseCash" :disabled="closingBusy">
            <i class="i-Lock"></i> {{ $t('CloseCash') || 'Cerrar Caja' }}
          </b-button>
          <b-button variant="outline-secondary" class="mt-2" @click="$router.go(-1)">
            <i class="i-Arrow-Left"></i> {{ $t('back') }}
          </b-button>
        </b-col>
      </b-row>
    </b-card>

    <b-card v-if="errorMessage" class="wrapper">
      <b-alert variant="danger" show>{{ errorMessage }}</b-alert>
      <b-button @click="loadReport">{{ $t('Retry') || 'Reintentar' }}</b-button>
    </b-card>
  </div>
</template>

<script>
export default {
  metaInfo: { title: 'Cierre de Caja' },
  data() {
    return {
      isLoading: true,
      errorMessage: '',
      openingBalance: 0,
      totalInflows: 0,
      totalOutflows: 0,
      expectedBalance: 0,
      paymentTypeSummary: [],
      closingForm: { counted_cash: 0 },
      closingBusy: false,
      startDate: new Date().toISOString().slice(0, 10),
      endDate: new Date().toISOString().slice(0, 10),
    };
  },
  mounted() {
    this.loadReport();
  },
  methods: {
    async loadReport() {
      this.isLoading = true;
      this.errorMessage = '';
      try {
        const params = { from: this.startDate, to: this.endDate };
        const { data } = await axios.get('cash_closing/report', { params });
        this.openingBalance = data.openingBalance || 0;
        this.totalInflows = data.totalInflows || 0;
        this.totalOutflows = data.totalOutflows || 0;
        this.expectedBalance = data.expectedBalance || 0;
        this.closingForm.counted_cash = this.expectedBalance;
      } catch (e) {
        this.errorMessage = e.response?.data?.message || this.$t('Cash_not_initialized') || 'Caja no inicializada';
      } finally {
        this.isLoading = false;
      }
    },
    async CloseCash() {
      this.closingBusy = true;
      try {
        const typeCash = this.paymentTypeSummary.find(t => t.label === 'Cash') || { total: this.totalInflows };
        await axios.post('cash_closing', {
          total_inflows: this.totalInflows,
          total_outflows: this.totalOutflows,
          closing_balance: this.closingForm.counted_cash,
          type_cash_total: this.closingForm.counted_cash,
        });
        this.makeToast('success', this.$t('CashClosed') || 'Caja cerrada correctamente', this.$t('Success'));
        this.$router.push({ name: 'index_sales' });
      } catch (e) {
        this.makeToast('danger', e.response?.data?.message || this.$t('Failed'), this.$t('Failed'));
      } finally {
        this.closingBusy = false;
      }
    },
    formatMoney(val) {
      const n = parseFloat(val) || 0;
      return new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
    },
    makeToast(variant, msg, title) {
      this.$root.$bvToast.toast(msg, { title, variant, solid: true });
    },
  },
};
</script>
