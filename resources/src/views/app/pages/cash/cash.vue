<template>
  <div class="main-content">
    <breadcumb :page="$t('Cashs')" :folder="$t('Cashs')"/>

    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>
    <b-card class="wrapper" v-if="!isLoading">
      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="totalRows"
        :rows="cashs"
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
            <a @click="Edit_cash(props.row)" title="Edit" v-b-tooltip.hover>
              <i class="i-Edit text-25 text-success"></i>
            </a>
            <a title="Delete" v-b-tooltip.hover @click="Remove_Cash(props.row.id)">
              <i class="i-Close-Window text-25 text-danger"></i>
            </a>
          </span>
        </template>
      </vue-good-table>
    </b-card>

    <validation-observer ref="Create_Cash">
      <b-modal hide-footer size="md" id="New_Cash" :title="editmode ? $t('Edit') : $t('Add')">
        <b-form @submit.prevent="Submit_Cash">
          <b-row>
            <b-col md="12">
              <validation-provider name="Name" :rules="{ required: true }" v-slot="validationContext">
                <b-form-group :label="$t('Name') + ' *'">
                  <b-form-input
                    :placeholder="$t('Enter_name_cash')"
                    :state="getValidationState(validationContext)"
                    v-model="cash.name"
                  />
                  <b-form-invalid-feedback>{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>
            <b-col md="12">
              <validation-provider name="Description" v-slot="validationContext">
                <b-form-group :label="$t('Description')">
                  <b-form-textarea :placeholder="$t('Enter_description')" :state="getValidationState(validationContext)" v-model="cash.description" />
                </b-form-group>
              </validation-provider>
            </b-col>
            <b-col md="12">
              <validation-provider name="State" :rules="{ required: true }" v-slot="validationContext">
                <b-form-group :label="$t('State') + ' *'">
                  <b-form-select
                    :options="[
                      { value: 0, text: 'Inactive' },
                      { value: 1, text: 'Active' }
                    ]"
                    :state="getValidationState(validationContext)"
                    v-model="cash.state"
                  />
                </b-form-group>
              </validation-provider>
            </b-col>
            <b-col md="12">
              <validation-provider name="Warehouse" :rules="{ required: true }">
                <b-form-group slot-scope="{ valid, errors }" :label="$t('warehouse') + ' *'">
                  <v-select
                    :class="{ 'is-invalid': !!errors.length }"
                    :state="errors[0] ? false : (valid ? true : null)"
                    v-model="cash.warehouse_id"
                    :reduce="(label) => label.value"
                    :placeholder="$t('Choose_Warehouse')"
                    :options="warehouses.map((row) => ({ label: row.name, value: row.id }))"
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
  metaInfo: { title: "Cash" },
  data() {
    return {
      isLoading: true,
      SubmitProcessing: false,
      serverParams: { columnFilters: {}, sort: { field: "id", type: "desc" }, page: 1, perPage: 10 },
      selectedIds: [],
      totalRows: "",
      search: "",
      limit: "10",
      cashs: [],
      editmode: false,
      cash: { id: "", code: "", name: "", description: "", state: 1, warehouse_id: null },
      warehouses: []
    };
  },
  computed: {
    columns() {
      return [
        { label: this.$t("Code"), field: "code", tdClass: "text-left", thClass: "text-left" },
        { label: this.$t("Name"), field: "name", tdClass: "text-left", thClass: "text-left" },
        { label: this.$t("warehouse"), field: "warehouse", tdClass: "text-left", thClass: "text-left", formatFn: (v, r) => (r.warehouse ? r.warehouse.name : "-") },
        { label: this.$t("State"), field: "state", tdClass: "text-left", thClass: "text-left", formatFn: (v) => (v === 1 ? "Active" : "Inactive") },
        { label: this.$t("Action"), field: "actions", html: true, tdClass: "text-right", thClass: "text-right", sortable: false }
      ];
    }
  },
  methods: {
    updateParams(newProps) { this.serverParams = Object.assign({}, this.serverParams, newProps); },
    onPageChange({ currentPage }) {
      if (this.serverParams.page !== currentPage) {
        this.updateParams({ page: currentPage });
        this.Get_Cash(currentPage);
      }
    },
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_Cash(1);
      }
    },
    selectionChanged({ selectedRows }) { this.selectedIds = selectedRows.map((r) => r.id); },
    onSortChange(params) {
      this.updateParams({ sort: { type: params[0].type, field: params[0].field } });
      this.Get_Cash(this.serverParams.page);
    },
    onSearch(value) { this.search = value.searchTerm; this.Get_Cash(this.serverParams.page); },
    getValidationState({ dirty, validated, valid = null }) { return dirty || validated ? valid : null; },
    Submit_Cash() {
      this.$refs.Create_Cash.validate().then((success) => {
        if (!success) this.makeToast("danger", this.$t("Please_fill_the_form_correctly"), this.$t("Failed"));
        else if (!this.editmode) this.Create_Cash();
        else this.Update_Cash();
      });
    },
    makeToast(variant, msg, title) { this.$root.$bvToast.toast(msg, { title, variant, solid: true }); },
    New_cash() { this.reset_Form(); this.editmode = false; this.$bvModal.show("New_Cash"); },
    Edit_cash(cash) { this.reset_Form(); this.cash = { ...cash, warehouse_id: cash.warehouse_id }; this.editmode = true; this.$bvModal.show("New_Cash"); },
    Get_Cash(page) {
      NProgress.start();
      axios
        .get("cash?page=" + page + "&SortField=" + this.serverParams.sort.field + "&SortType=" + this.serverParams.sort.type + "&search=" + this.search + "&limit=" + this.limit)
        .then((r) => {
          this.cashs = r.data.cashs;
          this.totalRows = r.data.totalRows;
          this.warehouses = r.data.warehouses || [];
          NProgress.done();
          this.isLoading = false;
        })
        .catch(() => { NProgress.done(); this.isLoading = false; });
    },
    Create_Cash() {
      this.SubmitProcessing = true;
      axios.post("cash", this.cash).then(() => {
        this.SubmitProcessing = false;
        Fire.$emit("Event_Cash");
        this.makeToast("success", this.$t("Create.TitleCat"), this.$t("Success"));
      }).catch(() => { this.SubmitProcessing = false; this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed")); });
    },
    Update_Cash() {
      this.SubmitProcessing = true;
      axios.put("cash/" + this.cash.id, this.cash).then(() => {
        this.SubmitProcessing = false;
        Fire.$emit("Event_Cash");
        this.makeToast("success", this.$t("Update.TitleCat"), this.$t("Success"));
      }).catch(() => { this.SubmitProcessing = false; this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed")); });
    },
    reset_Form() {
      this.cash = { id: "", name: "", code: "", description: "", state: 1, warehouse_id: null };
    },
    Remove_Cash(id) {
      this.$swal({ title: this.$t("Delete.Title"), text: this.$t("Delete.Text"), type: "warning", showCancelButton: true, confirmButtonColor: "#3085d6", cancelButtonColor: "#d33", cancelButtonText: this.$t("Delete.cancelButtonText"), confirmButtonText: this.$t("Delete.confirmButtonText") }).then((result) => {
        if (result.value) {
          axios.delete("cash/" + id).then(() => {
            this.$swal(this.$t("Delete.Deleted"), this.$t("Delete.CatDeleted"), "success");
            Fire.$emit("Delete_Cash");
          }).catch(() => this.$swal(this.$t("Delete.Failed"), this.$t("Delete.Therewassomethingwronge"), "warning"));
        }
      });
    },
    delete_by_selected() {
      if (!this.selectedIds.length) return;
      this.$swal({ title: this.$t("Delete.Title"), text: this.$t("Delete.Text"), type: "warning", showCancelButton: true, confirmButtonColor: "#3085d6", cancelButtonColor: "#d33", cancelButtonText: this.$t("Delete.cancelButtonText"), confirmButtonText: this.$t("Delete.confirmButtonText") }).then((result) => {
        if (result.value) {
          NProgress.start();
          Promise.all(this.selectedIds.map((id) => axios.delete("cash/" + id)))
            .then(() => { this.$swal(this.$t("Delete.Deleted"), this.$t("Delete.CatDeleted"), "success"); Fire.$emit("Delete_Cash"); })
            .catch(() => this.$swal(this.$t("Delete.Failed"), this.$t("Delete.Therewassomethingwronge"), "warning"))
            .finally(() => NProgress.done());
        }
      });
    }
  },
  created() {
    this.Get_Cash(1);
    Fire.$on("Event_Cash", () => { setTimeout(() => { this.Get_Cash(this.serverParams.page); this.$bvModal.hide("New_Cash"); }, 500); });
    Fire.$on("Delete_Cash", () => { setTimeout(() => this.Get_Cash(this.serverParams.page), 500); });
  }
};
</script>
