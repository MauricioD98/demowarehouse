<template>
  <div class="main-content">
    <breadcumb :page="$t('Cash_outflows')" :folder="$t('Cashs')"/>

    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>
    <b-card class="wrapper" v-if="!isLoading">
      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="totalRows"
        :rows="cash_outflows"
        @on-page-change="onPageChange"
        @on-per-page-change="onPerPageChange"
        @on-sort-change="onSortChange"
        @on-search="onSearch"
        :search-options="{ enabled: true, placeholder: $t('Search_this_table') }"
        :select-options="{ enabled: true, clearSelectionText: '' }"
        @on-selected-rows-change="selectionChanged"
        :pagination-options="{ enabled: true, mode: 'records', nextLabel: 'next', prevLabel: 'prev' }"
        styleClass="table-hover tableOne vgt-table"
      >
        <div slot="selected-row-actions">
          <button class="btn btn-danger btn-sm" @click="delete_by_selected()">{{ $t('Del') }}</button>
        </div>
        <div slot="table-actions" class="mt-2 mb-3">
          <b-button @click="New_cash()" class="btn-rounded" variant="btn btn-primary btn-icon m-1">
            <i class="i-Add"></i> {{ $t('Add') }}
          </b-button>
        </div>
        <template slot="table-row" slot-scope="props">
          <span v-if="props.column.field == 'actions'">
            <a @click="Edit_cash(props.row)" title="Edit" v-b-tooltip.hover v-if="props.row.record_type === 'MANUAL'">
              <i class="i-Edit text-25 text-success"></i>
            </a>
            <a title="Delete" v-b-tooltip.hover @click="Remove_Cash_outflows(props.row.id)" v-if="props.row.record_type === 'MANUAL'">
              <i class="i-Close-Window text-25 text-danger"></i>
            </a>
          </span>
        </template>
      </vue-good-table>
    </b-card>

    <validation-observer ref="Create_Cash_outflows">
      <b-modal hide-footer size="md" id="New_Cash_outflows" :title="editmode ? $t('Edit') : $t('Add')">
        <b-form @submit.prevent="Submit_Cash_outflows">
          <b-row>
            <b-col md="12">
              <validation-provider name="Date" :rules="{ required: true }" v-slot="validationContext">
                <b-form-group :label="$t('Date') + ' *'">
                  <b-form-input type="date" :state="getValidationState(validationContext)" v-model="cash_outflow.date" />
                  <b-form-invalid-feedback>{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>
            <b-col md="12">
              <validation-provider name="Concept" :rules="{ required: true }" v-slot="validationContext">
                <b-form-group :label="$t('Concept') + ' *'">
                  <b-form-textarea :placeholder="$t('Enter_concept')" :state="getValidationState(validationContext)" v-model="cash_outflow.concept" />
                  <b-form-invalid-feedback>{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>
            <b-col md="12">
              <validation-provider name="Total Amount" :rules="{ required: true }" v-slot="validationContext">
                <b-form-group :label="$t('Total_Amount') + ' *'">
                  <b-form-input type="number" step="0.01" :state="getValidationState(validationContext)" v-model="cash_outflow.total_amount" />
                  <b-form-invalid-feedback>{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>
            <b-col md="12">
              <validation-provider name="Type Cash Outflow" :rules="{ required: true }">
                <b-form-group slot-scope="{ valid, errors }" :label="$t('Type_Cash_Outflow') + ' *'">
                  <v-select
                    :class="{ 'is-invalid': !!errors.length }"
                    :state="errors[0] ? false : (valid ? true : null)"
                    v-model="cash_outflow.type_cash_outflow_id"
                    :reduce="label => label.value"
                    :placeholder="$t('Choose_Type_Cash_Outflow')"
                    :options="typeCashOutflows.map(row => ({ label: row.name, value: row.id }))"
                  />
                  <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>
            <b-col md="12" class="mt-3">
              <b-button variant="primary" type="submit" :disabled="SubmitProcessing">
                <i class="i-Yes me-2 font-weight-bold"></i> {{ $t('submit') }}
              </b-button>
              <div v-once class="typo__p" v-if="SubmitProcessing">
                <div class="spinner sm spinner-primary mt-3"></div>
              </div>
            </b-col>
          </b-row>
        </b-form>
      </b-modal>
    </validation-observer>
  </div>
</template>

<script>
import NProgress from "nprogress";

export default {
  metaInfo: { title: "Cash_outflows" },
  data() {
    return {
      isLoading: true,
      SubmitProcessing: false,
      serverParams: { columnFilters: {}, sort: { field: "id", type: "desc" }, page: 1, perPage: 10 },
      selectedIds: [],
      totalRows: "",
      search: "",
      limit: "10",
      cash_outflows: [],
      editmode: false,
      cash_outflow: {
        id: "",
        date: "",
        concept: "",
        total_amount: 0,
        record_type: "MANUAL",
        type_cash_outflow_id: null
      },
      typeCashOutflows: []
    };
  },
  computed: {
    columns() {
      return [
        { label: this.$t("Code"), field: "outflow_num", tdClass: "text-left", thClass: "text-left" },
        { label: this.$t("Concept"), field: "concept", tdClass: "text-left", thClass: "text-left" },
        { label: this.$t("Date"), field: "date", tdClass: "text-left", thClass: "text-left", formatFn: (v) => (v ? (v.date || v).toString().slice(0, 10) : '-') },
        { label: this.$t("Total_Amount"), field: "total_amount", tdClass: "text-left", thClass: "text-left", formatFn: (v) => parseFloat(v || 0).toFixed(2) },
        { label: this.$t("State"), field: "state", tdClass: "text-left", thClass: "text-left", formatFn: (v) => (v === 1 ? 'Active' : 'Inactive') },
        { label: this.$t("Action"), field: "actions", html: true, tdClass: "text-right", thClass: "text-right", sortable: false }
      ];
    }
  },
  methods: {
    updateParams(newProps) { this.serverParams = Object.assign({}, this.serverParams, newProps); },
    onPageChange({ currentPage }) {
      if (this.serverParams.page !== currentPage) {
        this.updateParams({ page: currentPage });
        this.Get_Cash_outflows(currentPage);
      }
    },
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_Cash_outflows(1);
      }
    },
    selectionChanged({ selectedRows }) { this.selectedIds = selectedRows.map(r => r.id); },
    onSortChange(params) {
      this.updateParams({ sort: { type: params[0].type, field: params[0].field } });
      this.Get_Cash_outflows(this.serverParams.page);
    },
    onSearch(value) { this.search = value.searchTerm; this.Get_Cash_outflows(this.serverParams.page); },
    getValidationState({ dirty, validated, valid = null }) { return dirty || validated ? valid : null; },
    Submit_Cash_outflows() {
      this.$refs.Create_Cash_outflows.validate().then((success) => {
        if (!success) this.makeToast("danger", this.$t("Please_fill_the_form_correctly"), this.$t("Failed"));
        else if (!this.editmode) this.Create_Cash_outflows();
        else this.Update_Cash_outflows();
      });
    },
    makeToast(variant, msg, title) { this.$root.$bvToast.toast(msg, { title, variant, solid: true }); },
    New_cash() { this.reset_Form(); this.editmode = false; this.cash_outflow.record_type = "MANUAL"; this.$bvModal.show("New_Cash_outflows"); },
    Edit_cash(row) {
      this.reset_Form();
      this.cash_outflow = {
        id: row.id,
        date: row.date ? (row.date.date || row.date).toString().slice(0, 10) : "",
        concept: row.concept,
        total_amount: parseFloat(row.total_amount),
        record_type: row.record_type || "MANUAL",
        type_cash_outflow_id: row.type_cash_outflow_id
      };
      this.editmode = true;
      this.$bvModal.show("New_Cash_outflows");
    },
    Get_Cash_outflows(page) {
      NProgress.start();
      axios.get("cash_outflow?page=" + page + "&SortField=" + this.serverParams.sort.field + "&SortType=" + this.serverParams.sort.type + "&search=" + this.search + "&limit=" + this.limit)
        .then((r) => {
          this.cash_outflows = r.data.cash_outflows || [];
          this.totalRows = r.data.totalRows || 0;
          NProgress.done();
          this.isLoading = false;
        })
        .catch(() => { NProgress.done(); this.isLoading = false; });
    },
    Create_Cash_outflows() {
      this.SubmitProcessing = true;
      axios.post("cash_outflow", this.cash_outflow).then(() => {
        this.SubmitProcessing = false;
        Fire.$emit("Event_Cash_outflows");
        this.makeToast("success", this.$t("Create.TitleCat"), this.$t("Success"));
      }).catch((e) => {
        this.SubmitProcessing = false;
        const msg = (e.response && e.response.data && e.response.data.message) || this.$t("InvalidData");
        this.makeToast("danger", msg, this.$t("Failed"));
        if (msg && (msg.includes('initialized') || msg.includes('inicializada'))) window.location.reload();
      });
    },
    Update_Cash_outflows() {
      this.SubmitProcessing = true;
      axios.put("cash_outflow/" + this.cash_outflow.id, this.cash_outflow).then(() => {
        this.SubmitProcessing = false;
        Fire.$emit("Event_Cash_outflows");
        this.makeToast("success", this.$t("Update.TitleCat"), this.$t("Success"));
      }).catch((e) => {
        this.SubmitProcessing = false;
        this.makeToast("danger", (e.response && e.response.data && e.response.data.message) || this.$t("InvalidData"), this.$t("Failed"));
      });
    },
    reset_Form() {
      this.cash_outflow = { id: "", date: "", concept: "", total_amount: 0, record_type: "MANUAL", type_cash_outflow_id: null };
    },
    Remove_Cash_outflows(id) {
      this.$swal({ title: this.$t("Delete.Title"), text: this.$t("Delete.Text"), type: "warning", showCancelButton: true, confirmButtonColor: "#3085d6", cancelButtonColor: "#d33", cancelButtonText: this.$t("Delete.cancelButtonText"), confirmButtonText: this.$t("Delete.confirmButtonText") }).then((result) => {
        if (result.value) {
          axios.delete("cash_outflow/" + id).then(() => {
            this.$swal(this.$t("Delete.Deleted"), this.$t("Delete.CatDeleted"), "success");
            Fire.$emit("Delete_Cash_outflows");
          }).catch((e) => this.$swal(this.$t("Delete.Failed"), (e.response && e.response.data && e.response.data.message) || this.$t("Delete.Therewassomethingwronge"), "warning"));
        }
      });
    },
    delete_by_selected() {
      if (!this.selectedIds.length) return;
      this.$swal({ title: this.$t("Delete.Title"), text: this.$t("Delete.Text"), type: "warning", showCancelButton: true, confirmButtonColor: "#3085d6", cancelButtonColor: "#d33", cancelButtonText: this.$t("Delete.cancelButtonText"), confirmButtonText: this.$t("Delete.confirmButtonText") }).then((result) => {
        if (result.value) {
          NProgress.start();
          Promise.all(this.selectedIds.map((id) => axios.delete("cash_outflow/" + id)))
            .then(() => { this.$swal(this.$t("Delete.Deleted"), this.$t("Delete.CatDeleted"), "success"); Fire.$emit("Delete_Cash_outflows"); })
            .catch(() => this.$swal(this.$t("Delete.Failed"), this.$t("Delete.Therewassomethingwronge"), "warning"))
            .finally(() => NProgress.done());
        }
      });
    },
    GetTypeCashOutflows() {
      axios.get("type_cash_outflow/get_list").then((r) => { this.typeCashOutflows = r.data || []; }).catch(() => {});
    }
  },
  created() {
    this.GetTypeCashOutflows();
    this.Get_Cash_outflows(1);
    Fire.$on("Event_Cash_outflows", () => { setTimeout(() => { this.Get_Cash_outflows(this.serverParams.page); this.$bvModal.hide("New_Cash_outflows"); }, 500); });
    Fire.$on("Delete_Cash_outflows", () => { setTimeout(() => this.Get_Cash_outflows(this.serverParams.page), 500); });
  }
};
</script>
