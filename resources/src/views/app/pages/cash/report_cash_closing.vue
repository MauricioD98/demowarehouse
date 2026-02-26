<template>
  <div class="main-content">
    <breadcumb :page="$t('cash_report') || 'Informe de Caja'" :folder="$t('Cashs')"/>

    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>
    <b-card class="wrapper" v-if="!isLoading">
      <div slot="table-actions" class="mt-2 mb-3 d-flex flex-wrap align-items-center gap-2">
        <b-form-group :label="$t('Cashs')" class="mb-0">
          <v-select
            v-model="cash_id"
            :reduce="label => label.value"
            :placeholder="$t('Choose_Cash')"
            :options="cashs.map(c => ({ label: c.name, value: c.id }))"
            @input="Get_products_report(1)"
          />
        </b-form-group>
      </div>
      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="totalRows"
        :rows="products"
        @on-page-change="onPageChange"
        @on-per-page-change="onPerPageChange"
        @on-search="onSearch"
        :search-options="{ enabled: true, placeholder: $t('Search_this_table') }"
        :pagination-options="{ enabled: true, mode: 'records', nextLabel: 'next', prevLabel: 'prev' }"
        styleClass="table-hover tableOne vgt-table"
      >
        <template slot="table-row" slot-scope="props">
          <span v-if="props.column.field == 'actions'">
            <b-button @click="openDetailModal(props.row)" variant="primary" size="sm">
              {{ $t('Detail_Report') || 'Ver Detalle' }}
            </b-button>
          </span>
          <span v-else-if="props.column.field == 'cash_state'">
            <span :class="props.row.cash_state == 1 ? 'badge badge-success' : 'badge badge-info'">
              {{ props.row.cash_state == 1 ? ($t('Started') || 'Abierta') : ($t('Closing') || 'Cerrada') }}
            </span>
          </span>
          <span v-else-if="props.column.field == 'state'">
            <span :class="props.row.state == 1 ? 'badge badge-success' : 'badge badge-danger'">
              {{ props.row.state == 1 ? ($t('Active') || 'Activo') : ($t('Deleted') || 'Eliminado') }}
            </span>
          </span>
          <span v-else-if="['opening_amount','closing_amount','total_cash'].includes(props.column.field)">
            {{ formatMoney(props.row[props.column.field]) }}
          </span>
          <span v-else-if="['opening_date','closing_date'].includes(props.column.field)">
            {{ formatDate(props.row[props.column.field]) }}
          </span>
          <span v-else>{{ props.formattedRow[props.column.field] }}</span>
        </template>
      </vue-good-table>
    </b-card>

    <b-modal v-model="showDetailModal" :title="$t('Cash_Detail_Report') || 'Detalle de Caja'" size="xl" hide-footer>
      <div v-if="isLoadingDetail" class="text-center py-4">
        <div class="spinner-border text-primary"></div>
        <p class="mt-2">{{ $t('Loading_Detail') || 'Cargando...' }}</p>
      </div>
      <div v-if="!isLoadingDetail && cashInfo.id">
        <b-card class="mb-3">
          <h6><i class="i-Cash mr-2"></i> {{ $t('Cash_Information') || 'Información de Caja' }}</h6>
          <b-row>
            <b-col md="4"><strong>{{ $t('CashName') || 'Caja' }}:</strong> {{ cashInfo.cash ? cashInfo.cash.name : '-' }}</b-col>
            <b-col md="4"><strong>{{ $t('CashCloseNumber') || 'Nro Cierre' }}:</strong> {{ cashInfo.cash_close_number }}</b-col>
            <b-col md="4"><strong>{{ $t('Opening_Date') || 'Apertura' }}:</strong> {{ formatDate(cashInfo.opening_date) }}</b-col>
            <b-col md="4"><strong>{{ $t('Closing_Date') || 'Cierre' }}:</strong> {{ formatDate(cashInfo.closing_date) }}</b-col>
            <b-col md="4"><strong>{{ $t('OpeningAmount') || 'Monto Apertura' }}:</strong> {{ formatMoney(cashInfo.opening_amount) }}</b-col>
            <b-col md="4"><strong>{{ $t('TotalInflows') || 'Total Ingresos' }}:</strong> {{ formatMoney(cashInfo.total_inflow) }}</b-col>
            <b-col md="4"><strong>{{ $t('TotalOutflows') || 'Total Egresos' }}:</strong> {{ formatMoney(cashInfo.total_outflow) }}</b-col>
            <b-col md="4"><strong>{{ $t('ExpectedBalance') || 'Balance' }}:</strong> {{ formatMoney(cashInfo.closing_amount) }}</b-col>
          </b-row>
        </b-card>
        <h6 class="mt-3">{{ $t('Cash_inflows') || 'Entradas' }}</h6>
        <vue-good-table
          :columns="inflowColumns"
          :rows="detailInflows"
          :pagination-options="{ enabled: true, perPage: 5 }"
          styleClass="table-sm table-hover"
        />
        <h6 class="mt-4">{{ $t('Cash_outflows') || 'Egresos' }}</h6>
        <vue-good-table
          :columns="outflowColumns"
          :rows="detailOutflows"
          :pagination-options="{ enabled: true, perPage: 5 }"
          styleClass="table-sm table-hover"
        />
      </div>
    </b-modal>
  </div>
</template>

<script>
import NProgress from "nprogress";

export default {
  metaInfo: { title: "Informe de Caja" },
  data() {
    return {
      isLoading: true,
      isLoadingDetail: false,
      serverParams: { sort: { field: "id", type: "desc" }, page: 1, perPage: 10 },
      totalRows: "",
      search: "",
      limit: "10",
      products: [],
      cashs: [],
      cash_id: "",
      showDetailModal: false,
      cashInfo: {},
      detailInflows: [],
      detailOutflows: []
    };
  },
  computed: {
    columns() {
      return [
        { label: this.$t("CashCloseNumber") || "Nro", field: "cash_close_number", tdClass: "text-left", thClass: "text-left" },
        { label: this.$t("CashName") || "Caja", field: "cash_name", tdClass: "text-left", thClass: "text-left" },
        { label: this.$t("OpeningAmount") || "Apertura", field: "opening_amount", tdClass: "text-right", thClass: "text-right" },
        { label: this.$t("Closing_amount") || "Cierre", field: "closing_amount", tdClass: "text-right", thClass: "text-right" },
        { label: this.$t("TotalCash") || "Total Efectivo", field: "total_cash", tdClass: "text-right", thClass: "text-right" },
        { label: this.$t("State") || "Estado", field: "state", tdClass: "text-center", thClass: "text-center" },
        { label: this.$t("CashState") || "Caja", field: "cash_state", tdClass: "text-center", thClass: "text-center" },
        { label: this.$t("Action") || "Acción", field: "actions", html: true, tdClass: "text-center", thClass: "text-center", sortable: false }
      ];
    },
    inflowColumns() {
      return [
        { label: this.$t("Date"), field: "date" },
        { label: this.$t("Concept"), field: "concept" },
        { label: this.$t("Type"), field: "type_name" },
        { label: this.$t("Total_Amount"), field: "total_amount", formatFn: (v) => this.formatMoney(v) }
      ];
    },
    outflowColumns() {
      return [
        { label: this.$t("Date"), field: "date" },
        { label: this.$t("Concept"), field: "concept" },
        { label: this.$t("Type"), field: "type_name" },
        { label: this.$t("Total_Amount"), field: "total_amount", formatFn: (v) => this.formatMoney(v) }
      ];
    }
  },
  methods: {
    updateParams(newProps) { this.serverParams = Object.assign({}, this.serverParams, newProps); },
    onPageChange({ currentPage }) { if (this.serverParams.page !== currentPage) { this.updateParams({ page: currentPage }); this.Get_products_report(currentPage); } },
    onPerPageChange({ currentPerPage }) { if (this.limit !== currentPerPage) { this.limit = currentPerPage; this.updateParams({ page: 1, perPage: currentPerPage }); this.Get_products_report(1); } },
    onSearch(value) { this.search = value.searchTerm; this.Get_products_report(1); },
    formatMoney(val) { const n = parseFloat(val) || 0; return new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n); },
    formatDate(val) { if (!val) return "-"; const d = val.date ? new Date(val.date) : new Date(val); return d.toLocaleDateString(); },
    Get_products_report(page) {
      NProgress.start();
      const params = { page, SortField: this.serverParams.sort.field, SortType: this.serverParams.sort.type, search: this.search, limit: this.limit };
      if (this.cash_id) params.cash_id = this.cash_id;
      axios.get("report_cash_closing", { params })
        .then((r) => {
          this.products = r.data.products || [];
          this.totalRows = r.data.totalRows || 0;
          this.cashs = r.data.cashs || [];
          if (!this.cash_id && this.cashs.length) this.cash_id = this.cashs[0].id;
          NProgress.done();
          this.isLoading = false;
        })
        .catch(() => { NProgress.done(); this.isLoading = false; });
    },
    openDetailModal(row) {
      this.showDetailModal = true;
      this.isLoadingDetail = true;
      this.cashInfo = {};
      this.detailInflows = [];
      this.detailOutflows = [];
      axios.get("report_cash_closing/detail/" + row.id)
        .then((r) => {
          this.cashInfo = r.data.cashOpening || {};
          this.detailInflows = (r.data.inflows || []).map((i) => ({ ...i, date: i.date ? (i.date.date || i.date).toString().slice(0, 10) : "" }));
          this.detailOutflows = (r.data.outflows || []).map((o) => ({ ...o, date: o.date ? (o.date.date || o.date).toString().slice(0, 10) : "" }));
        })
        .catch(() => {})
        .finally(() => { this.isLoadingDetail = false; });
    }
  },
  created() {
    this.Get_products_report(1);
  }
};
</script>
